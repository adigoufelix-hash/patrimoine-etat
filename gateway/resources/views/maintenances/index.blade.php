@extends('layouts.app')
@section('titre', 'Maintenances')
@section('sous-titre', 'Entretiens, réparations et contrôles des biens')

@section('actions')
    <a class="bouton" href="/maintenances/nouvelle">+ Planifier une maintenance</a>
@endsection

@section('contenu')
    @if (session('succes')) <p class="succes">{{ session('succes') }}</p> @endif
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif

    @if ($erreur)
        <p class="alerte">{{ $erreur }}</p>
    @else
        @php
            $statuts = ['planifiee' => 'Planifiée', 'en_retard' => 'En retard', 'realisee' => 'Réalisée', 'annulee' => 'Annulée'];
            $d = fn ($x) => $x ? \Carbon\Carbon::parse($x)->format('d/m/Y') : '—';
        @endphp

        <form method="GET" action="/maintenances" class="filtres">
            <select name="statut" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach ($statuts as $cle => $lib)
                    <option value="{{ $cle }}" @selected(($statut ?? '') === $cle)>{{ $lib }}</option>
                @endforeach
            </select>
            <a class="lien" href="/maintenances">Réinitialiser</a>
        </form>

        <div class="carte defile">
            <table class="tableau tableau-mnt">
                <thead>
                    <tr><th>Bien</th><th>Type</th><th>Date prévue</th><th>Statut</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $m)
                        <tr>
                            <td class="code">{{ $m['bien_libelle'] }}</td>
                            <td>{{ $types[$m['type']] ?? $m['type'] }}</td>
                            <td>{{ $d($m['date_prevue']) }}</td>
                            <td>
                                @if ($m['statut'] === 'planifiee' && $m['en_retard'])
                                    <span class="badge badge-rouge">En retard</span>
                                @elseif ($m['statut'] === 'planifiee')
                                    <span class="badge badge-jaune">Planifiée</span>
                                @elseif ($m['statut'] === 'realisee')
                                    <span class="badge badge-vert">Réalisée</span>
                                    <br><span class="note">le {{ $d($m['date_realisee']) }}</span>
                                @else
                                    <span class="badge badge-gris">Annulée</span>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($m['description'] ?? '—', 70) }}</td>
                            <td>
                                @if ($m['statut'] === 'planifiee')
                                    <form method="POST" action="/maintenances/{{ $m['id'] }}/statut" class="en-ligne">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="statut" value="realisee">
                                        <button class="lien-bouton" type="submit">Réalisée</button>
                                    </form>
                                    <form method="POST" action="/maintenances/{{ $m['id'] }}/statut" class="en-ligne" onsubmit="return confirm('Annuler cette maintenance ?')">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="statut" value="annulee">
                                        <button class="lien-bouton" type="submit">Annuler</button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="vide">Aucune maintenance ne correspond.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="note">{{ count($lignes) }} maintenance(s). Une maintenance clôturée ne peut plus être modifiée.</p>
    @endif
@endsection
