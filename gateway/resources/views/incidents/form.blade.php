@extends('layouts.app')
@section('titre', 'Signaler un incident')
@section('sous-titre', 'Déclarez une panne, une perte ou une dégradation')

@section('contenu')
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif
    @if ($errors->any())
        <div class="alerte">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $types = ['panne' => 'Panne', 'perte' => 'Perte', 'degradation' => 'Dégradation'];
        $statuts = ['ouvert' => ['Ouvert', 'rouge'], 'en_cours' => ['En cours', 'jaune'], 'resolu' => ['Résolu', 'vert']];
        $d = fn ($x) => $x ? \Carbon\Carbon::parse($x)->format('d/m') : '—';
        $bienChoisi = old('bien_id', $choisi ?: '');
    @endphp

    <div class="deux-colonnes">
        <form method="POST" action="/incidents" class="carte formulaire">
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
                <label for="type">Type d’incident</label>
                <select id="type" name="type" required>
                    @foreach ($types as $cle => $lib)
                        <option value="{{ $cle }}" @selected(old('type', 'panne') === $cle)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="champ">
                <label for="date_constat">Date du constat</label>
                <input id="date_constat" type="date" name="date_constat" max="{{ now()->toDateString() }}" value="{{ old('date_constat', now()->toDateString()) }}">
            </div>
            <div class="champ">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" maxlength="2000" required placeholder="Décrivez le problème rencontré…">{{ old('description') }}</textarea>
            </div>

            <div class="boutons">
                <a class="bouton bouton-clair" href="/incidents">Annuler</a>
                <button class="bouton" type="submit">Envoyer le signalement</button>
            </div>
        </form>

        <aside class="carte panneau">
            <h2>Signalements récents</h2>
            @forelse ($recents as $i)
                @php [$lib, $col] = $statuts[$i['statut']] ?? [$i['statut'], 'gris']; @endphp
                <div class="recent">
                    <div>
                        <strong>{{ $i['bien_libelle'] }}</strong><br>
                        <span class="note">{{ $types[$i['type']] ?? $i['type'] }} · {{ $d($i['date_signalement']) }}</span>
                    </div>
                    <span class="badge badge-{{ $col }}">{{ $lib }}</span>
                </div>
            @empty
                <p class="note">Aucun signalement pour le moment.</p>
            @endforelse
        </aside>
    </div>
@endsection
