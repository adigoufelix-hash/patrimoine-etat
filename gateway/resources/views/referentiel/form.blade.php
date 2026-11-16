@extends('layouts.app')
@section('titre', ($ligne ? 'Modifier ' : 'Ajouter ') . $c['indefini'])
@section('sous-titre', $ligne ? $ligne['nom'] : $c['sous_titre'])

@section('contenu')
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif
    @if ($errors->any())
        <div class="alerte">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $ligne ? '/' . $c['type'] . '/' . $ligne['id'] : '/' . $c['type'] }}" class="carte formulaire">
        @csrf
        @if ($ligne) @method('PUT') @endif

        @foreach ($c['champs'] as $cle => $libelle)
            <div class="champ">
                <label for="{{ $cle }}">{{ $libelle }}</label>
                @if ($cle === 'description')
                    <textarea id="{{ $cle }}" name="{{ $cle }}" rows="3" maxlength="500">{{ old($cle, $ligne[$cle] ?? '') }}</textarea>
                @else
                    <input id="{{ $cle }}" name="{{ $cle }}" value="{{ old($cle, $ligne[$cle] ?? '') }}" {{ $cle === 'nom' ? 'required' : '' }}>
                @endif
            </div>
        @endforeach

        <div class="boutons">
            <a class="bouton bouton-clair" href="/{{ $c['type'] }}">Annuler</a>
            <button class="bouton" type="submit">{{ $ligne ? 'Enregistrer' : 'Ajouter' }}</button>
        </div>
    </form>
@endsection
