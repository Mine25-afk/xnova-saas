<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;background:#0f766e;color:#ffffff;">
                            @if (filled($companyName))
                                <div style="margin:0 0 8px;font-size:11px;line-height:1.4;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.82;">{{ $companyName }}</div>
                            @endif
                            <h1 style="margin:0;font-size:20px;line-height:1.3;">{{ $title }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#1f2937;">Hola,</p>

                            @if (filled($intro))
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#1f2937;">{{ $intro }}</p>
                            @endif

                            @if (!empty($detailLines))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                                    @foreach ($detailLines as $line)
                                        <tr>
                                            <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;line-height:1.6;color:#334155;">{{ $line }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if (filled($footer))
                                <p style="margin:16px 0 0;font-size:13px;line-height:1.6;color:#475569;">{{ $footer }}</p>
                            @endif

                            <p style="margin:16px 0 0;font-size:12px;line-height:1.6;color:#94a3b8;">Gracias por confiar en {{ $companyName ?: 'nuestro equipo' }}.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
