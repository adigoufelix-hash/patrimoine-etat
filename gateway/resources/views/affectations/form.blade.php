@extends('layouts.app')
@section('titre', 'Affecter un bien')
@section('sous-titre', 'L’opération est enregistrée dans l’historique des mouvements')

@section('contenu')
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif
    @if ($errors->any())
        <div class="alerte">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $d = fn ($x) => $x ? \Carbon\Carbon::parse($x)->format('d/m/Y') : '—';
        $etats = [
            'neuf'     => ['Neuf', 'vert'],
            'bon'      => ['Bon', 'vert'],
            'en_panne' => ['En panne', 'rouge'],
            'reforme'  => ['Réformé', 'jaune'],
        ];
    @endphp

    <div class="deux-colonnes">
        <form method="POST" action="/affectations" class="carte formulaire">
            @csrf

            <div class="champ">
                <label for="bien_id">Bien à affecter</label>
                <select id="bien_id" name="bien_id" required
                        onchange="window.location='/affectations/nouvelle?bien='+this.value">
                    <option value="">Choisir un bien…</option>
                    @foreach ($biens as $b)
                        <option value="{{ $b['id'] }}"
                                @selected(($choisi['id'] ?? null) == $b['id'])
                                @disabled($b['etat'] === 'reforme')>
                            {{ $b['code_inventaire'] }} · {{ $b['designation'] }}{{ $b['etat'] === 'reforme' ? ' (réformé)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($choisi)
                @php [$lib, $col] = $etats[$choisi['etat']] ?? [$choisi['etat'], 'gris']; @endphp
                <div class="champ">
                    <label>État du bien</label>
                    <div><span class="badge badge-{{ $col }}">{{ $lib }}</span></div>
                </div>
            @endif

            <div class="champ">
                <label for="service_id">Service destinataire</label>
                <select id="service_id" name="service_id" required>
                    <option value="">Choisir un service…</option>
                    @foreach ($services as $s)
                        <option value="{{ $s['id'] }}" @selected(old('service_id') == $s['id'])>{{ $s['nom'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="champ">
                <label for="motif">Motif</label>
                <input id="motif" name="motif" maxlength="255" value="{{ old('motif') }}" placeholder="Renouvellement du poste de travail">
            </div>

            <div class="champ">
                <label>Date d’affectation</label>
                <input value="{{ now()->format('d/m/Y') }}" readonly>
            </div>

            <div class="boutons">
                <a class="bouton bouton-clair" href="/affectations">Annuler</a>
                <button class="bouton" type="submit">Confirmer l’affectation</button>
            </div>
        </form>

        <aside class="carte panneau">
            <h2>Affectation actuelle</h2>
            @if (! $choisi)
                <p class="note">Choisis un bien pour voir son affectation actuelle.</p>
            @elseif ($actuelle)
                <div>
                    <p class="etiquette">Service</p>
                    <p class="valeur">{{ $actuelle['service']['nom'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="etiquette">Depuis le</p>
                    <p class="valeur">{{ $d($actuelle['date_debut']) }}</p>
                </div>
                <div><span class="badge badge-vert">Active</span></div>
                <p class="avertissement">En confirmant, l’affectation actuelle sera clôturée (date de fin) et une nouvelle sera créée. L’historique est conservé.</p>
            @else
                <p class="note">Ce bien n’est pas encore affecté.</p>
            @endif

            @if (count($historique))
                <h2>Historique du bien</h2>
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
@endsection
