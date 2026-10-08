@props(['title' => null, 'fond' => 'bg-slate-50'])
{{-- Écran plein (sans navigation) : colonne mobile centrée. --}}
<x-layouts.base :title="$title">
    <div class="flex min-h-dvh justify-center {{ $fond }}">
        <div class="relative flex min-h-dvh w-full max-w-[440px] flex-col">
            {{ $slot }}
        </div>
    </div>
</x-layouts.base>
