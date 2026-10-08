@php $client = $user->estClient(); @endphp
<div class="flex flex-1 flex-col">
    <x-entete-profil titre="Préférences" sous-titre="Notifications, rappels et langue" />

    <div @class(['w-full max-w-[520px]', 'px-5 pb-6 pt-4' => $client])>
        <div class="mb-2 pl-1 text-[11px] font-bold uppercase tracking-[0.5px] text-slate-400">Notifications</div>
        <div class="mb-2 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white shadow-carte">
            <x-interrupteur wire:model.live="notifSms" titre="Recevoir les SMS" :enregistre="$enregistre === 'sms'"
                :description="$user->estGerant()
                    ? 'Nouvelle réservation, demande d’un client, rappel : un SMS au ' . $user->telephone . '.'
                    : 'Confirmation, report, réponse du gérant : un SMS au ' . $user->telephone . '.'" />
            <x-interrupteur wire:model.live="notifRappels" titre="Rappel avant le créneau" :enregistre="$enregistre === 'rappels'"
                :description="'Un rappel environ ' . $minutesRappel . ' minutes avant chaque réservation.'" />
        </div>
        <p class="mb-5 px-1 text-xs leading-normal text-slate-500">
            @if (! $notifSms)
                <span class="font-semibold text-attente">SMS désactivés :</span>
                @if ($user->estGerant())
                    vous ne serez plus averti par SMS d'une nouvelle réservation. Pensez à consulter vos notifications.
                @else
                    vous ne recevrez plus de SMS, y compris pour la confirmation de vos réservations.
                @endif
            @else
                Quel que soit ce réglage, toutes vos notifications restent consultables dans l'application.
            @endif
        </p>

        <div class="mb-5 overflow-hidden rounded-2xl bg-white shadow-carte">
            <a href="/notifications" class="flex items-center gap-3 px-4 py-3.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500"><x-icone nom="cloche" /></span>
                <span class="flex-1 text-sm font-medium text-ink">Voir mes notifications</span>
                @if ($nonLues > 0)
                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-bold text-white">{{ $nonLues > 99 ? '99+' : $nonLues }}</span>
                @endif
                <x-icone nom="chevron" taille="16" class="text-slate-400" />
            </a>
        </div>

        <div class="mb-2 pl-1 text-[11px] font-bold uppercase tracking-[0.5px] text-slate-400">Langue</div>
        <div class="overflow-hidden rounded-2xl bg-white shadow-carte">
            <div class="flex items-center gap-3 px-4 py-3.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500"><x-icone nom="langue" /></span>
                <span class="flex-1 text-sm font-medium text-ink">Français</span>
                <x-icone nom="coche" taille="18" class="text-brand" />
            </div>
        </div>
        <p class="mt-2 px-1 text-xs leading-normal text-slate-500">BookSpace est disponible en français uniquement pour le moment.</p>
    </div>
</div>
