<div class="flex flex-1 flex-col">
    <div class="px-6 pt-5"><x-retour href="/" /></div>

    <form wire:submit="connecter" class="flex-1 px-6 pb-9 pt-7">
        <div class="mb-8">
            <h1 class="mb-1.5 font-display text-[26px] font-bold text-brand-dark">Bon retour 👋</h1>
            <p class="text-sm text-slate-500">Connectez-vous pour continuer</p>
        </div>

        <x-erreur />

        <label class="mb-4 block">
            <span class="etiquette">NUMÉRO DE TÉLÉPHONE</span>
            <input type="tel" wire:model="telephone" class="champ" placeholder="+226 70 00 00 00" autocomplete="tel" required autofocus>
        </label>

        <label class="mb-4 block">
            <span class="etiquette">MOT DE PASSE</span>
            <input type="password" wire:model="password" class="champ" placeholder="••••••••" autocomplete="current-password" required>
        </label>
        <div class="-mt-2 mb-2 text-right">
            <a href="/mot-de-passe-oublie" class="text-[13px] font-semibold text-brand">Mot de passe oublié ?</a>
        </div>

        <div class="h-3"></div>
        <button type="submit" class="btn-primaire" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="connecter">Se connecter</span>
            <span wire:loading wire:target="connecter">Connexion…</span>
        </button>

        <p class="mt-7 text-center text-[13px] text-slate-500">
            Pas encore de compte ?
            <a href="/inscription" class="font-semibold text-brand">S'inscrire</a>
        </p>
    </form>
</div>
