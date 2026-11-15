@extends('layouts.app')
@section('titre', 'Incidents')
@section('sous-titre', 'Pannes, pertes et dégradations signalées')

@section('actions')
    <a class="bouton" href="/incidents/nouveau">+ Signaler un incident</a>
@endsection

@section('contenu')
    @if (session('succes')) <p class="succes">{{ session('succes') }}</p> @endif
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif

    @if ($erreur)
        <p class="alerte">{{ $erreur }}</p>
    @else
        @php
            $types = ['panne' => 'Panne', 'perte' => 'Perte', 'degradation' => 'Dégradation'];
            $statuts = ['ouvert' => ['Ouvert', 'rouge'], 'en_cours' => ['En cours', 'jaune'], 'resolu' => ['Résolu', 'vert']];
            $peutGerer = in_array(session('user.role'), ['admin', 'gestionnaire'], true);
            $d = fn ($x) => $x ? \Carbon\Carbon::parse($x)->format('d/m/Y') : '—';
        @endphp

        <div class="carte defile">
            <table class="tableau tableau-inc">
                <thead>
                    <tr>
                        <th>Bien</th><th>Type</th><th>Description</th><th>Date</th><th>Statut</th>
                        @if ($peutGerer)<th>Traitement</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $i)
                        @php [$lib, $col] = $statuts[$i['statut']] ?? [$i['statut'], 'gris']; @endphp
                        <tr>
                            <td class="code">{{ $i['bien_libelle'] }}</td>
                            <td>{{ $types[$i['type']] ?? $i['type'] }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($i['description'], 70) }}</td>
                            <td>{{ $d($i['date_signalement']) }}</td>
                            <td><span class="badge badge-{{ $col }}">{{ $lib }}</span></td>
                            @if ($peutGerer)
                                <td>
                                    <form method="POST" action="/incidents/{{ $i['id'] }}/statut" class="statut-form">
                                        @csrf
                                        @method('PATCH')
                                        <select name="statut" onchange="this.form.submit()">
                                            @foreach ($statuts as $cle => [$libelle, $c])
                                                <option value="{{ $cle }}" @selected($i['statut'] === $cle)>{{ $libelle }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="vide">Aucun incident signalé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="note">{{ count($lignes) }} incident(s)</p>
    @endif
@endsection
