<?php

namespace App\Http\Controllers;

use App\SiteSetting;
use App\SiteSlide;
use App\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\BusinessFeedback;
use App\Testimonial;
use App\Services\PlatformEmail;

class LandingController extends Controller
{
    public function index()
    {
        // if (Auth::check()) {
        //     return redirect()->route('dashboard');
        // }

        $settings = SiteSetting::withoutGlobalScopes()->whereNull('business_id')->get()->pluck('value', 'key_name');
        $slides = SiteSlide::where('is_active', true)->orderBy('sort_order')->get();
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        $testimonials = Testimonial::where('is_active', true)->orderBy('sort_order')->orderBy('id')->limit(10)->get();

        return view('landing', compact('settings', 'slides', 'plans', 'testimonials'));
    }

    public function contact(Request $request, PlatformEmail $email)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'subject' => 'nullable|string|max:191',
            'message' => 'required|string|max:5000',
        ]);

        BusinessFeedback::create([
            'business_id' => null,
            'user_id' => null,
            'subject' => ($data['subject'] ?: 'Website contact enquiry') . ' — ' . $data['name'] . ' <' . $data['email'] . '>',
            'message' => $data['message'],
        ]);
        $email->send('info@ayuledgers.com', 'Website contact: '.($data['subject'] ?: 'Contact enquiry'), [
            'Name: '.$data['name'], 'Email: '.$data['email'], 'Subject: '.($data['subject'] ?: 'Website contact enquiry'), 'Message:', $data['message'],
        ], $data['email']);

        return back()->with('contact_status', 'Thank you. Your message has been sent successfully.');
    }
}
