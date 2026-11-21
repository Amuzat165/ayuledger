<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your AYULEDGERS email</title>
</head>
<body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#101828;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #eaecf0;border-radius:12px;">
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 24px;color:#175cd3;font-size:18px;font-weight:bold;">AYULEDGERS</p>
                            <h1 style="margin:0 0 16px;font-size:24px;">Verify your email</h1>
                            <p>Hello {{ $user->name }},</p>
                            <p>Click the button to verify your email, or enter this six-digit code on the verification page:</p>
                            <p style="margin:24px 0;padding:16px;background:#f4f7fb;border-radius:8px;text-align:center;font-size:30px;font-weight:bold;letter-spacing:8px;">{{ $code }}</p>
                            <p style="margin:24px 0;text-align:center;">
                                <a href="{{ $verificationUrl }}" style="display:inline-block;padding:13px 22px;background:#175cd3;border-radius:6px;color:#ffffff;text-decoration:none;font-weight:bold;">Verify my email</a>
                            </p>
                            <p>This link and code expire in 10 minutes. If you did not create an account, you can ignore this email.</p>
                            <p style="margin:24px 0 0;color:#667085;font-size:12px;word-break:break-all;">If the button does not work, copy this link into your browser:<br>{{ $verificationUrl }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
