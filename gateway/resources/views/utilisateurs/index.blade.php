@extends('layouts.app')
@section('titre', 'Utilisateurs')
@section('sous-titre', 'Comptes, rôles et accès à la plateforme')

@section('actions')
    <a class="bouton" href="/utilisateurs/creer">+ Ajouter un utilisateur</a>
@endsection

@section('contenu')
    @if (session('succes')) <p class="succes">{{ session('succes') }}</p> @endif
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif

    @if ($erreur)
        <p class="alerte">{{ $erreur }}</p>
    @else
        @php
            $roles = ['admin' => ['Administrateur', 'bleu'], 'gestionnaire' => ['Gestionnaire', 'vert'], 'responsable' => ['Responsable', 'gris']];
        @endphp
        <div class="carte defile">
            <table class="tableau tableau-util">
                <thead>
                    <tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Service</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach ($lignes as $u)
                        @php [$lib, $col] = $roles[$u['role']] ?? [$u['role'], 'gris']; @endphp
                        <tr>
                            <td><strong>{{ $u['name'] }}</strong></td>
                            <td>{{ $u['email'] }}</td>
                            <td><span class="badge badge-{{ $col }}">{{ $lib }}</span></td>
                            <td>{{ $u['service_nom'] ?? '—' }}</td>
                            <td>
                                @if ($u['actif'])
                                    <span class="badge badge-vert">Actif</span>
                                @else
                                    <span class="badge badge-gris">Inactif</span>
                                @endif
                            </td>
                            <td><a class="lien" href="/utilisateurs/{{ $u['id'] }}/modifier">Modifier</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="note">{{ count($lignes) }} compte(s)</p>
    @endif
@endsection
