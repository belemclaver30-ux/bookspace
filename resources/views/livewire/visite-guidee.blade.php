{{--
    Visite guidée du nouvel utilisateur : voile sombre, élément mis en lumière
    et bulle d'explication. Positionnement et navigation : resources/js/visite.js.
--}}
<div x-data="visiteGuidee(@js($etapes))" x-cloak x-show="actif"
     @keydown.window="clavier($event)"
     class="fixed inset-0 z-[100]" role="dialog" aria-modal="true" aria-labelledby="visite-titre" aria-describedby="visite-texte">

    {{-- Voile : bloque la page ; plein écran quand l'étape n'a pas de cible. --}}
    <div class="absolute inset-0 transition-colors duration-300 motion-reduce:transition-none"
         :class="trou ? 'bg-transparent' : 'bg-[#1A0A03]/75'"></div>

    {{-- Halo autour de l'élément présenté (son ombre géante assombrit le reste). --}}
    <div x-show="trou" class="pointer-events-none fixed rounded-2xl ring-[3px] ring-gold transition-all duration-300 motion-reduce:transition-none"
         :style="trou"></div>

    {{-- Bulle --}}
    <div x-ref="bulle" :style="bulle"
         class="fixed w-[min(340px,calc(100vw-32px))] rounded-[20px] bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.35)] transition-[top,left] duration-300 motion-reduce:transition-none">
        <div x-show="trou" class="absolute h-3.5 w-3.5 rotate-45 bg-white" :style="fleche"></div>

        <template x-if="etape.illustration">
            <div class="mb-4 flex justify-center">
                <div class="flex h-[72px] w-[72px] items-center justify-center rounded-[22px] bg-gradient-to-br from-brand to-brand-dark text-white shadow-[0_10px_30px_rgba(252,78,0,0.35)]">
                    <span x-show="etape.illustration === 'accueil'"><x-icone nom="batiment" taille="34" /></span>
                    <span x-show="etape.illustration === 'fin'"><x-icone nom="coche" taille="34" /></span>
                </div>
            </div>
        </template>

        <div class="mb-1 text-[11px] font-bold uppercase tracking-[0.6px] text-brand"
             x-text="`Étape ${i + 1} sur ${etapes.length}`"></div>
        <h2 id="visite-titre" class="mb-1.5 font-display text-[18px] font-bold leading-snug text-brand-dark" x-text="etape.titre"></h2>
        <p id="visite-texte" class="text-[14px] leading-relaxed text-slate-600" x-text="etape.texte"></p>

        {{-- Progression --}}
        <div class="mt-4 flex items-center gap-1.5" aria-hidden="true">
            <template x-for="(e, n) in etapes" :key="n">
                <span class="h-1.5 rounded-full transition-all duration-300 motion-reduce:transition-none"
                      :class="n === i ? 'w-5 bg-brand' : (n < i ? 'w-1.5 bg-brand/40' : 'w-1.5 bg-slate-200')"></span>
            </template>
        </div>

        {{-- Étapes intermédiaires --}}
        <div x-show="! derniere" class="mt-4 flex items-center gap-2">
            <button type="button" @click="passer()"
                    class="mr-auto rounded-lg px-1 py-2 text-[13px] font-semibold text-slate-400 hover:text-slate-600">Passer</button>
            <button type="button" x-show="i > 0" @click="precedent()"
                    class="rounded-xl border-[1.5px] border-slate-200 px-3.5 py-2.5 text-[13px] font-semibold text-slate-600">Précédent</button>
            <button type="button" x-ref="suivant" @click="suivant()"
                    class="rounded-xl bg-brand px-4 py-2.5 text-[13px] font-bold text-white shadow-[0_6px_16px_rgba(252,78,0,0.3)]"
                    x-text="i === 0 ? 'Démarrer la visite' : 'Suivant'"></button>
        </div>

        {{-- Dernière étape : action principale pleine largeur --}}
        <div x-show="derniere" class="mt-4">
            <button type="button" x-ref="fin" @click="suivant()"
                    class="w-full rounded-xl bg-brand px-4 py-3 text-[14px] font-bold text-white shadow-[0_6px_16px_rgba(252,78,0,0.3)]"
                    x-text="etape.lien ? etape.lien.libelle : 'Commencer'"></button>
            <div class="mt-2 flex items-center justify-between">
                <button type="button" @click="precedent()"
                        class="rounded-lg px-1 py-1.5 text-[12.5px] font-semibold text-slate-400 hover:text-slate-600">← Précédent</button>
                <button type="button" x-show="etape.lien" @click="terminer()"
                        class="rounded-lg px-1 py-1.5 text-[12.5px] font-semibold text-slate-400 hover:text-slate-600">Plus tard</button>
            </div>
        </div>
    </div>
</div>
