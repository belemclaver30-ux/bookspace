<div class="flex flex-1 flex-col">
    <div class="px-6 pt-5"><x-retour href="/connexion" /></div>

    <div class="flex-1 px-6 pb-9 pt-7">
        <div class="mb-7">
            <h1 class="mb-1.5 font-display text-[26px] font-bold text-brand-dark">Mot de passe oublié</h1>
            <p class="text-sm text-slate-500">
                @if ($etape === 'telephone')
                    Indiquez le numéro de votre compte : nous vous envoyons un code par SMS.
                @else
                    Si un compte existe pour le <b>{{ $telephone }}</b>, un code vient d'y être envoyé par SMS.
                    Il est valable {{ $validite }} minutes.
                @endif
            </p>
        </div>

        <x-erreur />

        @if ($etape === 'telephone')
            <form wire:submit="envoyerCode">
                <label class="mb-5 block">
                    <span class="etiquette">NUMÉRO DE TÉLÉPHONE</span>
                    <input type="tel" wire:model="telephone" class="champ" placeholder="+226 70 00 00 00" autocomplete="tel" required autofocus>
                </label>
                <button type="submit" class="btn-primaire" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="envoyerCode">Recevoir un code</span>
                    <span wire:loading wire:target="envoyerCode">Envoi…</span>
                </button>
            </form>
        @else
            <form wire:submit="reinitialiser">
                <label class="mb-4 block">
                    <span class="etiquette">CODE REÇU PAR SMS</span>
                    <input type="text" wire:model="code" class="champ text-center text-xl tracking-[0.5em]" inputmode="numeric"
                           autocomplete="one-time-code" maxlength="6" placeholder="••••••" required autofocus>
                </label>
                <label class="mb-4 block">
                    <span class="etiquette">NOUVEAU MOT DE PASSE</span>
                    <input type="password" wire:model="password" class="champ" placeholder="Min. 8 caractères" autocomplete="new-password" minlength="8" required>
                </label>
                <label class="mb-5 block">
                    <span class="etiquette">CONFIRMER LE MOT DE PASSE</span>
                    <input type="password" wire:model="password_confirmation" class="champ" autocomplete="new-password" minlength="8" required>
                </label>
                <button type="submit" class="btn-primaire" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="reinitialiser">Changer mon mot de passe</span>
                    <span wire:loading wire:target="reinitialiser">Vérification…</span>
                </button>
            </form>

            <p class="mt-6 text-center text-[13px] text-slate-500">
                Pas reçu de SMS ?
                <button type="button" wire:click="recommencer" class="font-semibold text-brand">Changer de numéro ou renvoyer un code</button>
            </p>
        @endif
    </div>
</div>
