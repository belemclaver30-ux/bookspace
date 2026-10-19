{{-- Cloche de notifications avec pastille du nombre de non-lues. --}}
@php $nonLues = auth()->user()?->notifications()->whereNull('lu_at')->count() ?? 0; @endphp
<a href="/notifications" aria-label="Notifications"
   {{ $attributes->merge(['class' => 'relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white']) }}>
    <x-icone nom="cloche" taille="20" />
    <x-pastille :nombre="$nonLues" compteur="notifications" class="absolute -right-1 -top-1" />
</a>
