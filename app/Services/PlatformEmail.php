<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PlatformEmail
{
    public function settings()
    {
        return DB::table('site_settings')->whereNull('business_id')->pluck('value', 'key_name')->all();
    }

    public function send($to, $subject, $lines, $replyTo = null)
    {
        if (!$to) return false;
        try {
            $settings = $this->settings();
            $config = config('mail');
            $config['driver'] = $settings['email_driver'] ?? $config['driver'];
            foreach (['host' => 'email_host', 'port' => 'email_port', 'username' => 'email_username', 'encryption' => 'email_encryption'] as $key => $setting) {
                if (!empty($settings[$setting])) $config[$key] = $settings[$setting];
            }
            if (($settings['email_encryption'] ?? null) === 'none') $config['encryption'] = null;
            if (!empty($settings['email_password'])) {
                try { $config['password'] = Crypt::decryptString($settings['email_password']); } catch (\Throwable $e) { $config['password'] = $settings['email_password']; }
            }
            $fromAddress = $settings['email_from_address'] ?? $config['from']['address'] ?? null;
            if (!$fromAddress || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL) || preg_match('/@(example\.com|example\.org)$/i', $fromAddress)) {
                $fromAddress = !empty($config['username']) && filter_var($config['username'], FILTER_VALIDATE_EMAIL)
                    ? $config['username']
                    : null;
            }
            if (!$fromAddress) {
                throw new \RuntimeException('A valid sender email address is required in platform email settings.');
            }
            $driver = $config['driver'] ?? 'smtp';
            $config['from'] = ['address' => $fromAddress, 'name' => $settings['email_from_name'] ?? 'AYULEDGERS'];
            config(['mail' => $config]);
            $mailManager = app('mail.manager');
            if (method_exists($mailManager, 'purge')) {
                $mailManager->purge($driver);
            }
            Mail::send('emails.platform', ['subject' => $subject, 'lines' => $lines, 'logo' => $settings['email_logo'] ?? null, 'footer' => $settings['email_footer'] ?? 'AYULEDGERS'], function ($message) use ($to, $subject, $replyTo, $settings) {
                $message->to($to)->subject($subject);
                if ($replyTo || !empty($settings['email_reply_to'])) $message->replyTo($replyTo ?: $settings['email_reply_to']);
            });
            return true;
        } catch (\Throwable $e) {
            Log::error('Platform email delivery failed: '.$e->getMessage(), ['recipient' => $to, 'subject' => $subject]);
            return false;
        }
    }

    public function notifyPayment($business, $user, $payment, $status)
    {
        $details = ['Business: '.$business->business_name, 'Account email: '.$business->business_email, 'Plan: '.$business->subscription_plan, 'Billing: '.$business->subscription_interval, 'Amount: NGN '.number_format((float)$payment->amount, 2), 'Payment method: '.$payment->payment_method, 'Reference: '.$payment->reference, 'Paid on: '.$payment->paid_on, 'Status: '.$status, 'Subscription expires: '.($business->subscription_expires_at ?: 'Pending activation')];
        $this->send($user ? $user->email : $business->business_email, $status === 'pending' ? 'Payment received and under review' : 'Payment confirmed', array_merge([$status === 'pending' ? 'We received your payment details. Your account will be activated after our team confirms the payment.' : 'Your payment was successful and your subscription is active.'], $details));
        $this->send('sales@ayuledgers.com', 'AYULEDGERS payment record: '.$business->business_name, $details);
    }
}
