<?php

namespace App\Http\Controllers;

use App\Setting;
use App\Support\WhtConfiguration;
use Hamcrest\Core\Set;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

use App\Http\Controllers\RoleManageController;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingsController extends Controller
{

    public function general_show()
    {
        $businessId = (int) optional(request()->user())->business_id;
        $cacheKey = 'general_settings_' . $businessId;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $data = json_decode($cached, true);
        } else {
            $systemSettings = $this->generalSettingsQuery($businessId)->firstOrFail();
            $data = json_decode($systemSettings->content, true);
        }
        $data = app(WhtConfiguration::class)->withDefaults(is_array($data) ? $data : []);
        return view('admin.settings.general')->with('settings', $data);
    }

    public function general_update(Request $request)
    {
        $request->validate([
            'company_name' => 'required',
            'company_logo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'header_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'vat_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'wht_enabled' => 'required|in:0,1',
            'wht_mode' => 'required|in:manual,rule_based',
            'wht_manual_rate' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'discount_enabled' => 'sometimes|in:0,1',
            'discount_percentage' => $request->input('discount_enabled') === '1'
                ? ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(?:\.\d{1,2})?$/']
                : 'sometimes',
            'company_color' => 'nullable|regex:/^#[0-9A-F]{6}$/i'
        ]);

        $businessId = (int) optional($request->user())->business_id;
        $setting_general = $this->generalSettingsQuery($businessId)->firstOrFail();
        $setting_general_data = json_decode($setting_general->content, true) ?: [];
        $whtSettings = app(WhtConfiguration::class)->normalize($request->only([
            'wht_enabled', 'wht_mode', 'wht_manual_rate',
        ]));
        $company_new_logo = $setting_general_data['company_logo'] ?? 'upload/company-logo/e.png';
        $company_new_header_image = $setting_general_data['header_image'] ?? null;
        $oldCompanyLogo = null;
        $oldHeaderImage = null;

        if ($request->hasFile('company_logo')) {
            $oldCompanyLogo = $company_new_logo;
            $company_new_logo = $this->storeSettingsImage($request->file('company_logo'), 'upload/company-logo');
        }

        if ($request->hasFile('header_image')) {
            $oldHeaderImage = $company_new_header_image;
            $company_new_header_image = $this->storeSettingsImage($request->file('header_image'), 'upload/header-image');
        }

        $discountEnabled = array_key_exists('discount_enabled', $request->all())
            ? $request->input('discount_enabled') === '1'
            : (bool) ($setting_general_data['discount_enabled'] ?? false);
        $discountPercentage = $discountEnabled
            ? number_format((float) $request->input('discount_percentage'), 2, '.', '')
            : ($setting_general_data['discount_percentage'] ?? '0.00');

        $data = array_merge($setting_general_data, array(
            'company_name' => $request->company_name,
            'contract_person' => $request->contract_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'address_1' => $request->address_1,
            'address_2' => $request->address_2,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'company_logo' => $company_new_logo,
            'header_image' => $company_new_header_image,
            'vat_percentage' => number_format((float) $request->vat_percentage, 2, '.', ''),
            'discount_enabled' => $discountEnabled,
            'discount_percentage' => $discountPercentage,
            'company_color' => $request->company_color ?? '#00897B',
            'wht_enabled' => $whtSettings['wht_enabled'],
            'wht_mode' => $whtSettings['wht_mode'],
            'wht_manual_rate' => $whtSettings['wht_manual_rate'],
        ));

        try {
            $json_data = json_encode($data, JSON_THROW_ON_ERROR);
            DB::transaction(function () use ($data, $json_data, $businessId, $setting_general) {
                $setting = $this->generalSettingsQuery($businessId)
                    ->whereKey($setting_general->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $setting->vat_percentage = $data['vat_percentage'];
                $setting->content = $json_data;
                $setting->save();
            });
            Cache::put('general_settings_' . $businessId, $json_data);

            if ($oldCompanyLogo && $oldCompanyLogo !== 'upload/company-logo/e.png'
                && !$this->settingImageIsUsedElsewhere($oldCompanyLogo, 'company_logo', $setting_general->id)) {
                $this->deleteSettingsImage($oldCompanyLogo, 'upload/company-logo');
            }
            if ($oldHeaderImage && !$this->settingImageIsUsedElsewhere($oldHeaderImage, 'header_image', $setting_general->id)) {
                $this->deleteSettingsImage($oldHeaderImage, 'upload/header-image');
            }

            Session::flash('success', __('ui.messages.updated'));
        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        return redirect()->back();
    }

    private function storeSettingsImage($file, string $directory): string
    {
        $targetDirectory = public_path($directory);
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            throw new \RuntimeException('Unable to create the image upload directory.');
        }

        $extension = strtolower($file->extension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new \RuntimeException('Unsupported image type.');
        }
        $filename = Str::uuid()->toString() . '.' . $extension;
        $file->move($targetDirectory, $filename);

        return trim(str_replace('\\', '/', $directory), '/') . '/' . $filename;
    }

    private function generalSettingsQuery(int $businessId)
    {
        $query = Setting::withoutGlobalScope('business')->where('settings_name', 'General Settings');
        return $businessId > 0
            ? $query->where('business_id', $businessId)
            : $query->whereNull('business_id');
    }

    private function settingImageIsUsedElsewhere(string $path, string $field, int $settingId): bool
    {
        return Setting::withoutGlobalScope('business')
            ->where('settings_name', 'General Settings')
            ->where('id', '<>', $settingId)
            ->pluck('content')
            ->contains(function ($content) use ($path, $field) {
                $settings = json_decode($content, true);
                return is_array($settings) && ($settings[$field] ?? null) === $path;
            });
    }

    private function deleteSettingsImage(?string $storedPath, string $expectedDirectory): void
    {
        if (!$storedPath) {
            return;
        }

        $normalized = ltrim(str_replace('\\', '/', $storedPath), '/');
        $expectedPrefix = trim(str_replace('\\', '/', $expectedDirectory), '/') . '/';
        if (strpos($normalized, $expectedPrefix) !== 0) {
            return;
        }

        $baseDirectory = realpath(public_path($expectedDirectory));
        if ($baseDirectory === false) {
            return;
        }

        $candidate = realpath($baseDirectory . DIRECTORY_SEPARATOR . basename($normalized));
        $basePrefix = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($candidate !== false && strpos($candidate, $basePrefix) === 0 && is_file($candidate)) {
            unlink($candidate);
        }
    }

    //    settings/system
    public function system_show()
    {
        if (Cache::get('system_settings') && Cache::get('system_settings') != null) {
            $data = json_decode(Cache::get('system_settings'), true);
        } else {
            $system_settings_info = Setting::where('settings_name', 'System Settings')->get()->first();
            $data = json_decode($system_settings_info->content, true);
        }
        return view('admin.settings.system')->with('settings', $data);
    }
    public function system_update(Request $request)
    {
        $setting_system = Setting::where('settings_name', 'System Settings')->get()->first();
        $currencies = config('currencies');
        $currencyCode = $request->currency_code;

        if (!array_key_exists($currencyCode, $currencies)) {
            return redirect()->back()->withInput()->withErrors([
                'currency_code' => 'Please select a valid currency.',
            ]);
        }

        $data = array(
            'date_format' => $request->date_format,
            'timezone' => $request->timezone,
            'currency_code' => $currencyCode,
            'currency_symbol' => $currencies[$currencyCode],
            'is_code' => $request->is_code,
            'is_rtl' => $request->is_rtl,
            'currency_position' => $request->currency_position,
            'fixed_asset_schedule_starting_date' => date('Y-m-d', strtotime($request->fixed_asset_schedule_starting_date))
        );
        try {
            $json_data = json_encode($data);
            $setting_system->content = $json_data;
            Cache::put('system_settings', $json_data);
            $setting_system->save();
            Session::flash('success', __('ui.messages.updated'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        return redirect()->back();
    }
}
