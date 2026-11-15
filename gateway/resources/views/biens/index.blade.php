@extends('layouts.app')
@section('titre', 'Biens')
@section('sous-titre', 'Recensement du patrimoine de l’État')

@section('actions')
    <a class="bouton ecriture" href="/biens/creer">+ Ajouter un bien</a>
@endsection

@section('contenu')
    @if (session('succes')) <p class="succes">{{ session('succes') }}</p> @endif
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif

    @if ($erreur)
        <p class="alerte">{{ $erreur }}</p>
    @else
        @php
            $etats = [
                'neuf'     => ['Neuf', 'vert'],
                'bon'      => ['Bon', 'vert'],
                'en_panne' => ['En panne', 'rouge'],
                'reforme'  => ['Réformé', 'jaune'],
            ];
        @endphp

        <form method="GET" action="/biens" class="filtres">
            <input type="search" name="q" placeholder="Rechercher un bien (code, désignation)…" value="{{ $filtres['q'] ?? '' }}">
            <select name="categorie">
                <option value="">Catégorie</option>
                @foreach ($categories as $c)
                    <option value="{{ $c['id'] }}" @selected(($filtres['categorie'] ?? '') == $c['id'])>{{ $c['nom'] }}</option>
                @endforeach
            </select>
            <select name="etat">
                <option value="">État</option>
                @foreach ($etats as $cle => [$lib, $col])
                    <option value="{{ $cle }}" @selected(($filtres['etat'] ?? '') === $cle)>{{ $lib }}</option>
                @endforeach
            </select>
            <select name="service">
                <option value="">Service</option>
                <option value="aucun" @selected(($filtres['service'] ?? '') === 'aucun')>Non affecté</option>
                @foreach ($services as $s)
                    <option value="{{ $s['nom'] }}" @selected(($filtres['service'] ?? '') === $s['nom'])>{{ $s['nom'] }}</option>
                @endforeach
            </select>
            <button class="bouton" type="submit">Filtrer</button>
            <a class="lien" href="/biens">Réinitialiser</a>
        </form>

        <div class="carte">
            <table class="tableau">
                <thead>
                    <tr><th>Code</th><th>Désignation</th><th>Catégorie</th><th>État</th><th>Service</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($biens as $bien)
                        @php [$libelle, $couleur] = $etats[$bien['etat']] ?? [$bien['etat'], 'gris']; @endphp
                        <tr>
                            <td class="code">{{ $bien['code_inventaire'] }}</td>
                            <td>{{ $bien['designation'] }}</td>
                            <td>{{ $bien['categorie']['nom'] ?? '-' }}</td>
                            <td><span class="badge badge-{{ $couleur }}">{{ $libelle }}</span></td>
                            <td>{{ $bien['service'] ?? 'Non affecté' }}</td>
                            <td>
                            <a class="lien ecriture" href="/biens/{{ $bien['id'] }}/modifier">Modifier</a>
                            <a class="lien ecriture" href="/affectations/nouvelle?bien={{ $bien['id'] }}">Affecter</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="vide">Aucun bien ne correspond.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="note">{{ count($biens) }} bien(s) affiché(s) sur {{ $total }}</p>
    @endif
@endsection
