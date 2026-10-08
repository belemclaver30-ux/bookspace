<div class="flex flex-1 flex-col">
    <div class="px-6 pt-5"><x-retour href="/" /></div>

    <form wire:submit="inscrire" class="flex-1 px-6 pb-9 pt-6">
        <div class="mb-6">
            <h1 class="mb-1.5 font-display text-[26px] font-bold text-brand-dark">Créer un compte</h1>
            <p class="text-sm text-slate-500">Rejoignez BookSpace dès aujourd'hui</p>
        </div>

        <x-erreur />

        <div class="mb-4 flex gap-2.5">
            @foreach (['client' => 'Client', 'gerant' => 'Gérant'] as $valeur => $libelle)
                <button type="button" wire:click="$set('role', '{{ $valeur }}')" @class([
                    'flex-1 rounded-xl p-3 text-[13px] font-semibold',
                    'border-2 border-brand bg-brand-50 text-brand' => $role === $valeur,
                    'border-[1.5px] border-slate-200 bg-white text-slate-500' => $role !== $valeur,
                ]) aria-pressed="{{ $role === $valeur ? 'true' : 'false' }}">
                    {{ $libelle }}
                </button>
            @endforeach
        </div>

        <label class="mb-4 block">
            <span class="etiquette">NOM COMPLET</span>
            <input type="text" wire:model="name" class="champ" placeholder="Konan Yao Marcel" autocomplete="name" required>
        </label>

        @if ($role === 'gerant')
            <label class="mb-4 block">
                <span class="etiquette">RAISON SOCIALE</span>
                <input type="text" wire:model="raison_sociale" class="champ" placeholder="Studio Harmonie" autocomplete="organization" required>
            </label>
        @endif

        <label class="mb-4 block">
            <span class="etiquette">NUMÉRO DE TÉLÉPHONE</span>
            <input type="tel" wire:model="telephone" class="champ" placeholder="+226 70 00 00 00" autocomplete="tel" required>
        </label>

        <label class="mb-4 block">
            <span class="etiquette">EMAIL (optionnel)</span>
            <input type="email" wire:model="email" class="champ" placeholder="email@exemple.com" autocomplete="email">
        </label>

        <label class="mb-4 block">
            <span class="etiquette">MOT DE PASSE</span>
            <input type="password" wire:model="password" class="champ" placeholder="Min. 8 caractères" autocomplete="new-password" minlength="8" required>
        </label>

        <label class="mb-5 mt-2 flex items-start gap-2">
            <input type="checkbox" wire:model="accepte" class="mt-0.5 accent-brand">
            <span class="text-xs leading-normal text-slate-500">
                J'accepte les <a href="/conditions" class="font-semibold text-brand underline">Conditions d'utilisation</a>
                et la <a href="/confidentialite" class="font-semibold text-brand underline">Politique de confidentialité</a>
            </span>
        </label>

        <button type="submit" class="btn-primaire" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="inscrire">Créer mon compte</span>
            <span wire:loading wire:target="inscrire">Création…</span>
        </button>

        <p class="mt-5 text-center text-[13px] text-slate-500">
            Déjà un compte ? <a href="/connexion" class="font-semibold text-brand">Se connecter</a>
        </p>
    </form>
</div>
