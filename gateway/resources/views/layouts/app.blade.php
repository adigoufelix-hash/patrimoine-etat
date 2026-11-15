<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre') - Patrimoine de l’État</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <strong>Patrimoine de l’État</strong>
            <span>Gestion des biens publics</span>
        </div>
        <nav class="menu">
            <a href="/" class="{{ request()->is('/') ? 'actif' : '' }}">Tableau de bord</a>
            <a href="/biens" class="{{ request()->is('biens*') ? 'actif' : '' }}">Biens</a>
            <a href="/affectations" class="{{ request()->is('affectations*') ? 'actif' : '' }}">Affectations</a>
            <a href="/incidents" class="{{ request()->is('incidents*') ? 'actif' : '' }}">Incidents</a>
            <a href="/utilisateurs" class="{{ request()->is('utilisateurs*') ? 'actif' : '' }}">Utilisateurs</a>
        </nav>
        <div class="pied">
            <strong>Gestionnaire du patrimoine</strong>
            <a href="#">Se déconnecter</a>
        </div>
    </aside>

    <main class="contenu">
        <header class="entete">
            <div>
                <h1>@yield('titre')</h1>
                <p>@yield('sous-titre')</p>
            </div>
            <div>@yield('actions')</div>
        </header>
        @yield('contenu')
    </main>
</div>
</body>
</html>
