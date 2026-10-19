@props(['donnees', 'titre'])
{{--
    Visionneuse plein écran de la vue immersive d'un espace, ouverte par
    l'événement `ouvrir-vue-immersive`. Deux modes (resources/js/vue-immersive.js) :
    - `angles` : 4 photos qu'on fait défiler en boucle en glissant, avec boussole ;
    - `panorama` : photo 360° (Photo Sphere Viewer, chargé seulement à l'ouverture).
--}}
<div x-data="vueImmersive(@js($donnees))" x-cloak x-show="ouvert"
     @ouvrir-vue-immersive.window="ouvrir()" @keydown.window="clavier($event)"
     x-transition.opacity.duration.200ms
     class="fixed inset-0 z-[90] flex flex-col bg-[#0E0603] text-white"
     role="dialog" aria-modal="true" aria-label="Vue immersive : {{ $titre }}">

    <div class="relative z-10 flex items-center gap-3 px-4 pb-3 pt-[max(16px,env(safe-area-inset-top))]">
        <div class="min-w-0 flex-1">
            <div class="truncate font-display text-[15px] font-bold">{{ $titre }}</div>
            <div class="text-[11.5px] text-white/55"
                 x-text="mode === 'panorama' ? 'Vue 360° · glissez pour regarder autour' : 'Glissez pour tourner dans l’espace'"></div>
        </div>
        <button type="button" x-ref="fermer" @click="fermer()" aria-label="Fermer la vue"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 hover:bg-white/20">
            <x-icone nom="fermer" taille="20" />
        </button>
    </div>

    {{-- ── Mode 4 angles ── --}}
    <template x-if="mode === 'angles'">
        <div class="relative flex flex-1 flex-col">
            <div x-ref="scene" class="relative flex-1 cursor-grab touch-pan-y select-none overflow-hidden active:cursor-grabbing"
                 @pointerdown="debutGlisser($event)" @pointermove="glisser($event)"
                 @pointerup="finGlisser($event)" @pointercancel="finGlisser($event)">
                <div class="flex h-full will-change-transform" :style="styleBande" @transitionend="finTransition()">
                    <template x-for="(vue, n) in bande" :key="n">
                        <div class="relative h-full w-full shrink-0 overflow-hidden">
                            {{-- Fond flouté de la même photo : la photo entière reste visible sans bandes noires. --}}
                            <img :src="vue.url" alt="" aria-hidden="true" draggable="false"
                                 class="absolute inset-0 h-full w-full scale-110 object-cover opacity-50 blur-2xl">
                            <img :src="vue.url" :alt="`Vue ${vue.libelle}`" draggable="false"
                                 class="relative h-full w-full object-contain">
                        </div>
                    </template>
                </div>

                {{-- Indice de geste, masqué dès la première rotation. --}}
                <div x-show="! aTourne" x-transition.opacity
                     class="pointer-events-none absolute inset-x-0 bottom-6 flex justify-center">
                    <div class="flex items-center gap-2 rounded-full bg-black/55 px-4 py-2 text-[12.5px] font-semibold backdrop-blur-sm">
                        <span class="inline-block motion-safe:animate-[glisser_1.6s_ease-in-out_infinite]">👆</span> Glissez vers la gauche ou la droite
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center gap-6 px-4 pb-[max(20px,env(safe-area-inset-bottom))] pt-4">
                <button type="button" @click="tourner(-1)" aria-label="Tourner vers la gauche"
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 hover:bg-white/20">
                    <x-icone nom="retour" taille="22" />
                </button>

                {{-- Boussole : le cône indique la direction regardée. --}}
                <div class="relative h-[92px] w-[92px]" aria-hidden="true">
                    <div class="absolute inset-0 rounded-full border border-white/15 bg-white/5"></div>
                    <div class="absolute inset-0" :style="`transform: rotate(${cap}deg)`"
                         :class="glisse ? '' : 'transition-transform duration-300 motion-reduce:transition-none'">
                        <div class="absolute left-1/2 top-1/2 h-[46px] w-[46px] -translate-x-1/2 -translate-y-full"
                             style="background: conic-gradient(from -30deg at 50% 100%, rgba(252,78,0,0.85) 0deg, rgba(252,78,0,0.85) 60deg, transparent 60deg); clip-path: polygon(0 0, 100% 0, 50% 100%);"></div>
                    </div>
                    <template x-for="(vue, n) in angles" :key="n">
                        <button type="button" @click="aller(n)" tabindex="-1"
                                class="absolute text-[9px] font-bold uppercase tracking-[0.3px]"
                                :class="[
                                    ['left-1/2 top-1 -translate-x-1/2', 'right-1 top-1/2 -translate-y-1/2', 'bottom-1 left-1/2 -translate-x-1/2', 'left-1 top-1/2 -translate-y-1/2'][n],
                                    n === actuel ? 'text-white' : 'text-white/45',
                                ]"
                                x-text="vue.libelle.slice(0, 3)"></button>
                    </template>
                    <div class="absolute left-1/2 top-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white"></div>
                </div>

                <button type="button" @click="tourner(1)" aria-label="Tourner vers la droite"
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 hover:bg-white/20">
                    <x-icone nom="retour" taille="22" class="rotate-180" />
                </button>
            </div>
            <div class="sr-only" aria-live="polite" x-text="`Vue ${angles[actuel].libelle}, ${actuel + 1} sur ${angles.length}`"></div>
        </div>
    </template>

    {{-- ── Mode panorama 360° ── --}}
    <template x-if="mode === 'panorama'">
        <div class="relative flex-1">
            <div x-ref="sphere" class="absolute inset-0"></div>
            <div x-show="chargement" class="absolute inset-0 flex items-center justify-center text-[13px] text-white/70">Chargement de la vue 360°…</div>
            <div x-show="erreur" x-text="erreur" class="absolute inset-x-6 top-1/2 -translate-y-1/2 rounded-xl bg-red-600/90 p-3.5 text-center text-[13px]"></div>
        </div>
    </template>
</div>
