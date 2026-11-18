@extends('layouts.app')
@section('titre', 'Planifier une maintenance')
@section('sous-titre', 'Les biens réformés ne peuvent pas faire l’objet d’une maintenance')

@section('contenu')
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif
    @if ($errors->any())
        <div class="alerte">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @php $bienChoisi = old('bien_id', $choisi ?: ''); @endphp

    <form method="POST" action="/maintenances" class="carte formulaire">
        @csrf

        <div class="champ">
            <label for="bien_id">Bien concerné</label>
            <select id="bien_id" name="bien_id" required>
                <option value="">Choisir un bien…</option>
                @foreach ($biens as $b)
                    <option value="{{ $b['id'] }}" @selected($bienChoisi == $b['id'])>{{ $b['code_inventaire'] }} · {{ $b['designation'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label for="type">Type de maintenance</label>
            <select id="type" name="type" required>
                @foreach ($types as $cle => $lib)
                    <option value="{{ $cle }}" @selected(old('type', 'entretien') === $cle)>{{ $lib }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label for="date_prevue">Date prévue</label>
            <input id="date_prevue" type="date" name="date_prevue" min="{{ now()->toDateString() }}" value="{{ old('date_prevue') }}" required>
        </div>
        <div class="champ">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" maxlength="1000" placeholder="Révision annuelle, remplacement d’une pièce…">{{ old('description') }}</textarea>
        </div>

        <div class="boutons">
            <a class="bouton bouton-clair" href="/maintenances">Annuler</a>
            <button class="bouton" type="submit">Planifier</button>
        </div>
    </form>
@endsection
