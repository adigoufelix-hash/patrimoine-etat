<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventaire du patrimoine - {{ now()->format('d/m/Y') }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #1c2128; font-size: 13px; }
        h1 { color: #006A4E; margin: 0 0 4px; font-size: 22px; }
        .sous { color: #6b7280; margin: 0 0 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #006A4E; color: #fff; text-align: left; padding: 8px 10px; font-size: 12px; }
        td { padding: 8px 10px; border-bottom: 1px solid #dee3eb; }
        tr:nth-child(even) td { background: #f7f9fb; }
        .actions { margin-bottom: 20px; display: flex; gap: 12px; }
        button, a.retour { background: #006A4E; color: #fff; border: 0; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-size: 14px; text-decoration: none; }
        a.retour { background: #fff; color: #1c2128; border: 1px solid #dee3eb; }
        @media print { .actions { display: none; } body { margin: 12mm; } tr { page-break-inside: avoid; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
        <a class="retour" href="/biens">Retour</a>
    </div>

    <h1>Inventaire du patrimoine de l’État</h1>
    <p class="sous">Édité le {{ now()->format('d/m/Y à H:i') }} · {{ count($biens) }} bien(s)</p>

    <table>
        <thead>
            <tr><th>Code</th><th>Désignation</th><th>Catégorie</th><th>État</th><th>Service</th><th>Valeur (FCFA)</th></tr>
        </thead>
        <tbody>
            @foreach ($biens as $b)
                <tr>
                    <td>{{ $b['code_inventaire'] }}</td>
                    <td>{{ $b['designation'] }}</td>
                    <td>{{ $b['categorie']['nom'] ?? '-' }}</td>
                    <td>{{ $etats[$b['etat']] ?? $b['etat'] }}</td>
                    <td>{{ $b['service'] ?? 'Non affecté' }}</td>
                    <td>{{ $b['valeur'] !== null ? number_format((float) $b['valeur'], 0, ',', ' ') : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
