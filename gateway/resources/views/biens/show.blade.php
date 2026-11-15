@extends('layouts.app')
@section('titre', $bien['designation'])
@section('sous-titre', $bien['code_inventaire'] . ' · ' . ($bien['categorie']['nom'] ?? 'Sans catégorie'))

@section('actions')
    <span class="actions">
        <a class="bouton bouton-clair ecriture" href="/biens/{{ $bien['id'] }}/modifier">Modifier</a>
        <a class="bouton bouton-clair ecriture" href="/affectations/nouvelle?bien={{ $bien['id'] }}">Affecter</a>
        <a class="bouton" href="/incidents/nouveau?bien={{ $bien['id'] }}">Signaler un incident</a>
    </span>
@endsection

@section('contenu')
    @php
        $etats = ['neuf' => ['Neuf', 'vert'], 'bon' => ['Bon', 'vert'], 'en_panne' => ['En panne', 'rouge'], 'reforme' => ['Réformé', 'jaune']];
        [$libEtat, $colEtat] = $etats[$bien['etat']] ?? [$bien['etat'], 'gris'];
        $types = ['panne' => 'Panne', 'perte' => 'Perte', 'degradation' => 'Dégradation'];
        $statuts = ['ouvert' => ['Ouvert', 'rouge'], 'en_cours' => ['En cours', 'jaune'], 'resolu' => ['Résolu', 'vert']];
        $d = fn ($x) => $x ? \Carbon\Carbon::parse($x)->format('d/m/Y') : '—';
        $actuelle = collect($historique)->first(fn ($a) => empty($a['date_fin']));
    @endphp

    <div class="deux-colonnes">
        <section class="carte panneau">
            <h2>Informations</h2>
            <dl class="infos">
                <dt>Code d’inventaire</dt><dd>{{ $bien['code_inventaire'] }}</dd>
                <dt>Catégorie</dt><dd>{{ $bien['categorie']['nom'] ?? '—' }}</dd>
                <dt>État</dt><dd><span class="badge badge-{{ $colEtat }}">{{ $libEtat }}</span></dd>
                <dt>Date d’acquisition</dt><dd>{{ $d($bien['date_acquisition']) }}</dd>
                <dt>Valeur</dt><dd>{{ $bien['valeur'] !== null ? number_format((float) $bien['valeur'], 0, ',', ' ') . ' FCFA' : '—' }}</dd>
                <dt>Description</dt><dd>{{ $bien['description'] ?: '—' }}</dd>
            </dl>
        </section>

        <aside class="carte panneau">
            <h2>Affectation actuelle</h2>
            @if ($actuelle)
                <div>
                    <p class="etiquette">Service</p>
                    <p class="valeur">{{ $actuelle['service']['nom'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="etiquette">Depuis le</p>
                    <p class="valeur">{{ $d($actuelle['date_debut']) }}</p>
                </div>
                <div><span class="badge badge-vert">Active</span></div>
            @else
                <p class="note">Ce bien n’est pas affecté.</p>
            @endif

            @if (count($historique))
                <h2>Historique</h2>
                <ul class="historique">
                    @foreach ($historique as $h)
                        <li>
                            <strong>{{ $h['service']['nom'] ?? '—' }}</strong><br>
                            du {{ $d($h['date_debut']) }} au {{ $h['date_fin'] ? $d($h['date_fin']) : 'aujourd’hui' }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </aside>
    </div>

    <section class="carte panneau espace-haut">
        <h2>Incidents</h2>
        @forelse ($incidents as $i)
            @php [$lib, $col] = $statuts[$i['statut']] ?? [$i['statut'], 'gris']; @endphp
            <div class="recent">
                <div>
                    <strong>{{ $types[$i['type']] ?? $i['type'] }}</strong> · {{ $d($i['date_signalement']) }}<br>
                    <span class="note">{{ \Illuminate\Support\Str::limit($i['description'], 120) }}</span>
                </div>
                <span class="badge badge-{{ $col }}">{{ $lib }}</span>
            </div>
        @empty
            <p class="note">Aucun incident signalé pour ce bien.</p>
        @endforelse
    </section>

    <p class="note"><a class="lien" href="/biens">← Retour à la liste des biens</a></p>
@endsection
