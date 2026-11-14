<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biens - Patrimoine de l’État</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 32px; color: #1c2128; }
        h1 { color: #1f4e79; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e3e7ec; }
        th { background: #f7f9fb; color: #6b7280; font-size: 13px; }
        .code { color: #1f4e79; font-weight: 600; }
        .erreur { background: #fdeaea; color: #b82e2e; padding: 12px 16px; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>Biens</h1>

    @if ($erreur)
        <p class="erreur">{{ $erreur }}</p>
    @else
        <table>
            <thead>
                <tr><th>Code</th><th>Désignation</th><th>Catégorie</th><th>État</th></tr>
            </thead>
            <tbody>
                @forelse ($biens as $bien)
                    <tr>
                        <td class="code">{{ $bien['code_inventaire'] }}</td>
                        <td>{{ $bien['designation'] }}</td>
                        <td>{{ $bien['categorie']['nom'] ?? '-' }}</td>
                        <td>{{ $bien['etat'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aucun bien enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
