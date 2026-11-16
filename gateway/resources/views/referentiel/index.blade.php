@extends('layouts.app')
@section('titre', $c['titre'])
@section('sous-titre', $c['sous_titre'])

@section('actions')
    <a class="bouton" href="/{{ $c['type'] }}/creer">+ Ajouter</a>
@endsection

@section('contenu')
    @if (session('succes')) <p class="succes">{{ session('succes') }}</p> @endif
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif

    @if ($erreur)
        <p class="alerte">{{ $erreur }}</p>
    @else
        <div class="carte defile">
            <table class="tableau tableau-ref">
                <thead>
                    <tr>
                        @foreach ($c['champs'] as $libelle)
                            <th>{{ $libelle }}</th>
                        @endforeach
                        <th>{{ $c['compte_libelle'] }}</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $l)
                        <tr>
                            @foreach (array_keys($c['champs']) as $cle)
                                <td class="{{ $loop->first ? 'code' : '' }}">{{ $l[$cle] ?? '—' }}</td>
                            @endforeach
                            <td>{{ $l[$c['compte']] ?? 0 }}</td>
                            <td><a class="lien" href="/{{ $c['type'] }}/{{ $l['id'] }}/modifier">Modifier</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($c['champs']) + 2 }}" class="vide">Aucun élément enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="note">{{ count($lignes) }} élément(s). Les éléments ne sont pas supprimés, pour conserver l’historique.</p>
    @endif
@endsection
