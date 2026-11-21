<?php

namespace App\Http\Controllers;

use App\BusinessSetting;
use App\BusinessPaymentVerification;
use App\Tenant;
use App\TenantMembership;
use App\SiteSetting;
use App\SiteSlide;
use App\SubscriptionPlan;
use App\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\PlatformEmail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $role = $request->user()->role;
            abort_unless($role && strcasecmp($role->name, 'Super Admin') === 0, 403);
            return $next($request);
        });
    }

    public function index()
    {
        $businesses = BusinessSetting::with('tenant')->orderByDesc('id')->paginate(20);
        $payments = BusinessPaymentVerification::with('business')->where('status', 'pending')->latest()->get();
        $allPayments = BusinessPaymentVerification::with('business')->latest()->paginate(30, ['*'], 'payments_page');
        return view('superadmin.dashboard', compact('businesses', 'payments', 'allPayments'));
    }

    public function emailSettings()
    {
        $settings = SiteSetting::withoutGlobalScopes()->whereNull('business_id')->get()->pluck('value', 'key_name');
        return view('superadmin.email-settings', compact('settings'));
    }

    public function saveEmailSettings(Request $request)
    {
        $data = $request->validate([
            'email_host' => 'required|string|max:191', 'email_port' => 'required|integer|min:1|max:65535',
            'email_username' => 'nullable|string|max:191', 'email_password' => 'nullable|string|max:1000',
            'email_from_address' => 'required|email|max:191', 'email_from_name' => 'nullable|string|max:191',
            'email_reply_to' => 'nullable|email|max:191', 'email_encryption' => 'required|in:tls,ssl,none',
            'email_footer' => 'nullable|string|max:1000', 'email_logo' => 'nullable|image|max:2048',
        ]);
        unset($data['email_password']);
        foreach ($data as $key => $value) $this->saveGlobalSiteSetting($key, (string) ($value ?? ''));
        if ($request->filled('email_password')) $this->saveGlobalSiteSetting('email_password', Crypt::encryptString($request->input('email_password')));
        if ($request->hasFile('email_logo')) {
            $path = $request->file('email_logo')->store('email-branding', 'public');
            $this->saveGlobalSiteSetting('email_logo', $path);
        }
        return back()->with('status', 'Email settings saved.');
    }

    public function testEmailSettings(Request $request, PlatformEmail $email)
    {
        $request->validate(['test_email' => 'required|email']);
        $sent = $email->send($request->test_email, 'AYULEDGERS email configuration test', ['Your email configuration is working.']);
        return back()->with($sent ? 'status' : 'error', $sent ? 'Test email sent.' : 'Test email failed. Check the mail server settings and application log.');
    }

    public function database(Request $request)
    {
        $tables = DB::table('information_schema.tables')
            ->where('table_schema', DB::getDatabaseName())
            ->orderBy('table_name')
            ->pluck('table_name')
            ->filter(function ($table) {
            return !in_array($table, ['migrations'], true);
            })
            ->values()
            ->all();
        sort($tables);

        $table = $request->query('table');
        if (!in_array($table, $tables, true)) {
            $table = in_array('users', $tables, true) ? 'users' : ($tables[0] ?? null);
        }

        $columns = $table ? Schema::getColumnListing($table) : [];
        $query = $table ? DB::table($table) : null;
        $businessId = $request->query('business_id');

        if ($query && in_array('business_id', $columns, true) && $businessId !== null && $businessId !== '') {
            $query->where('business_id', $businessId);
        }

        $rows = $query ? $query->limit(500)->get() : collect();
        $businesses = BusinessSetting::withoutGlobalScopes()
            ->orderBy('business_name')
            ->get(['id', 'business_name']);

        return view('superadmin.database', compact(
            'tables',
            'table',
            'columns',
            'rows',
            'businesses',
            'businessId'
        ));
    }

    public function approve(BusinessSetting $business)
    {
        $wasApproved = $business->registration_status === 'approved';
        $business->update([
            'registration_status' => 'approved',
            'subscription_status' => 'active',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
        Tenant::whereKey($business->tenant_id)->update(['status' => 'active']);
        TenantMembership::where('tenant_id', $business->tenant_id)->update(['status' => 'active']);
        if (!$wasApproved) {
            $user = \App\User::where('business_id', $business->id)->first();
            app(PlatformEmail::class)->send(optional($user)->email ?: $business->business_email, 'Your AYULEDGERS account is approved', ['Your account for '.$business->business_name.' has been approved by our team.', 'You can now sign in and use your workspace.']);
        }
        return back()->with('status', $business->business_name . ' was approved.');
    }

    public function reject(Request $request, BusinessSetting $business)
    {
        $request->validate(['admin_note' => 'required|string|max:2000']);
        $business->update([
            'registration_status' => 'rejected',
            'subscription_status' => 'inactive',
            'admin_note' => $request->admin_note,
        ]);
        Tenant::whereKey($business->tenant_id)->update(['status' => 'inactive']);
        TenantMembership::where('tenant_id', $business->tenant_id)->update(['status' => 'inactive']);
        return back()->with('status', $business->business_name . ' was rejected.');
    }

    public function verifyPayment(BusinessPaymentVerification $verification)
    {
        $verification->update(['status' => 'verified']);
        $business = $verification->business;
        $business->update([
            'subscription_status' => 'active',
            'subscription_starts_at' => now()->toDateString(),
            'subscription_expires_at' => ($business->subscription_interval === 'yearly' ? now()->addYear() : now()->addMonth())->toDateString(),
        ]);
        Tenant::whereKey($business->tenant_id)->update(['status' => 'active']);
        TenantMembership::where('tenant_id', $business->tenant_id)->update(['status' => 'active']);

        $user = \App\User::where('business_id', $business->id)->first();
        app(PlatformEmail::class)->notifyPayment($business->fresh(), $user, $verification, 'successful');

        return back()->with('status', 'Payment verified and business activated.');
    }

    public function siteBuilder()
    {
        $settings = SiteSetting::withoutGlobalScopes()->whereNull('business_id')->get()->pluck('value', 'key_name');
        $slides = SiteSlide::orderBy('sort_order')->get();
        $plans = SubscriptionPlan::orderBy('sort_order')->get();
          $testimonials = Testimonial::orderBy('sort_order')->get();
          return view('superadmin.site-builder', compact('settings', 'slides', 'plans', 'testimonials'));
    }

    public function saveSiteSettings(Request $request)
    {
          foreach ($request->except(['_token', '_method', 'logo', 'favicon', 'stats_enabled', 'testimonials_enabled', 'brand_name']) as $key => $value) {
              $this->saveGlobalSiteSetting($key, (string) $value);
        }

          foreach (['stats_enabled', 'testimonials_enabled'] as $toggle) {
              $this->saveGlobalSiteSetting($toggle, $request->has($toggle) ? '1' : '0');
          }

        foreach (['logo' => 'logo_path', 'favicon' => 'favicon_path'] as $field => $settingKey) {
            if ($request->hasFile($field)) {
                $request->validate([$field => 'image|max:2048']);
                $path = $request->file($field)->store('site-branding', 'public');
                  $this->saveGlobalSiteSetting($settingKey, $path);
            }
        }
        return back()->with('status', 'Landing page settings saved.');
    }

    public function saveSlide(Request $request, SiteSlide $slide = null)
    {
        $data = $request->validate([
            'eyebrow' => 'nullable|string|max:191',
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'primary_label' => 'nullable|string|max:100',
            'primary_url' => 'nullable|string|max:191',
            'secondary_label' => 'nullable|string|max:100',
            'secondary_url' => 'nullable|string|max:191',
            'image' => 'nullable|string|max:191',
            'image_file' => 'nullable|image|max:5120',
            'image_files' => 'nullable|array|max:20',
            'image_files.*' => 'nullable|image|max:5120',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);
        if ($request->hasFile('image_file')) {
            $data['image'] = $request->file('image_file')->store('site-slides', 'public');
        }
        unset($data['image_file'], $data['image_files']);
        $data['is_active'] = $request->has('is_active');
        if ($slide) {
            $slide->update($data);
        } else {
            $uploadedImages = $request->file('image_files', []);
            if (count($uploadedImages)) {
                foreach ($uploadedImages as $index => $uploadedImage) {
                    $slideData = $data;
                    $slideData['image'] = $uploadedImage->store('site-slides', 'public');
                    $slideData['sort_order'] = ($data['sort_order'] ?? 0) + $index;
                    SiteSlide::create($slideData);
                }
            } else {
                SiteSlide::create($data);
            }
        }
        return back()->with('status', 'Carousel slide(s) saved.');
    }

    public function deleteSlide(SiteSlide $slide)
    {
        $slide->delete();
        return back()->with('status', 'Carousel slide removed.');
    }

      public function saveTestimonial(Request $request, Testimonial $testimonial = null)
      {
          $data = $request->validate([
              'name' => 'required|string|max:191',
              'role' => 'nullable|string|max:191',
              'quote' => 'required|string|max:2000',
              'image_file' => 'nullable|image|max:5120',
              'rating' => 'required|integer|min:1|max:5',
              'sort_order' => 'nullable|integer|min:0',
              'is_active' => 'nullable',
          ]);
          unset($data['image_file']);
          if (!$testimonial && Testimonial::count() >= 10) {
              return back()->withErrors(['testimonial_limit' => 'You can add up to 10 testimonials. Remove one before adding another.']);
          }
          if ($request->hasFile('image_file')) {
              $newImage = $request->file('image_file')->store('testimonials', 'public');
              if ($testimonial && $testimonial->image) {
                  \Illuminate\Support\Facades\Storage::disk('public')->delete($testimonial->image);
              }
              $data['image'] = $newImage;
          }
          $data['is_active'] = $request->has('is_active');
          if ($testimonial) {
              $testimonial->update($data);
          } else {
              Testimonial::create($data);
          }
          return back()->with('status', 'Testimonial saved.');
      }

      private function saveGlobalSiteSetting($key, $value)
      {
          $query = DB::table('site_settings')->whereNull('business_id')->where('key_name', $key);
          if ($query->exists()) {
              $query->update(['value' => $value, 'updated_at' => now()]);
              return;
          }

          DB::table('site_settings')->insert([
              'business_id' => null,
              'key_name' => $key,
              'value' => $value,
              'created_at' => now(),
              'updated_at' => now(),
          ]);
      }

      public function deleteTestimonial(Testimonial $testimonial)
      {
          $image = $testimonial->image;
          if ($testimonial->delete() && $image) {
              \Illuminate\Support\Facades\Storage::disk('public')->delete($image);
          }
          return back()->with('status', 'Testimonial removed.');
      }

    public function savePlan(Request $request, SubscriptionPlan $plan)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'max_users' => 'required|integer|min:1|max:10000',
            'description' => 'nullable|string|max:1000',
            'features' => 'nullable|string|max:3000',
            'is_featured' => 'nullable',
            'is_active' => 'nullable',
        ]);
        $features = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['features'] ?? ''))));
        unset($data['features']);
        $data['features'] = json_encode($features);
        $data['is_featured'] = $request->has('is_featured');
        $data['is_active'] = $request->has('is_active');
        $plan->update($data);
        return back()->with('status', $plan->name . ' plan saved.');
    }
}
