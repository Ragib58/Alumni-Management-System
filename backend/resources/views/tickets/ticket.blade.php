<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $ticket->ticket_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; }
        .ticket {
            border: 2px solid #4f46e5;
            border-radius: 12px;
            overflow: hidden;
        }
        .header {
            background: #4f46e5;
            color: #ffffff;
            padding: 16px 20px;
        }
        .header h1 { font-size: 20px; }
        .header .sub { font-size: 11px; opacity: .85; margin-top: 2px; }
        .body { padding: 18px 20px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 4px 0; }
        .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
        .value { font-size: 14px; font-weight: bold; color: #0f172a; }
        .qr { text-align: center; }
        .qr img { width: 150px; height: 150px; }
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            font-size: 11px; font-weight: bold;
        }
        .paid { background: #dcfce7; color: #166534; }
        .pending { background: #fef9c3; color: #854d0e; }
        .free { background: #e0e7ff; color: #3730a3; }
        .footer {
            border-top: 1px dashed #cbd5e1; padding: 10px 20px;
            font-size: 10px; color: #94a3b8; text-align: center;
        }
        .muted { color: #64748b; font-size: 11px; }
    </style>
</head>
<body>
    @php
        $paymentStatus = $registration->payment_status instanceof \BackedEnum
            ? $registration->payment_status->value : $registration->payment_status;
        $badgeClass = match ($paymentStatus) {
            'paid' => 'paid',
            'free' => 'free',
            default => 'pending',
        };
    @endphp
    <div class="ticket">
        <div class="header">
            <h1>{{ $event->title ?? 'Event' }}</h1>
            <div class="sub">{{ config('app.name') }} • Event Ticket</div>
        </div>
        <div class="body">
            <table>
                <tr>
                    <td style="width: 62%;">
                        <table>
                            <tr>
                                <td>
                                    <div class="label">Participant</div>
                                    <div class="value">{{ $participant->name ?? 'Guest' }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="label">Registration No.</div>
                                    <div class="value">{{ $registration->registration_no }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="label">Ticket No.</div>
                                    <div class="value">{{ $ticket->ticket_no }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="label">Date &amp; Venue</div>
                                    <div class="value" style="font-size:12px;">
                                        {{ optional($event->event_date)->format('D, d M Y • h:i A') }}
                                    </div>
                                    <div class="muted">{{ $event->venue ?? '' }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top: 8px;">
                                    <div class="label">Payment Status</div>
                                    <span class="badge {{ $badgeClass }}">{{ strtoupper($paymentStatus) }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 38%;" class="qr">
                        <img src="{{ $qr }}" alt="QR Code">
                        <div class="muted" style="margin-top:6px;">Scan at entry</div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="footer">
            This ticket is valid for one entry. Please present this QR code at the venue.
        </div>
    </div>
</body>
</html>
