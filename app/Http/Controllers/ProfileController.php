<?php

namespace App\Http\Controllers;

use App\Language;
use Illuminate\Http\Request;
use App\Profile;
use App\User;
use App\Support\LanguagePreference;
use App\Support\LocaleManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $profile = auth()->user()->profile;
        $languages = Language::get();
        $language_id = auth()->user()->language_id;
        return view('admin.profile.index', compact('languages', 'language_id'))->with('profile', $profile);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'avatar' => 'image',
            'language_id' => ['nullable', 'integer', Rule::exists('languages', 'id')->whereNull('deleted_at')],
        ]);

        $user = Auth::user();
        $user->profile->first_name = $request['first_name'];
        $user->profile->last_name = $request['last_name'];
        $user->profile->phone_number = $request['phone_number'];
        $user->profile->designation = $request['designation'];
        $user->profile->gender = $request['gender'];
        $user->profile->NID = $request['NID'];
        $user->profile->education = $request['education'];
        $user->profile->permanent_address = $request['permanent_address'];
        $user->profile->description = $request['description'];
        $user->profile->present_address = $request['present_address'];

        if ($request->hasFile('avatar')) {
            if (!empty($user->profile->avatar)  and $user->profile->avatar != 'upload/avatar/avatar.png') {
                unlink($user->profile->avatar); // Delete previous image file
            }
            $company_logo = $request->avatar;
            $temporaryName = time() . $company_logo->getClientOriginalName();
            $company_logo->move("upload/avatar", $temporaryName);
            $user->profile->avatar = 'upload/avatar/' . $temporaryName;
        }
        $user->profile->save();
        if (!env('DEMO_MODE')) {
            $user->language_id = $request->input('language_id');
            $user->save();
            $languages = app(LanguagePreference::class)->activeLanguages();
            $locale = app(LanguagePreference::class)->localeForLanguageId($languages, $user->language_id)
                ?: LocaleManager::DEFAULT_LOCALE;
            $request->session()->put('app_locale', $locale);
            // The saved profile language now takes effect over any unsaved dropdown choice.
            $request->session()->forget('app_locale_override');
            app()->setLocale($locale);
            Session::flash('success', __('ui.messages.updated'));
        }else{
            Session::flash('success', __('ui.messages.profile_language_disabled'));
        }

        return redirect()->route('profile');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function changePassword(Request $request)
    {

        $request->validate([
            'OldPassword' => 'required',
            'NewPassword' => 'required|min:6',
            'NewPasswordConfirm' => 'required|min:6',
        ]);

        if ($request["NewPassword"] !== $request["NewPasswordConfirm"]) {
            Session::flash('error', __('ui.messages.password_confirmation_mismatch'));
            return redirect()->back();
        }

        if (Hash::check($request["OldPassword"], Auth::user()->password)) {

            $user = User::find(Auth::user()->id);
            $user->password = Hash::make($request["NewPassword"]);
            $user->save();
            Session::flash('success', __('ui.messages.password_updated'));
            return redirect()->back();
        } else {

            Session::flash('error', __('ui.messages.old_password_incorrect'));
            return redirect()->back();
        }
    }
}
