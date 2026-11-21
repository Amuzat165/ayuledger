<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionAccess;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
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
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        $isSuperAdmin = strcasecmp($user->email, config('app.super_admin_email', 'admin@gmail.com')) === 0
            || ($user->role && strcasecmp($user->role->name, 'Super Admin') === 0);

        if (!$isSuperAdmin && !$user->email_verified_at) {
            auth()->logout();
            $request->session()->put('email_verification_user_id', $user->id);
            return redirect()->route('verification.notice')->with('status', 'Please verify your email before continuing.');
        }

        $business = $user->business;
        $subscriptions = app(SubscriptionAccess::class);
        if ($subscriptions->isPaymentPending($business)) {
            return redirect()->route('business.payment');
        }

        if (!$isSuperAdmin && !$subscriptions->hasActiveSubscription($business)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your subscription is inactive or expired. Use the renewal link sent to your business email to restore access.',
            ]);
        }

        return redirect()->route('dashboard');
    }

}
