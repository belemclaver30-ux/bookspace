{{--
    Message bref affiché en haut de l'écran après une redirection
    (`session()->flash('toast', '…')`), masqué tout seul après quelques secondes.
--}}
@if (session('toast'))
    <div x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 4500)" x-show="visible"
         x-transition.opacity.duration.300ms role="status"
         class="pointer-events-none fixed inset-x-0 top-[max(16px,env(safe-area-inset-top))] z-[80] flex justify-center px-4">
        <div class="pointer-events-auto flex max-w-[400px] items-center gap-2.5 rounded-2xl bg-ink px-4 py-3 text-[13.5px] font-semibold text-white shadow-[0_12px_30px_rgba(0,0,0,0.25)]">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500"><x-icone nom="coche" taille="14" /></span>
            <span class="flex-1">{{ session('toast') }}</span>
            <button type="button" @click="visible = false" class="text-white/60 hover:text-white" aria-label="Fermer">
                <x-icone nom="fermer" taille="16" />
            </button>
        </div>
    </div>
@endif
