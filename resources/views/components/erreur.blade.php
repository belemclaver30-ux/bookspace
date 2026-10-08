@props(['message' => null])
@php
    // Sans message explicite : première erreur de validation du composant.
    $message ??= $errors->first();
@endphp
@if ($message)
    <div {{ $attributes->merge(['class' => 'mb-3.5 rounded-xl bg-red-50 px-3.5 py-2.5 text-[13px] font-medium text-red-600']) }} role="alert">
        {{ $message }}
    </div>
@endif
