@extends('layouts.app')
@section('titre', 'Affectations')
@section('sous-titre', 'Historique des affectations de biens aux services')

@section('actions')
    <a class="bouton ecriture" href="/affectations/nouvelle">+ Nouvelle affectation</a>
@endsection

@section('contenu')
    @if (session('succes')) <p class="succes">{{ session('succes') }}</p> @endif
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif

    @if ($erreur)
        <p class="alerte">{{ $erreur }}</p>
    @else
        @php $d = fn ($x) => $x ? \Carbon\Carbon::parse($x)->format('d/m/Y') : '—'; @endphp
        <div class="carte defile">
            <table class="tableau tableau-aff">
                <thead>
                    <tr><th>Bien</th><th>Service</th><th>Début</th><th>Fin</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $a)
                        <tr>
                            <td class="code">{{ $a['bien_libelle'] }}</td>
                            <td>{{ $a['service']['nom'] ?? '—' }}</td>
                            <td>{{ $d($a['date_debut']) }}</td>
                            <td>{{ $d($a['date_fin']) }}</td>
                            <td>
                                @if (empty($a['date_fin']))
                                    <span class="badge badge-vert">Active</span>
                                @else
                                    <span class="badge badge-gris">Terminée</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="vide">Aucune affectation enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="note">{{ count($lignes) }} affectation(s)</p>
    @endif
@endsection
