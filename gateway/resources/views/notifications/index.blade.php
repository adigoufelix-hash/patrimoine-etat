@extends('layouts.app')
@section('titre', 'Notifications')
@section('sous-titre', 'Les messages qui vous sont destinés')

@section('actions')
    <form method="POST" action="/notifications/lues">
        @csrf
        <button class="bouton bouton-clair" type="submit">Tout marquer comme lu</button>
    </form>
@endsection

@section('contenu')
    @if (session('succes')) <p class="succes">{{ session('succes') }}</p> @endif
    @if (session('erreur')) <p class="alerte">{{ session('erreur') }}</p> @endif

    @if ($erreur)
        <p class="alerte">{{ $erreur }}</p>
    @else
        <div class="carte">
            @forelse ($lignes as $n)
                <form method="POST" action="/notifications/{{ $n['id'] }}/lue" class="notification {{ $n['lue'] ? '' : 'non-lue' }}">
                    @csrf
                    <input type="hidden" name="lien" value="{{ $n['lien'] ?? '' }}">
                    <button type="submit">
                        <span class="titre">
                            @if (! $n['lue'])<span class="point"></span>@endif
                            {{ $n['titre'] }}
                        </span>
                        <span class="texte">{{ $n['message'] }}</span>
                        <span class="note">{{ \Carbon\Carbon::parse($n['created_at'])->format('d/m/Y H:i') }}</span>
                    </button>
                </form>
            @empty
                <p class="vide">Aucune notification.</p>
            @endforelse
        </div>
    @endif
@endsection
