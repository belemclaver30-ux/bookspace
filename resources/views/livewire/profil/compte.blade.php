@php $client = $user->estClient(); $gerant = $user->gerant; @endphp
<div class="flex flex-1 flex-col">
    <x-entete-profil titre="Mon compte" sous-titre="Photo, informations et numéro de téléphone" />

    <div @class(['w-full max-w-[560px]', 'px-5 pb-6 pt-4' => $client])>

        {{-- ── Photo ── --}}
        <x-section-profil titre="Photo de profil" :enregistre="$enregistre === 'photo'">
            @error('photo') <x-erreur :message="$message" /> @enderror
            <div class="flex items-center gap-4">
                <x-avatar :user="$user" taille="72" />
                <div class="flex flex-wrap gap-2">
                    <label class="btn-mini cursor-pointer bg-brand-50 text-brand-dark focus-within:ring-2 focus-within:ring-brand">
                        <span wire:loading.remove wire:target="photo">{{ $user->avatar ? 'Changer la photo' : 'Ajouter une photo' }}</span>
                        <span wire:loading wire:target="photo">Envoi…</span>
                        <input type="file" wire:model="photo" accept="image/*" class="sr-only">
                    </label>
                    @if ($user->avatar)
                        <button type="button" wire:click="supprimerPhoto" wire:confirm="Retirer votre photo de profil ?" class="btn-mini bg-slate-100 text-slate-600">Retirer</button>
                    @endif
                </div>
            </div>
        </x-section-profil>

        {{-- ── Informations ── --}}
        <x-section-profil titre="Informations personnelles" :enregistre="$enregistre === 'infos'">
            <form wire:submit="enregistrerInfos">
                @error('name') <x-erreur :message="$message" /> @enderror
                @error('email') <x-erreur :message="$message" /> @enderror
                @error('raison_sociale') <x-erreur :message="$message" /> @enderror
                <label class="mb-3 block">
                    <span class="etiquette">NOM COMPLET</span>
                    <input type="text" wire:model="name" class="champ" autocomplete="name" maxlength="255" required>
                </label>
                <label class="mb-3 block">
                    <span class="etiquette">EMAIL (optionnel)</span>
                    <input type="email" wire:model="email" class="champ" autocomplete="email" maxlength="255" placeholder="email@exemple.com">
                </label>
                @if ($gerant)
                    <label class="mb-3 block">
                        <span class="etiquette">RAISON SOCIALE</span>
                        <input type="text" wire:model="raison_sociale" class="champ" autocomplete="organization" maxlength="255" required>
                    </label>
                    <label class="mb-3 block">
                        <span class="etiquette">TÉLÉPHONE PROFESSIONNEL (optionnel)</span>
                        <input type="tel" wire:model="telephone_pro" class="champ" maxlength="30" placeholder="+226 70 00 00 00">
                    </label>
                @endif
                <button type="submit" class="btn-primaire" wire:loading.attr="disabled" wire:target="enregistrerInfos">Enregistrer</button>
            </form>
        </x-section-profil>

        {{-- ── Numéro de téléphone ── --}}
        <x-section-profil titre="Numéro de téléphone" :enregistre="$enregistre === 'telephone'"
            description="C'est votre identifiant de connexion et le numéro qui reçoit les SMS. Le nouveau numéro est confirmé par un code.">
            <div class="mb-3 flex items-center gap-3 rounded-xl bg-slate-50 px-3.5 py-3">
                <x-icone nom="telephone" class="text-slate-400" />
                <span class="flex-1 text-sm font-semibold text-ink">{{ $user->telephone }}</span>
                <span class="text-[11px] font-semibold text-slate-400">actuel</span>
            </div>

            @if (! $codeEnvoye)
                <form wire:submit="demanderCodeTelephone">
                    @error('nouveauTelephone') <x-erreur :message="$message" /> @enderror
                    @error('motDePasseTelephone') <x-erreur :message="$message" /> @enderror
                    <label class="mb-3 block">
                        <span class="etiquette">NOUVEAU NUMÉRO</span>
                        <input type="tel" wire:model="nouveauTelephone" class="champ" placeholder="+226 70 00 00 00" autocomplete="tel" required>
                    </label>
                    <label class="mb-3 block">
                        <span class="etiquette">MOT DE PASSE ACTUEL</span>
                        <input type="password" wire:model="motDePasseTelephone" class="champ" autocomplete="current-password" required>
                    </label>
                    <button type="submit" class="btn-contour" wire:loading.attr="disabled" wire:target="demanderCodeTelephone">
                        <span wire:loading.remove wire:target="demanderCodeTelephone">Recevoir un code sur ce numéro</span>
                        <span wire:loading wire:target="demanderCodeTelephone">Envoi…</span>
                    </button>
                </form>
            @else
                <form wire:submit="confirmerTelephone">
                    <p class="mb-3 text-[13px] text-slate-600">
                        Code envoyé par SMS au <b>{{ $nouveauTelephone }}</b>, valable {{ $validite }} minutes.
                    </p>
                    @error('codeTelephone') <x-erreur :message="$message" /> @enderror
                    <label class="mb-3 block">
                        <span class="etiquette">CODE REÇU</span>
                        <input type="text" wire:model="codeTelephone" class="champ text-center text-xl tracking-[0.5em]" inputmode="numeric"
                               autocomplete="one-time-code" maxlength="6" placeholder="••••••" required autofocus>
                    </label>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primaire flex-1" wire:loading.attr="disabled" wire:target="confirmerTelephone">Confirmer le numéro</button>
                        <button type="button" wire:click="annulerTelephone" class="btn-mini bg-slate-100 px-4 text-slate-600">Annuler</button>
                    </div>
                </form>
            @endif
        </x-section-profil>

        {{-- ── Versement des revenus (gérant) ── --}}
        @if ($gerant)
            <x-section-profil titre="Versement de vos revenus" :enregistre="$enregistre === 'versement'"
                description="Compte Mobile Money sur lequel BookSpace vous verse la part des acomptes payés en ligne (après commission).">
                <form wire:submit="enregistrerVersement">
                    <x-erreur />
                    <div class="mb-3 grid grid-cols-3 gap-2" role="radiogroup" aria-label="Opérateur">
                        @foreach ($operateurs as $cle => $libelle)
                            <button type="button" wire:click="$set('momo_operateur', '{{ $cle }}')" role="radio"
                                    aria-checked="{{ $momo_operateur === $cle ? 'true' : 'false' }}" @class([
                                'rounded-xl px-1 py-2.5 text-xs font-semibold',
                                'border-2 border-brand bg-brand-50 text-brand' => $momo_operateur === $cle,
                                'border-[1.5px] border-slate-200 bg-white text-slate-500' => $momo_operateur !== $cle,
                            ])>{{ $libelle }}</button>
                        @endforeach
                    </div>
                    <label class="mb-3 block">
                        <span class="etiquette">NUMÉRO MOBILE MONEY</span>
                        <input type="tel" wire:model="momo_numero" class="champ" placeholder="+226 70 00 00 00" maxlength="30" required>
                    </label>
                    <label class="mb-3 block">
                        <span class="etiquette">NOM DU TITULAIRE</span>
                        <input type="text" wire:model="momo_titulaire" class="champ" maxlength="255" placeholder="Tel qu'enregistré chez l'opérateur" required>
                    </label>
                    <button type="submit" class="btn-primaire" wire:loading.attr="disabled" wire:target="enregistrerVersement">Enregistrer</button>
                </form>
            </x-section-profil>
        @endif

        <a href="/profil/securite" class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3.5 shadow-carte">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500"><x-icone nom="cle" /></span>
            <span class="flex-1 text-sm font-medium text-ink">Mot de passe et sécurité</span>
            <x-icone nom="chevron" taille="16" class="text-slate-400" />
        </a>
    </div>
</div>
