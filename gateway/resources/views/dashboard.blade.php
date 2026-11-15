@extends('layouts.app')
@section('titre', 'Tableau de bord')
@section('sous-titre', 'Vue d’ensemble du patrimoine')

@section('contenu')
    @if ($biensIndisponible)
        <p class="alerte">Le service Biens est indisponible : certains chiffres ne peuvent pas être affichés.</p>
    @endif
    @if ($affIndisponible)
        <p class="alerte">Le service Affectations est indisponible : les affectations et les incidents ne peuvent pas être affichés.</p>
    @endif

    <div class="stats">
        <div class="carte stat">
            <p class="etiquette">Total des biens</p>
            <p class="nombre">{{ $stats['total'] ?? '—' }}</p>
        </div>
        <div class="carte stat">
            <p class="etiquette">Biens affectés</p>
            <p class="nombre nombre-bleu">{{ $stats['affectes'] ?? '—' }}</p>
        </div>
        <div class="carte stat">
            <p class="etiquette">Biens en panne</p>
            <p class="nombre nombre-rouge">{{ $stats['en_panne'] ?? '—' }}</p>
        </div>
        <div class="carte stat">
            <p class="etiquette">Incidents ouverts</p>
            <p class="nombre nombre-jaune">{{ $stats['incidents'] ?? '—' }}</p>
        </div>
    </div>

    <div class="panneaux">
        <section class="carte panneau">
            <h2>Biens par catégorie</h2>
            @forelse ($categories as $nom => $nb)
                <div class="ligne-barre">
                    <span class="nom">{{ $nom }}</span>
                    <span class="piste"><span class="barre" style="width: {{ round($nb / $maxCategorie * 100) }}%"></span></span>
                    <span class="valeur-barre">{{ $nb }}</span>
                </div>
            @empty
                <p class="note">Aucun bien enregistré.</p>
            @endforelse
        </section>

        <section class="carte panneau">
            <h2>Derniers mouvements</h2>
            @forelse ($mouvements as $m)
                <div class="mouvement">
                    <span>{{ $m['texte'] }}</span>
                    <span class="date">{{ $m['date'] }}</span>
                </div>
            @empty
                <p class="note">Aucun mouvement enregistré.</p>
            @endforelse
        </section>
    </div>
@endsection
