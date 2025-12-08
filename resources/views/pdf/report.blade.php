<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Compte Rendu - {{ $report->dossier->ref_number }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.6; font-size: 12pt; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24pt; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .meta-table { width: 100%; margin-bottom: 30px; }
        .meta-table td { vertical-align: top; }
        .label { font-weight: bold; color: #64748b; font-size: 10pt; text-transform: uppercase; }
        .value { font-weight: bold; margin-bottom: 10px; display: block; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; border-radius: 4px; min-height: 300px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8pt; text-align: center; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        h1 { font-size: 18pt; margin-bottom: 5px; color: #0f172a; }
        .badge { background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px; font-size: 9pt; }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">AMC Juridique <span style="font-weight:normal; font-size:12pt; color:#64748b;">| Cabinet Juridique</span></div>
    </div>

    <table class="meta-table">
        <tr>
            <td width="60%">
                <div class="label">Client</div>
                <div class="value">{{ $report->dossier->client->name }}</div>
                <div>{{ $report->dossier->client->address }}</div>
            </td>
            <td width="40%" style="text-align: right;">
                <div class="label">Dossier Réf.</div>
                <div class="value">{{ $report->dossier->ref_number }}</div>
                <div class="label">Date du document</div>
                <div>{{ $report->report_date->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 20px;">
        <span class="badge">{{ strtoupper($report->type) }}</span>
        <h1>Compte Rendu</h1>
        <p>Auteur : Me {{ $report->author->name }}</p>
    </div>

    <div class="content">
        {!! nl2br(e($report->content['body'] ?? '')) !!}
    </div>

    <div class="footer">
        Document généré automatiquement par NEXA Manager le {{ date('d/m/Y H:i') }}.<br>
        Ce document est confidentiel et couvert par le secret professionnel.
    </div>

</body>
</html>