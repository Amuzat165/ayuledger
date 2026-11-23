<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Tenant;
use App\TenantMembership;
use App\BusinessSetting;
use App\Branch;
use App\Profile;
use App\SubscriptionPlan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }


    public function showRegistrationForm()
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        return view('auth.register', compact('plans'));
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $request->validate([
            'business_name' => 'required|string|max:191',
            'phone' => 'required|string|max:30',
            'subscription_plan' => 'required|exists:subscription_plans,slug,is_active,1',
            'subscription_interval' => 'required|in:monthly,yearly',
        ]);

        try {
            $user = DB::transaction(function () use ($request) {
                $slugBase = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $request->business_name));
                $slug = trim($slugBase, '-');
                $tenant = Tenant::create([
                    'name' => $request->business_name,
                    'slug' => $slug . '-' . uniqid(),
                    'status' => 'pending_payment',
                ]);

                BusinessSetting::create([
                    'id' => $tenant->id,
                    'tenant_id' => $tenant->id,
                    'business_name' => $request->business_name,
                    'business_email' => $request->email,
                    'phone' => $request->phone,
                    'subscription_plan' => $request->subscription_plan,
                    'subscription_interval' => $request->subscription_interval,
                    'registration_status' => 'pending',
                    'subscription_status' => 'pending_payment',
                ]);

                $templates = DB::table('settings')
                    ->where('business_id', 1)
                    ->get();

                foreach ($templates as $template) {
                    if (DB::table('settings')
                        ->where('business_id', $tenant->id)
                        ->where('settings_name', $template->settings_name)
                        ->exists()) {
                        continue;
                    }

                    DB::table('settings')->insert([
                        'settings_name' => $template->settings_name,
                        'content' => $template->content,
                        'business_id' => $tenant->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $role = \App\RoleManage::withoutGlobalScopes()->where('name', 'Admin')->first();
                if (!$role) {
                    $role = \App\RoleManage::create([
                        'name' => 'Admin',
                        'content' => '{}',
                    ]);
                }
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role_manage_id' => $role ? $role->id : null,
                    'business_id' => $tenant->id,
                ]);

                TenantMembership::create([
                    'tenant_id' => $tenant->id,
                    'business_id' => $tenant->id,
                    'user_id' => $user->id,
                    'role_manage_id' => $user->role_manage_id,
                    'status' => 'pending',
                ]);

                $branch = new Branch();
                $branch->tenant_id = $tenant->id;
                $branch->business_id = $tenant->id;
                $branch->name = $request->business_name . ' - Main Branch (' . $tenant->id . ')';
                $branch->create_by = $user->email;
                $branch->save();

                $profile = new Profile();
                $profile->user_id = $user->id;
                $profile->business_id = $tenant->id;
                $profile->save();

                return $user;
            });
        } catch (\Throwable $exception) {
            Log::error('Registration could not be completed.', ['exception' => $exception]);
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['registration' => 'We could not complete your registration right now. Please try again shortly.']);
        }

        try {
            $request->session()->put('email_verification_user_id', $user->id);
            $codeStatus = $this->sendVerificationCode($user);
        } catch (\Throwable $exception) {
            Log::error('Registration verification step failed.', ['user_id' => $user->id, 'exception' => $exception]);
            $codeStatus = 'storage_failed';
        }

        if ($codeStatus !== 'sent') {
            return redirect()->route('verification.notice')->with('status', 'Your account was created, but verification could not be completed. Please contact support to finish setting up email verification.');
        }

        return redirect()->route('verification.notice')->with('status', 'We sent a verification code to '.$user->email.'.');
    }

    public function showVerificationForm(Request $request)
    {
        if (!$request->session()->has('email_verification_user_id')) {
            return redirect()->route('register');
        }
        return view('auth.verify-code');
    }

    public function verifyCode(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        $user = User::find($request->session()->get('email_verification_user_id'));
        if (!$user) return redirect()->route('register')->withErrors(['email' => 'Please register again to verify your account.']);

        if (!$this->hasValidVerificationCode($user, $request->code)) {
            return back()->withErrors(['code' => 'The code is invalid or expired. Request a new code and try again.']);
        }

        $this->markEmailAsVerified($user);
        $request->session()->forget('email_verification_user_id');
        auth()->login($user);

        return redirect()->route('business.payment')->with('status', 'Your email has been verified.');
    }

    public function verifyByEmailLink(Request $request, User $user, $code)
    {
        if ($user->email_verified_at) {
            return redirect()->route('login')->with('status', 'Your email is already verified. Please sign in.');
        }

        if (!$this->hasValidVerificationCode($user, $code)) {
            return redirect()->route('login')->withErrors([
                'email' => 'This verification link has expired. Sign in to request a new verification email.',
            ]);
        }

        $this->markEmailAsVerified($user);
        $request->session()->forget('email_verification_user_id');
        auth()->login($user);

        return redirect()->route('business.payment')->with('status', 'Your email has been verified.');
    }

    public function resendVerificationCode(Request $request)
    {
        $key = 'email-verification:'.$request->session()->get('email_verification_user_id');
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['code' => 'Too many requests. Please wait before requesting another code.']);
        }
        RateLimiter::hit($key, 60);
        $user = User::find($request->session()->get('email_verification_user_id'));
        if (!$user) return redirect()->route('register');
        $result = $this->sendVerificationCode($user);
        if ($result === 'sent') {
            return back()->with('status', 'A new verification code was sent.');
        }
        if ($result === 'storage_failed') {
            return back()->withErrors(['code' => 'Verification setup is incomplete. Please contact support to apply the email verification database migration.']);
        }

        return back()->withErrors(['code' => 'The verification email could not be delivered. Please contact support.']);
    }

    private function sendVerificationCode(User $user)
    {
        try {
            $code = (string) random_int(100000, 999999);
            $expiresAt = now()->addMinutes(10);
            $user->forceFill([
                'email_verification_code' => Hash::make($code),
                'email_verification_expires_at' => $expiresAt,
            ])->save();
        } catch (\Throwable $exception) {
            Log::error('Registration verification code could not be stored.', ['user_id' => $user->id, 'exception' => $exception]);
            return 'storage_failed';
        }

        try {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.email-link',
                $expiresAt,
                ['user' => $user->getKey(), 'code' => $code]
            );

            Mail::send([
                'html' => 'emails.verify-address',
                'text' => 'emails.verify-address-text',
            ], compact('user', 'code', 'verificationUrl'), function ($message) use ($user) {
                $message->to($user->email)->subject('Verify your AYULEDGERS email');
            });

            return 'sent';
        } catch (\Throwable $exception) {
            Log::error('Registration verification email could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception,
            ]);

            return 'delivery_failed';
        }
    }

    private function hasValidVerificationCode(User $user, $code)
    {
        return $user->email_verification_code
            && $user->email_verification_expires_at
            && now()->lessThanOrEqualTo($user->email_verification_expires_at)
            && Hash::check($code, $user->email_verification_code);
    }

    private function markEmailAsVerified(User $user)
    {
        $user->forceFill([
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'email_verified_at' => now(),
        ])->save();
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
