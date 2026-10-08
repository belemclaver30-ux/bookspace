@php
    $vues = [
        [
            'img' => 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=1200&q=80&auto=format&fit=crop',
            'titre' => 'Réservez votre espace en quelques secondes',
            'sous' => 'Studios de répétition et terrains de foot, à portée de clic.',
        ],
        [
            'img' => 'https://images.unsplash.com/photo-1459865264687-595d652de67e?w=1200&q=80&auto=format&fit=crop',
            'titre' => "Payez l'acompte par Mobile Money",
            'sous' => 'Orange Money, Moov Money : votre créneau bloqué à l’instant.',
        ],
        [
            'img' => 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=1200&q=80&auto=format&fit=crop',
            'titre' => 'Des disponibilités en temps réel',
            'sous' => 'Zéro double réservation, zéro allers-retours pour rien.',
        ],
    ];
@endphp
<x-layouts.base>
    <div class="relative flex min-h-dvh w-full justify-center bg-[#5A1500]"
         x-data="{
            i: 0,
            total: {{ count($vues) }},
            init() {
                if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;
                setInterval(() => this.i = (this.i + 1) % this.total, 5000);
            },
         }">
        {{-- Décor de fond (fondu entre les vues) --}}
        <div aria-hidden="true" class="absolute inset-0 overflow-hidden">
            @foreach ($vues as $idx => $vue)
                <div class="absolute inset-0 bg-cover bg-center transition-[opacity,transform] duration-[1s,6s] ease-in-out"
                     style="background-image: url('{{ $vue['img'] }}')"
                     :class="i === {{ $idx }} ? 'opacity-100 scale-105' : 'opacity-0 scale-100'"></div>
            @endforeach
            <div class="absolute inset-0"
                 style="background: radial-gradient(120% 75% at 50% 0%, rgba(252,78,0,0.34) 0%, transparent 55%), linear-gradient(180deg, rgba(50,12,0,0.42) 0%, rgba(80,18,0,0.58) 42%, rgba(70,16,0,0.97) 100%)"></div>
        </div>

        {{-- En-tête marque --}}
        <div class="absolute left-6 top-[max(22px,env(safe-area-inset-top))] z-[2] flex items-center gap-3">
            <div class="flex h-[46px] w-[46px] items-center justify-center rounded-[14px] bg-brand shadow-[0_10px_30px_rgba(252,78,0,0.55)]">
                <svg width="26" height="26" fill="none" viewBox="0 0 42 42" aria-hidden="true">
                    <path d="M14 28V16l14-4v12" stroke="white" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
                    <circle cx="11" cy="28" r="3" fill="white" />
                    <circle cx="25" cy="24" r="3" fill="white" />
                    <rect x="26" y="18" width="10" height="8" rx="2" stroke="white" stroke-width="2.2" fill="none" />
                </svg>
            </div>
            <div>
                <div class="font-display text-xl font-extrabold leading-none tracking-[-0.3px] text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.4)]">BookSpace</div>
                <div class="mt-[3px] text-[11px] tracking-[0.5px] text-white/70">Ouagadougou</div>
            </div>
        </div>

        {{-- Contenu --}}
        <div class="relative z-[2] flex w-full min-w-0 max-w-[460px] flex-col justify-end px-[26px] pb-[calc(30px+env(safe-area-inset-bottom))]">
            <div class="flex flex-col pb-[26px]">
                <div class="mb-[22px] flex gap-[7px]">
                    @foreach ($vues as $idx => $vue)
                        <button type="button" aria-label="Vue {{ $idx + 1 }}" @click="i = {{ $idx }}"
                                class="h-[5px] rounded-[3px] transition-all duration-400"
                                :class="i === {{ $idx }} ? 'w-[26px] bg-brand' : 'w-2 bg-white/35'"></button>
                    @endforeach
                </div>

                <div class="grid">
                    @foreach ($vues as $idx => $vue)
                        <div class="[grid-area:1/1]" x-show="i === {{ $idx }}" @if ($idx > 0) x-cloak @endif
                             x-transition:enter="animate-fadeinup">
                            <h1 class="m-0 text-balance font-display text-[clamp(23px,6vw,32px)] font-extrabold leading-[1.2] tracking-[-0.5px] text-white [text-shadow:0_2px_20px_rgba(0,0,0,0.4)]">
                                {{ $vue['titre'] }}
                            </h1>
                            <p class="mt-3 text-[clamp(13.5px,3.7vw,15px)] leading-[1.55] text-white/[0.82]">{{ $vue['sous'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a href="/connexion" class="block w-full rounded-2xl bg-brand p-4 text-center text-[15.5px] font-bold tracking-[0.2px] text-white shadow-[0_14px_34px_rgba(252,78,0,0.45)]">
                    Se connecter
                </a>
                <a href="/inscription" class="block w-full rounded-2xl border-[1.5px] border-white/50 bg-white/[0.12] p-4 text-center text-[15.5px] font-bold text-white backdrop-blur-[6px]">
                    Créer un compte
                </a>
                <a href="/inscription?role=gerant" class="mt-1 text-center text-[13px] text-white/70">
                    Vous gérez un espace ? <span class="font-semibold text-gold">Publiez-le</span>
                </a>
            </div>
        </div>
    </div>
</x-layouts.base>
