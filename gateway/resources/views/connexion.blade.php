<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - PatriGest</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth">
    <aside class="auth-visuel">
        <div class="bande"></div>
        <div class="interieur">
            @if (file_exists(public_path('images/armoiries.png')))
                <img class="armoiries" src="{{ asset('images/armoiries.png') }}" alt="Armoiries de la République Togolaise">
            @endif
            <img class="logo" src="{{ asset('images/logo_icone_clair.svg') }}" alt="Logo PatriGest" width="84" height="84">
            <h1>PatriGest</h1>
            <p class="slogan">Plateforme de gestion du patrimoine de l’État</p>
            <ul>
                <li>Recenser les biens publics</li>
                <li>Les affecter aux services</li>
                <li>Suivre leur état et leurs incidents</li>
            </ul>
        </div>
        <div class="pied-auth">
            <img src="{{ asset('images/flag_togo.svg') }}" alt="Drapeau du Togo">
            <div>
                <strong>République Togolaise</strong>
                <span>Travail · Liberté · Patrie</span>
            </div>
        </div>
    </aside>

    <main class="auth-formulaire">
        <form method="POST" action="/connexion" class="carte-connexion">
            @csrf
            <h2>Connexion</h2>
            <p class="note">Accédez à la plateforme avec votre compte.</p>

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
    </main>
</div>
</body>
</html>
