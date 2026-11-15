@extends('layouts.app')
@section('titre', $bien ? 'Modifier le bien' : 'Ajouter un bien')
@section('sous-titre', $bien ? $bien['code_inventaire'] . ' · ' . $bien['designation'] : 'Enregistrer un nouveau bien dans le patrimoine')

@section('contenu')
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif
    @if ($errors->any())
        <div class="alerte">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $etats = ['neuf' => 'Neuf', 'bon' => 'Bon', 'en_panne' => 'En panne', 'reforme' => 'Réformé'];
        $valeur = fn ($champ, $defaut = '') => old($champ, $bien[$champ] ?? $defaut);
    @endphp

    <form method="POST" action="{{ $bien ? '/biens/' . $bien['id'] : '/biens' }}" class="carte formulaire">
        @csrf
        @if ($bien) @method('PUT') @endif

        <div class="champ">
            <label for="code_inventaire">Code d’inventaire</label>
            <input id="code_inventaire" name="code_inventaire" value="{{ $valeur('code_inventaire') }}" placeholder="INV-0003" {{ $bien ? 'readonly' : 'required' }}>
        </div>
        <div class="champ">
            <label for="designation">Désignation</label>
            <input id="designation" name="designation" value="{{ $valeur('designation') }}" required>
        </div>
        <div class="champ">
            <label for="categorie_id">Catégorie</label>
            <select id="categorie_id" name="categorie_id" required>
                <option value="">Choisir…</option>
                @foreach ($categories as $c)
                    <option value="{{ $c['id'] }}" @selected($valeur('categorie_id') == $c['id'])>{{ $c['nom'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label for="etat">État</label>
            <select id="etat" name="etat">
                @foreach ($etats as $cle => $lib)
                    <option value="{{ $cle }}" @selected($valeur('etat', 'bon') === $cle)>{{ $lib }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label for="date_acquisition">Date d’acquisition</label>
            <input id="date_acquisition" type="date" name="date_acquisition" value="{{ substr((string) $valeur('date_acquisition'), 0, 10) }}">
        </div>
        <div class="champ">
            <label for="valeur">Valeur (FCFA)</label>
            <input id="valeur" type="number" step="0.01" min="0" name="valeur" value="{{ $valeur('valeur') }}">
        </div>
        <div class="champ">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3">{{ $valeur('description') }}</textarea>
        </div>

        <div class="boutons">
            <a class="bouton bouton-clair" href="/biens">Annuler</a>
            <button class="bouton" type="submit">{{ $bien ? 'Enregistrer les modifications' : 'Ajouter le bien' }}</button>
        </div>
    </form>
@endsection
