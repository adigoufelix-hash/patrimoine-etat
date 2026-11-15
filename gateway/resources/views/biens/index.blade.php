@extends('layouts.app')
@section('titre', 'Biens')
@section('sous-titre', 'Recensement du patrimoine de l’État')

@section('contenu')
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
        <div class="carte">
            <table class="tableau">
                <thead>
                    <tr><th>Code</th><th>Désignation</th><th>Catégorie</th><th>État</th></tr>
                </thead>
                <tbody>
                    @forelse ($biens as $bien)
                        @php [$libelle, $couleur] = $etats[$bien['etat']] ?? [$bien['etat'], 'gris']; @endphp
                        <tr>
                            <td class="code">{{ $bien['code_inventaire'] }}</td>
                            <td>{{ $bien['designation'] }}</td>
                            <td>{{ $bien['categorie']['nom'] ?? '-' }}</td>
                            <td><span class="badge badge-{{ $couleur }}">{{ $libelle }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="vide">Aucun bien enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="note">{{ count($biens) }} bien(s) enregistré(s)</p>
    @endif
@endsection
