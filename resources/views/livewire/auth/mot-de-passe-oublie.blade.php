@php
    $etapes = ['telephone' => 1, 'code' => 2, 'nouveau' => 3];
    $titres = [
        'telephone' => ['Mot de passe oublié', 'Indiquez le numéro de votre compte : nous vous envoyons un code par SMS.'],
        'code' => ['Entrez le code', null],
        'nouveau' => ['Nouveau mot de passe', 'Dernière étape : choisissez un mot de passe d’au moins 8 caractères.'],
        'support' => ['Plus accès à votre numéro ?', 'Pas d’inquiétude : notre équipe peut vous aider à récupérer votre compte.'],
    ];
    [$titre, $sousTitre] = $titres[$etape];
@endphp
<div class="flex flex-1 flex-col">
    <div class="flex items-center gap-3 px-6 pt-5">
        @if (in_array($etape, ['code', 'support'], true))
            {{-- Retour à l'étape précédente, sans quitter la page. --}}
            <button type="button" wire:click="afficher('{{ $etape === 'support' && $telephone !== '' ? 'code' : 'telephone' }}')"
                    class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[10px] bg-white text-brand-dark shadow-carte"
                    aria-label="Étape précédente">
                <x-icone nom="retour" taille="22" />
            </button>
        @else
            <x-retour href="/connexion" />
        @endif

        @isset($etapes[$etape])
            <div class="ml-auto flex items-center gap-1.5" aria-label="Étape {{ $etapes[$etape] }} sur 3">
                @foreach ($etapes as $n)
                    <span @class(['h-1.5 rounded-full', 'w-6 bg-brand' => $n === $etapes[$etape], 'w-1.5 bg-brand/40' => $n < $etapes[$etape], 'w-1.5 bg-slate-200' => $n > $etapes[$etape]])></span>
                @endforeach
            </div>
        @endisset
    </div>

    <div class="flex-1 px-6 pb-9 pt-7">
        <div class="mb-7">
            <h1 class="mb-1.5 font-display text-[26px] font-bold text-brand-dark">{{ $titre }}</h1>
            <p class="text-sm leading-relaxed text-slate-500">
                @if ($etape === 'code')
                    Si un compte existe pour le <b class="whitespace-nowrap text-ink">{{ $telephone }}</b>, un code à 6 chiffres
                    vient d’y être envoyé par SMS. Il est valable {{ $validite }} minutes.
                @else
                    {{ $sousTitre }}
                @endif
            </p>
        </div>

        <x-erreur />

        {{-- ── 1. Numéro ── --}}
        @if ($etape === 'telephone')
            <form wire:submit="envoyerCode">
                <label class="mb-5 block">
                    <span class="etiquette">NUMÉRO DE TÉLÉPHONE</span>
                    <input type="tel" wire:model="telephone" class="champ" placeholder="+226 70 00 00 00" autocomplete="tel" inputmode="tel" required autofocus>
                </label>
                <button type="submit" class="btn-primaire" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="envoyerCode">Recevoir un code</span>
                    <span wire:loading wire:target="envoyerCode">Envoi…</span>
                </button>
            </form>

            <button type="button" wire:click="afficher('support')" class="mt-6 block w-full text-center text-[13px] font-semibold text-slate-500">
                Je n’ai plus accès à mon numéro
            </button>
        @endif

        {{-- ── 2. Code ── --}}
        @if ($etape === 'code')
            @if (session('code_renvoye'))
                <div class="mb-3.5 rounded-xl bg-green-50 px-3.5 py-2.5 text-[13px] font-medium text-green-700" role="status">Un nouveau code vient d’être envoyé.</div>
            @endif

            <form wire:submit="verifierCode">
                <label class="mb-5 block">
                    <span class="etiquette">CODE REÇU PAR SMS</span>
                    {{-- Validation automatique dès le 6e chiffre (code collé ou proposé par le clavier). --}}
                    <input type="text" wire:model="code" class="champ text-center text-2xl font-bold tracking-[0.5em]" inputmode="numeric"
                           autocomplete="one-time-code" maxlength="6" placeholder="••••••" required autofocus
                           x-data x-on:input="$el.value = $el.value.replace(/\D/g, ''); if ($el.value.length === 6) { $wire.code = $el.value; $wire.verifierCode() }">
                </label>
                <button type="submit" class="btn-primaire" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="verifierCode">Valider le code</span>
                    <span wire:loading wire:target="verifierCode">Vérification…</span>
                </button>
            </form>

            <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-[13px] text-slate-600">
                <div class="mb-2 font-semibold text-ink">Pas reçu de SMS ?</div>
                <ul class="mb-3 list-disc space-y-1 pl-5 text-slate-500">
                    <li>Vérifiez que le numéro ci-dessus est bien le vôtre.</li>
                    <li>Le SMS peut mettre jusqu’à une minute à arriver.</li>
                </ul>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2" wire:key="renvoi-{{ $attente }}"
                     x-data="{ reste: {{ $attente }} }"
                     x-init="const t = setInterval(() => { if (reste > 0) reste--; else clearInterval(t) }, 1000)">
                    <button type="button" wire:click="renvoyerCode" :disabled="reste > 0" wire:loading.attr="disabled"
                            class="font-semibold text-brand disabled:text-slate-400">
                        <span x-show="reste > 0" x-text="`Renvoyer le code (${reste} s)`">Renvoyer le code</span>
                        <span x-show="reste <= 0">Renvoyer le code</span>
                    </button>
                    <button type="button" wire:click="afficher('telephone')" class="font-semibold text-slate-500">Changer de numéro</button>
                </div>
            </div>

            <button type="button" wire:click="afficher('support')" class="mt-5 block w-full text-center text-[13px] font-semibold text-slate-500">
                Je n’ai plus accès à ce numéro
            </button>
        @endif

        {{-- ── 3. Nouveau mot de passe ── --}}
        @if ($etape === 'nouveau')
            <div class="mb-5 flex items-center gap-2.5 rounded-xl bg-green-50 px-3.5 py-3 text-[13px] font-medium text-green-700" role="status">
                <x-icone nom="coche" taille="18" /> Code vérifié
            </div>

            <form wire:submit="reinitialiser" x-data="{ voir: false }">
                <label class="mb-2 block" for="nouveau-mdp"><span class="etiquette">NOUVEAU MOT DE PASSE</span></label>
                <div class="relative mb-2">
                    <input id="nouveau-mdp" :type="voir ? 'text' : 'password'" type="password" wire:model="password"
                           class="champ pr-14" autocomplete="new-password" minlength="8" required autofocus>
                    <button type="button" @click="voir = ! voir" :aria-pressed="voir" :aria-label="voir ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                            class="absolute inset-y-0 right-1 my-auto h-10 rounded-lg px-3 text-[12px] font-bold text-brand"
                            x-text="voir ? 'Masquer' : 'Afficher'">Afficher</button>
                </div>
                <p class="mb-5 text-xs text-slate-400">Au moins 8 caractères. Évitez votre date de naissance ou votre numéro.</p>

                <button type="submit" class="btn-primaire" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="reinitialiser">Enregistrer et me connecter</span>
                    <span wire:loading wire:target="reinitialiser">Enregistrement…</span>
                </button>
            </form>
        @endif

        {{-- ── Plus accès au numéro ── --}}
        @if ($etape === 'support')
            <ol class="mb-6 space-y-3 text-[13.5px] text-slate-600">
                @foreach ([
                    'Contactez le support BookSpace avec le numéro de votre ancien compte et votre nom complet.',
                    'Nous vérifions votre identité (nom, réservations récentes, pièce d’identité pour un gérant).',
                    'Nous rattachons votre compte à votre nouveau numéro : vous pourrez alors recevoir un code.',
                ] as $i => $texte)
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand">{{ $i + 1 }}</span>
                        <span>{{ $texte }}</span>
                    </li>
                @endforeach
            </ol>

            <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white shadow-carte">
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 px-4 py-3.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-green-50 text-green-600"><x-icone nom="message" /></span>
                        <span class="flex-1 text-sm font-semibold text-ink">Écrire sur WhatsApp</span>
                        <x-icone nom="chevron" taille="16" class="text-slate-400" />
                    </a>
                @endif
                @isset($support['telephone'])
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $support['telephone']) }}" class="flex items-center gap-3 px-4 py-3.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand"><x-icone nom="telephone" /></span>
                        <span class="flex-1 text-sm font-semibold text-ink">Appeler le {{ $support['telephone'] }}</span>
                        <x-icone nom="chevron" taille="16" class="text-slate-400" />
                    </a>
                @endisset
                @if ($courriel)
                    <a href="{{ $courriel }}" class="flex items-center gap-3 px-4 py-3.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand"><x-icone nom="enveloppe" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-ink">Écrire un e-mail</span>
                            <span class="block truncate text-xs text-slate-400">{{ $support['email'] }}</span>
                        </span>
                        <x-icone nom="chevron" taille="16" class="text-slate-400" />
                    </a>
                @endif
            </div>
            @isset($support['horaires'])
                <p class="mt-3 text-center text-xs text-slate-400">{{ $support['horaires'] }}</p>
            @endisset
        @endif
    </div>
</div>
