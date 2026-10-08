@props(['titre', 'description' => null, 'enregistre' => false])
{{-- Réglage oui/non : libellé + description à gauche, interrupteur à droite. --}}
<label class="flex cursor-pointer items-start gap-3 px-4 py-3.5">
    <span class="min-w-0 flex-1">
        <span class="block text-sm font-semibold text-ink">
            {{ $titre }}
            @if ($enregistre)
                <span class="ml-1 text-xs font-semibold text-green-600" role="status">✓ Enregistré</span>
            @endif
        </span>
        @if ($description)
            <span class="mt-0.5 block text-xs leading-normal text-slate-500">{{ $description }}</span>
        @endif
    </span>
    <input type="checkbox" role="switch" {{ $attributes->merge(['class' => 'peer sr-only']) }}>
    <span aria-hidden="true"
          class="relative mt-0.5 h-[26px] w-[46px] shrink-0 rounded-full bg-slate-300 transition-colors after:absolute after:left-[3px] after:top-[3px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-brand peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2"></span>
</label>
