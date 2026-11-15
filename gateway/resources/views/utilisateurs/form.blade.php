@extends('layouts.app')
@section('titre', $utilisateur ? 'Modifier l’utilisateur' : 'Ajouter un utilisateur')
@section('sous-titre', $utilisateur ? $utilisateur['name'] . ' · ' . $utilisateur['email'] : 'Créer un compte avec le rôle de votre choix')

@section('contenu')
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif
    @if ($errors->any())
        <div class="alerte">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $roles = ['admin' => 'Administrateur', 'gestionnaire' => 'Gestionnaire du patrimoine', 'responsable' => 'Responsable de service'];
        $v = fn ($champ, $defaut = '') => old($champ, $utilisateur[$champ] ?? $defaut);
        $actif = old('actif', $utilisateur['actif'] ?? true);
    @endphp

    <form method="POST" action="{{ $utilisateur ? '/utilisateurs/' . $utilisateur['id'] : '/utilisateurs' }}" class="carte formulaire">
        @csrf
        @if ($utilisateur) @method('PATCH') @endif

        @if (! $utilisateur)
            <div class="champ">
                <label for="name">Nom complet</label>
                <input id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="champ">
                <label for="email">Adresse e-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="champ">
                <label for="password">Mot de passe provisoire (8 caractères minimum)</label>
                <input id="password" type="password" name="password" minlength="8" required>
            </div>
        @endif

        <div class="champ">
            <label for="role">Rôle</label>
            <select id="role" name="role" required>
                @foreach ($roles as $cle => $lib)
                    <option value="{{ $cle }}" @selected($v('role', 'responsable') === $cle)>{{ $lib }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label for="service_id">Service</label>
            <select id="service_id" name="service_id">
                <option value="">Aucun service</option>
                @foreach ($services as $s)
                    <option value="{{ $s['id'] }}" @selected($v('service_id') == $s['id'])>{{ $s['nom'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label class="case">
                <input type="hidden" name="actif" value="0">
                <input type="checkbox" name="actif" value="1" @checked($actif)>
                Compte actif (un compte inactif ne peut plus se connecter)
            </label>
        </div>

        <div class="boutons">
            <a class="bouton bouton-clair" href="/utilisateurs">Annuler</a>
            <button class="bouton" type="submit">{{ $utilisateur ? 'Enregistrer' : 'Créer le compte' }}</button>
        </div>
    </form>
@endsection
