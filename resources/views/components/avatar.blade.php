@props(['user', 'taille' => 72])
{{-- Photo de profil, ou initiale du nom sur fond orange. --}}
@php $url = $user->avatarUrl(); @endphp
<span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand font-display font-bold text-white']) }}
      style="width: {{ $taille }}px; height: {{ $taille }}px; font-size: {{ round($taille * 0.4) }}px">
    @if ($url)
        <img src="{{ $url }}" alt="Photo de {{ $user->name }}" class="h-full w-full object-cover">
    @else
        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
    @endif
</span>
