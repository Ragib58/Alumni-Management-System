<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Ticket</title>
</head>
<body style="margin:0; padding:0; background:#f1f5f9; font-family: Arial, Helvetica, sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0"
                       style="background:#ffffff; border-radius:12px; overflow:hidden; max-width:560px;">
                    <tr>
                        <td style="background:#4f46e5; padding:24px 32px; color:#ffffff;">
                            <h1 style="margin:0; font-size:20px;">{{ config('app.name') }}</h1>
                            <p style="margin:4px 0 0; font-size:13px; opacity:.85;">Your ticket is confirmed 🎉</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; font-size:15px;">Hi {{ $participant->name ?? 'there' }},</p>
                            <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#475569;">
                                Thank you for registering for
                                <strong>{{ $event->title ?? 'the event' }}</strong>.
                                Your payment has been received and your spot is confirmed.
                                Your ticket (with QR code) is attached to this email as a PDF.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="border:1px solid #e2e8f0; border-radius:8px; margin:16px 0;">
                                <tr>
                                    <td style="padding:14px 18px; font-size:13px; color:#64748b;">Registration No.</td>
                                    <td style="padding:14px 18px; font-size:13px; font-weight:bold; text-align:right;">
                                        {{ $registration->registration_no }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px; font-size:13px; color:#64748b; border-top:1px solid #e2e8f0;">Ticket No.</td>
                                    <td style="padding:14px 18px; font-size:13px; font-weight:bold; text-align:right; border-top:1px solid #e2e8f0;">
                                        {{ $ticket->ticket_no }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px; font-size:13px; color:#64748b; border-top:1px solid #e2e8f0;">Date</td>
                                    <td style="padding:14px 18px; font-size:13px; font-weight:bold; text-align:right; border-top:1px solid #e2e8f0;">
                                        {{ optional($event->event_date)->format('D, d M Y • h:i A') }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0; font-size:13px; color:#94a3b8;">
                                Please present the attached QR code at the venue entrance.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px; background:#f8fafc; font-size:12px; color:#94a3b8; text-align:center;">
                            © {{ date('Y') }} {{ config('app.name') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
