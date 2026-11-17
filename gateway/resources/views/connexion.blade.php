<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - PatriGest</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
</head>
<body>
<div class="page-connexion">
    <form method="POST" action="/connexion" class="carte-connexion">
        @csrf
        <img src="{{ asset('images/logo_patrigest.svg') }}" alt="PatriGest - Gestion du patrimoine de l’État" class="logo-connexion">
        <p class="note">Connectez-vous pour accéder à la plateforme</p>

        @if ($errors->any())
            <div class="alerte">{{ $errors->first() }}</div>
        @endif

        <div class="champ">
            <label for="email">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="prenom.nom@gouv.tg" required autofocus>
        </div>
        <div class="champ">
            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" required>
        </div>
        <button class="bouton" type="submit">Se connecter</button>
        <p class="note centre">Pas encore de compte ? <a class="lien" href="/inscription">Créer un compte</a></p>
    </form>
</div>
</body>
</html>
