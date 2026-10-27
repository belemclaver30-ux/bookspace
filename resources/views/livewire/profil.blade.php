@php
    $client = $user->estClient();
    $libelleRole = ['client' => 'Compte Client', 'gerant' => 'Compte Gérant', 'admin' => 'Administrateur'][$user->role];
    $groupes = [
        'Mon compte' => array_values(array_filter([
            $user->estAdmin() ? null : ['message', 'Messages', '/messages', app(\App\Services\MessagerieService::class)->nonLus($user)],
            ['cloche', 'Notifications', '/notifications', $user->notifications()->whereNull('lu_at')->count()],
            ['utilisateur', 'Informations personnelles', '/profil/compte'],
            ['cle', 'Mot de passe et sécurité', '/profil/securite'],
            $client ? ['coeur', 'Mes favoris', '/profil/favoris'] : null,
            $client ? ['calendrier', 'Mes abonnements', '/abonnements'] : null,
            $user->estGerant() ? ['revenus', 'Mes revenus', '/profil/revenus'] : null,
        ])),
        'Préférences' => [
            ['cloche', 'Notifications et rappels', '/profil/preferences'],
            ['langue', 'Langue : Français', '/profil/preferences'],
        ],
        'Aide & Support' => array_values(array_filter([
            ['aide', "Centre d'aide", '/profil/aide'],
            $user->estAdmin() ? null : ['drapeau', 'Signaler un problème', '/profil/aide#signalement'],
            ['document', "Conditions d'utilisation", '/conditions'],
            ['bouclier', 'Politique de confidentialité', '/confidentialite'],
        ])),
    ];
@endphp
<div class="flex flex-1 flex-col">
    <div @class(['bg-brand-dark px-5 pb-7 pt-6 text-center', 'mb-5 rounded-2xl' => ! $client])>
        <a href="/profil/compte" class="mx-auto mb-3 block w-fit" aria-label="Modifier ma photo et mes informations">
            <x-avatar :user="$user" taille="72" class="ring-4 ring-white/15" />
        </a>
        <h1 class="font-display text-lg font-bold text-white">{{ $user->name }}</h1>
        <div class="mt-0.5 text-[13px] text-white/50">{{ $user->telephone }}</div>
        <div @class(['mt-2 inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold', 'bg-emerald-500/25 text-emerald-300' => $client, 'bg-brand/40 text-gold' => ! $client])>
            {{ $libelleRole }}
        </div>
        @if ($activite)
            <div class="mx-auto mt-4 grid max-w-[320px] grid-cols-3 gap-2">
                @foreach ($activite as [$valeur, $libelle])
                    <div class="rounded-xl bg-white/10 px-2 py-2">
                        <div class="text-lg font-extrabold leading-tight text-white">{{ $valeur }}</div>
                        <div class="text-[11px] text-white/55">{{ $libelle }}</div>
                    </div>
                @endforeach
            </div>
        @endif
        @if ($user->gerant && ! $user->gerant->estValide())
            <div class="mt-3 text-xs font-semibold text-amber-300">
                Compte gérant : {{ $user->gerant->statut_validation === 'en_attente' ? 'en attente de validation' : 'rejeté' }}
                @if ($user->gerant->motif_rejet) — {{ $user->gerant->motif_rejet }} @endif
            </div>
        @endif
    </div>

    <div @class(['w-full max-w-[460px]', 'px-5 pb-6 pt-4' => $client])>
        @foreach ($groupes as $titre => $elements)
            <div class="mb-5">
                <div class="mb-2 pl-1 text-[11px] font-bold uppercase tracking-[0.5px] text-slate-400">{{ $titre }}</div>
                <div class="overflow-hidden rounded-2xl bg-white shadow-carte">
                    @foreach ($elements as $element)
                        @php [$icone, $libelle, $lien] = $element; $pastille = $element[3] ?? 0; @endphp
                        <{{ $lien ? 'a' : 'div' }} @if ($lien) href="{{ $lien }}" @endif class="flex items-center gap-3 border-b border-slate-50 px-4 py-3.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500"><x-icone :nom="$icone" /></span>
                            <span class="flex-1 text-sm font-medium text-ink">{{ $libelle }}</span>
                            <x-pastille :nombre="$pastille" />
                            @if ($lien) <x-icone nom="chevron" taille="16" class="text-slate-400" /> @endif
                        </{{ $lien ? 'a' : 'div' }}>
                    @endforeach
                </div>
            </div>
        @endforeach

        <form method="POST" action="/deconnexion" class="mt-2">
            @csrf
            <button type="submit" class="btn-contour">Se déconnecter</button>
        </form>
    </div>
</div>
