<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartRoom Temporary Password</title>
</head>
<body style="margin:0;padding:0;background:#f6f8fb;font-family:Arial,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 0;background:#f6f8fb;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td style="background:#0b1640;color:#ffffff;padding:20px 24px;">
                            <h1 style="margin:0;font-size:20px;">SmartRoom Account Created</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;line-height:1.5;">
                            <p style="margin:0 0 12px;">Hello {{ $user->name }},</p>
                            <p style="margin:0 0 16px;">An administrator created your SmartRoom account. Use this temporary password to sign in:</p>
                            <p style="margin:0 0 20px;padding:12px 14px;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;font-size:18px;font-weight:700;letter-spacing:0.4px;">
                                {{ $temporaryPassword }}
                            </p>
                            <p style="margin:0 0 10px;">For your security, you must change this password immediately after your first login.</p>
                            <p style="margin:0 0 18px;">Login email: <strong>{{ $user->email }}</strong></p>
                            <p style="margin:0;color:#6b7280;font-size:13px;">If you did not expect this account, contact your SmartRoom administrator.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
