@props(['title' => null])
{{--
    Coque de back-office avec barre latérale fixe (bureau) / tiroir (mobile).
    Partagée par les espaces gérant et administrateur : le menu dépend du rôle.
--}}
@php
    $user = auth()->user();
    $admin = $user->estAdmin();

    if ($admin) {
        $titreEspace = 'Administration';
        $sousTitre = 'Administrateur';
        $liens = [
            ['/admin', 'tableau', 'Tableau de bord', request()->is('admin'), 0],
            ['/admin/validations', 'valider', 'Validations', request()->is('admin/validations'), 0],
            ['/admin/gerants', 'revenus', 'Gérants', request()->is('admin/gerants'), 0],
            ['/admin/litiges', 'alerte', 'Litiges', request()->is('admin/litiges'), 0],
            ['/admin/parametres', 'curseurs', 'Paramètres', request()->is('admin/parametres'), 0],
            ['/profil', 'utilisateur', 'Profil', request()->is('profil*'), 0],
        ];
    } else {
        $gerant = $user->gerant;
        $titreEspace = 'Espace gérant';
        $sousTitre = $gerant?->raison_sociale ?? $user->telephone;
        $demandesEnAttente = $gerant
            ? \App\Models\DemandeModification::where('statut', 'en_attente')
                ->whereHas('reservation', fn ($q) => $q->where('gerant_id', $gerant->id))->count()
            : 0;
        $liens = [
            ['/gerant', 'tableau', 'Tableau de bord', request()->is('gerant'), 0],
            ['/gerant/reservations', 'calendrier', 'Réservations', request()->is('gerant/reservations'), 0],
            ['/gerant/demandes', 'alerte', 'Demandes', request()->is('gerant/demandes'), $demandesEnAttente],
            ['/gerant/espaces', 'batiment', 'Mes espaces', request()->is('gerant/espaces*'), 0],
            ['/messages', 'message', 'Messages', request()->is('messages*'), app(\App\Services\MessagerieService::class)->nonLus($user)],
            ['/profil', 'utilisateur', 'Profil', request()->is('profil*'), 0],
        ];
    }

    $titrePage = collect($liens)->first(fn ($l) => $l[3])[2] ?? $titreEspace;
@endphp
<x-layouts.base :title="$title ?? $titrePage">
    <div class="flex min-h-dvh bg-[#EEF2F7]" x-data="{ ouvert: false }" @keydown.escape.window="ouvert = false">
        {{-- Voile du tiroir mobile --}}
        <div x-cloak x-show="ouvert" x-transition.opacity @click="ouvert = false"
             class="fixed inset-0 z-40 bg-black/45 min-[901px]:hidden"></div>

        <aside class="fixed left-0 top-0 z-50 flex h-dvh w-[248px] shrink-0 -translate-x-full flex-col bg-brand-dark text-white transition-transform duration-200 min-[901px]:sticky min-[901px]:translate-x-0"
               :class="ouvert && '!translate-x-0'">
            <div class="border-b border-white/[0.08] px-5 pb-[18px] pt-[22px]">
                <div class="font-display text-lg font-extrabold">BookSpace</div>
                <div class="mt-0.5 text-xs text-white/45">{{ $titreEspace }}</div>
            </div>

            <nav class="flex flex-1 flex-col gap-[3px] overflow-y-auto px-3 py-3.5">
                @foreach ($liens as [$url, $icone, $libelle, $actif, $pastille])
                    <a href="{{ $url }}" @class([
                        'flex items-center gap-3 rounded-[10px] px-3.5 py-[11px] text-sm font-semibold',
                        'bg-brand text-white' => $actif,
                        'text-white/60 hover:text-white' => ! $actif,
                    ]) @if ($actif) aria-current="page" @endif>
                        <span class="flex w-5 justify-center"><x-icone :nom="$icone" /></span>
                        <span class="flex-1">{{ $libelle }}</span>
                        @if ($pastille || $url === '/messages')
                            {{-- Messages : pastille mise à jour en direct (événement `compteur`). --}}
                            <span class="flex h-[18px] min-w-[18px] items-center justify-center rounded-[10px] bg-gold px-[5px] text-[11px] font-extrabold text-[#7A1E00]"
                                  @if ($url === '/messages') data-compteur="messages" @endif @if (! $pastille) hidden @endif>{{ $pastille }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-white/[0.08] px-4 py-3.5">
                <div class="text-[13px] font-semibold">{{ $user->name }}</div>
                <div class="mb-2.5 text-[11px] text-white/45">{{ $sousTitre }}</div>
                <form method="POST" action="/deconnexion">
                    @csrf
                    <button class="w-full rounded-lg bg-white/[0.08] px-3 py-[9px] text-[13px] font-semibold text-white hover:bg-white/15">Déconnexion</button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-[58px] items-center gap-3 border-b border-slate-200 bg-white px-5">
                <button type="button" @click="ouvert = true" aria-label="Ouvrir le menu" class="p-1 text-brand-dark min-[901px]:hidden">
                    <x-icone nom="menu" taille="24" />
                </button>
                <div class="flex-1 font-display text-[15px] font-bold text-brand-dark">{{ $titrePage }}</div>
                @unless ($admin)
                    <div class="rounded-xl bg-brand-dark"><x-cloche /></div>
                @endunless
            </header>

            <main class="mx-auto w-full max-w-[1080px] flex-1 p-4 min-[901px]:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>
