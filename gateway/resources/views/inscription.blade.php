<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer un compte - Patrimoine de l’État</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="page-connexion">
    <form method="POST" action="/inscription" class="carte-connexion">
        @csrf
        <h1>Créer un compte</h1>
        <p class="note">Les nouveaux comptes ont le rôle « responsable de service » : ils consultent les biens de leur service.</p>

        @if ($errors->any())
            <div class="alerte">
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="champ">
            <label for="name">Nom complet</label>
            <input id="name" name="name" value="{{ old('name') }}" required autofocus>
        </div>
        <div class="champ">
            <label for="email">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="prenom.nom@gouv.tg" required>
        </div>
        <div class="champ">
            <label for="service_id">Mon service</label>
            <select id="service_id" name="service_id">
                <option value="">Choisir mon service…</option>
                @foreach ($services as $s)
                    <option value="{{ $s['id'] }}" @selected(old('service_id') == $s['id'])>{{ $s['nom'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label for="password">Mot de passe (8 caractères minimum)</label>
            <input id="password" type="password" name="password" minlength="8" required>
        </div>
        <div class="champ">
            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required>
        </div>
        <button class="bouton" type="submit">Créer mon compte</button>
        <p class="note centre">Déjà un compte ? <a class="lien" href="/connexion">Se connecter</a></p>
    </form>
</div>
</body>
</html>
