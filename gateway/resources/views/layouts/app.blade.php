@php
    $u = session('user');
    $role = $u['role'] ?? null;
    $libelles = ['admin' => 'Administrateur', 'gestionnaire' => 'Gestionnaire du patrimoine', 'responsable' => 'Responsable de service'];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre') - PatriGest</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="role-{{ $role }}">
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <img src="{{ asset('images/logo_icone_clair.svg') }}" alt="Logo PatriGest" width="40" height="40">
            <div>
                <strong>PatriGest</strong>
                <span>Gestion des biens publics</span>
            </div>
        </div>
        <nav class="menu">
            @if (in_array($role, ['admin', 'gestionnaire']))
                <a href="/" class="{{ request()->is('/') ? 'actif' : '' }}">Tableau de bord</a>
            @endif
            <a href="/biens" class="{{ request()->is('biens*') ? 'actif' : '' }}">Biens</a>
            <a href="/affectations" class="{{ request()->is('affectations*') ? 'actif' : '' }}">Affectations</a>
            <a href="/incidents" class="{{ request()->is('incidents*') ? 'actif' : '' }}">Incidents</a>
            <a href="/notifications" class="{{ request()->is('notifications*') ? 'actif' : '' }}">Notifications @if (($nbNotifs ?? 0) > 0)<span class="pastille">{{ $nbNotifs }}</span>@endif</a>
            @if ($role === 'admin')
                <a href="/utilisateurs" class="{{ request()->is('utilisateurs*') ? 'actif' : '' }}">Utilisateurs</a>
                <a href="/categories" class="{{ request()->is('categories*') ? 'actif' : '' }}">Catégories</a>
                <a href="/services" class="{{ request()->is('services*') ? 'actif' : '' }}">Services</a>
                <a href="/audit" class="{{ request()->is('audit*') ? 'actif' : '' }}">Journal d’audit</a>
            @endif
        </nav>
        <div class="pied">
            <strong>{{ $u['name'] ?? '' }}</strong>
            <span>{{ $libelles[$role] ?? '' }}</span>
            <form method="POST" action="/deconnexion">
                @csrf
                <button type="submit" class="deconnexion">Se déconnecter</button>
            </form>
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
