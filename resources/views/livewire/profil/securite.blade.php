@php $client = $user->estClient(); @endphp
<div class="flex flex-1 flex-col">
    <x-entete-profil titre="Sécurité" sous-titre="Mot de passe, appareils connectés et compte" />

    <div @class(['w-full max-w-[560px]', 'px-5 pb-6 pt-4' => $client])>

        {{-- ── Mot de passe ── --}}
        <x-section-profil titre="Changer de mot de passe" :enregistre="$enregistre === 'mot_de_passe'"
            description="Vos autres appareils connectés seront déconnectés.">
            <form wire:submit="changerMotDePasse">
                @error('actuel') <x-erreur :message="$message" /> @enderror
                @error('nouveau') <x-erreur :message="$message" /> @enderror
                <label class="mb-3 block">
                    <span class="etiquette">MOT DE PASSE ACTUEL</span>
                    <input type="password" wire:model="actuel" class="champ" autocomplete="current-password" required>
                </label>
                <label class="mb-3 block">
                    <span class="etiquette">NOUVEAU MOT DE PASSE</span>
                    <input type="password" wire:model="nouveau" class="champ" autocomplete="new-password" minlength="8" placeholder="Min. 8 caractères" required>
                </label>
                <label class="mb-3 block">
                    <span class="etiquette">CONFIRMER LE NOUVEAU MOT DE PASSE</span>
                    <input type="password" wire:model="nouveau_confirmation" class="champ" autocomplete="new-password" minlength="8" required>
                </label>
                <button type="submit" class="btn-primaire" wire:loading.attr="disabled" wire:target="changerMotDePasse">Changer le mot de passe</button>
            </form>
            <p class="mt-3 text-center text-xs text-slate-500">
                Mot de passe oublié ? Déconnectez-vous puis utilisez « Mot de passe oublié » sur l'écran de connexion.
            </p>
        </x-section-profil>

        {{-- ── Appareils ── --}}
        <x-section-profil titre="Appareils connectés" :enregistre="$enregistre === 'appareils'"
            description="Téléphone perdu ou ordinateur partagé ? Fermez toutes les autres sessions ouvertes sur votre compte ; celle-ci reste active.">
            <form wire:submit="deconnecterAutresAppareils">
                @error('motDePasseAppareils') <x-erreur :message="$message" /> @enderror
                <label class="mb-3 block">
                    <span class="etiquette">MOT DE PASSE</span>
                    <input type="password" wire:model="motDePasseAppareils" class="champ" autocomplete="current-password" required>
                </label>
                <button type="submit" class="btn-contour" wire:loading.attr="disabled" wire:target="deconnecterAutresAppareils">Se déconnecter des autres appareils</button>
            </form>
        </x-section-profil>

        {{-- ── Suppression du compte ── --}}
        @unless ($user->estAdmin())
            <x-section-profil titre="Supprimer mon compte" danger
                description="Vos informations personnelles (nom, téléphone, e-mail, photo) sont effacées et vous ne pourrez plus vous connecter. L'historique des réservations et des paiements est conservé de façon anonyme pour la comptabilité.">
                @if ($aVenir > 0)
                    <div class="rounded-xl bg-[#FEF0D6] px-3.5 py-3 text-[13px] text-[#854D0E]">
                        @if ($user->estGerant())
                            {{ $aVenir }} réservation{{ $aVenir > 1 ? 's' : '' }} à venir dans vos espaces. Honorez-les ou annulez-les avant de supprimer votre compte.
                        @else
                            Vous avez {{ $aVenir }} réservation{{ $aVenir > 1 ? 's' : '' }} à venir. Annulez-la ou attendez qu'elle soit passée avant de supprimer votre compte.
                        @endif
                    </div>
                @else
                    <form wire:submit="supprimerCompte">
                        @error('suppression') <x-erreur :message="$message" /> @enderror
                        @error('motDePasseSuppression') <x-erreur :message="$message" /> @enderror
                        @error('confirmeSuppression') <x-erreur :message="$message" /> @enderror
                        @if ($user->estGerant())
                            <p class="mb-3 text-[13px] text-slate-600">Vos espaces seront retirés du catalogue.</p>
                        @endif
                        <label class="mb-3 block">
                            <span class="etiquette">MOT DE PASSE</span>
                            <input type="password" wire:model="motDePasseSuppression" class="champ" autocomplete="current-password" required>
                        </label>
                        <label class="mb-3 flex items-start gap-2">
                            <input type="checkbox" wire:model="confirmeSuppression" class="mt-0.5 accent-red-600">
                            <span class="text-xs leading-normal text-slate-600">Je comprends que cette action est définitive.</span>
                        </label>
                        <button type="submit" wire:loading.attr="disabled" wire:target="supprimerCompte"
                                class="block w-full rounded-[14px] bg-red-600 px-4 py-[13px] text-center text-[15px] font-semibold text-white">
                            Supprimer définitivement mon compte
                        </button>
                    </form>
                @endif
            </x-section-profil>
        @endunless
    </div>
</div>
