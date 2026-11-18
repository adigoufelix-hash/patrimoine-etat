@extends('layouts.app')
@section('titre', 'Journal d’audit')
@section('sous-titre', 'Qui a fait quoi, et quand. Le journal est en lecture seule.')

@section('contenu')
    @if ($erreur)
        <p class="alerte">{{ $erreur }}</p>
    @else
        @php
            $roles = ['admin' => ['Administrateur', 'bleu'], 'gestionnaire' => ['Gestionnaire', 'vert'], 'responsable' => ['Responsable', 'gris']];
            $nettoyes = array_filter($filtres, fn ($v) => $v !== null && $v !== '');
            $lien = fn ($p) => '/audit?' . http_build_query($nettoyes + ['page' => $p]);
        @endphp

        <form method="GET" action="/audit" class="filtres">
            <input type="search" name="utilisateur" placeholder="Utilisateur" value="{{ $filtres['utilisateur'] ?? '' }}">
            <input type="search" name="action" placeholder="Action" value="{{ $filtres['action'] ?? '' }}">
            <select name="role">
                <option value="">Rôle</option>
                @foreach ($roles as $cle => [$lib, $col])
                    <option value="{{ $cle }}" @selected(($filtres['role'] ?? '') === $cle)>{{ $lib }}</option>
                @endforeach
            </select>
            <input type="date" name="du" value="{{ $filtres['du'] ?? '' }}" title="Du">
            <input type="date" name="au" value="{{ $filtres['au'] ?? '' }}" title="Au">
            <button class="bouton" type="submit">Filtrer</button>
            <a class="lien" href="/audit">Réinitialiser</a>
        </form>

        <div class="carte defile">
            <table class="tableau tableau-aud">
                <thead>
                    <tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Objet</th><th>Détail</th><th>Adresse IP</th></tr>
                </thead>
                <tbody>
                    @forelse ($evenements as $e)
                        @php [$lib, $col] = $roles[$e['role'] ?? ''] ?? [null, 'gris']; @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($e['created_at'])->format('d/m/Y H:i') }}</td>
                            <td>
                                {{ $e['utilisateur_nom'] ?? 'Anonyme' }}
                                @if ($lib)<br><span class="badge badge-{{ $col }}">{{ $lib }}</span>@endif
                            </td>
                            <td><strong>{{ $e['action'] }}</strong></td>
                            <td>{{ $e['objet'] ?? '—' }}</td>
                            <td>
                                @forelse ($e['detail'] ?? [] as $k => $v)
                                    <span class="note">{{ $k }} : {{ is_scalar($v) ? $v : json_encode($v) }}</span><br>
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td>{{ $e['adresse_ip'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="vide">Aucun événement ne correspond.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-simple">
            <span class="note">{{ $meta['total'] }} événement(s) · page {{ $meta['page'] }} sur {{ $meta['dernier'] }}</span>
            <span>
                @if ($meta['page'] > 1)<a class="lien" href="{{ $lien($meta['page'] - 1) }}">← Précédent</a>@endif
                @if ($meta['page'] < $meta['dernier'])<a class="lien" href="{{ $lien($meta['page'] + 1) }}">Suivant →</a>@endif
            </span>
        </div>
    @endif
@endsection
