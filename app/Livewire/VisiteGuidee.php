<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

/**
 * Visite guidée de l'interface, proposée une fois au nouvel utilisateur sur
 * la page d'accueil de son rôle (client : /app, gérant : /gerant). Chaque
 * étape met en lumière un élément marqué `data-visite="…"` ; le placement et
 * l'enchaînement sont gérés côté navigateur (resources/js/visite.js).
 * Proposée une seule fois : marquée vue dès son affichage (relançable à la
 * demande depuis Profil > Aide).
 */
class VisiteGuidee extends Component
{
    /**
     * Comptée comme vue dès son affichage : elle n'est proposée qu'une seule
     * fois, même si l'utilisateur quitte la page en cours de visite.
     */
    public function mount(): void
    {
        auth()->user()->forceFill(['visite_terminee_at' => now()])->save();
    }

    /** Fin ou « Passer » : rien à enregistrer de plus (déjà fait à l'affichage). */
    public function terminer(): void
    {
        $this->skipRender();
    }

    /** Page d'accueil du rôle où la visite démarre, si elle reste à faire. */
    public static function aProposer(?User $user): bool
    {
        return $user
            && $user->visite_terminee_at === null
            && ! $user->estAdmin()
            && request()->is(ltrim($user->accueil(), '/'));
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.visite-guidee', [
            'etapes' => $user->estGerant() ? $this->etapesGerant($user) : $this->etapesClient($user),
        ]);
    }

    /**
     * Une étape : titre, texte, `cibles` (valeurs de `data-visite`, la première
     * visible est mise en lumière ; aucune = bulle centrée), `menu` (ouvrir le
     * tiroir de navigation sur mobile pour montrer l'entrée) et `lien` final.
     *
     * @return list<array{titre: string, texte: string, cibles?: list<string>, lien?: array{libelle: string, url: string}}>
     */
    private function etapesClient(User $user): array
    {
        return [
            [
                'titre' => 'Bienvenue sur BookSpace, ' . $this->prenom($user) . ' !',
                'texte' => 'Réservez un studio de répétition ou un terrain de foot à Ouagadougou en quelques minutes. Faisons le tour de l’application ensemble.',
                'illustration' => 'accueil',
            ],
            [
                'titre' => 'Cherchez un espace',
                'texte' => 'Tapez le nom d’un studio, d’un terrain ou d’un quartier : les résultats s’affichent au fur et à mesure.',
                'cibles' => ['recherche'],
            ],
            [
                'titre' => 'Filtrez par type',
                'texte' => 'Salle de répétition ou terrain de football : n’affichez que ce qui vous intéresse.',
                'cibles' => ['filtres'],
            ],
            [
                'titre' => 'Près de chez vous',
                'texte' => 'Activez « Près de moi » pour classer les espaces du plus proche au plus éloigné. Votre position n’est jamais partagée.',
                'cibles' => ['proximite'],
            ],
            [
                'titre' => 'Découvrez un espace',
                'texte' => 'Touchez une carte pour voir les photos, le tarif, les équipements, l’itinéraire et les créneaux libres, puis réservez.',
                'cibles' => ['espace'],
            ],
            [
                'titre' => 'Vos réservations',
                'texte' => 'Payez l’acompte par Mobile Money, suivez vos réservations, demandez un report ou une annulation, et laissez un avis.',
                'cibles' => ['nav-reservations'],
            ],
            [
                'titre' => 'Échangez avec le gérant',
                'texte' => 'Une question sur votre créneau ? Écrivez directement au gérant depuis votre réservation.',
                'cibles' => ['nav-messages'],
            ],
            [
                'titre' => 'Restez informé',
                'texte' => 'Confirmations, rappels avant votre créneau et réponses des gérants arrivent ici — et par SMS.',
                'cibles' => ['nav-notifications', 'cloche'],
            ],
            [
                'titre' => 'Votre profil',
                'texte' => 'Retrouvez vos favoris, réglez vos SMS et contactez l’aide si besoin. Vous pourrez y revoir cette visite.',
                'cibles' => ['nav-profil'],
            ],
            [
                'titre' => 'C’est parti !',
                'texte' => 'Choisissez un espace et réservez votre premier créneau.',
                'illustration' => 'fin',
            ],
        ];
    }

    private function etapesGerant(User $user): array
    {
        $valide = (bool) $user->gerant?->estValide();

        return [
            [
                'titre' => 'Bienvenue, ' . $this->prenom($user) . ' !',
                'texte' => $valide
                    ? 'Votre espace gérant est prêt. Voici comment publier vos espaces et gérer vos réservations.'
                    : 'Votre compte est en cours de validation par l’équipe BookSpace : vous pourrez publier vos espaces dès qu’il sera validé. En attendant, faisons le tour.',
                'illustration' => 'accueil',
            ],
            [
                'titre' => 'Votre tableau de bord',
                'texte' => 'Réservations du jour, chiffre d’affaires et taux d’occupation de vos espaces, en un coup d’œil.',
                'cibles' => ['tableau'],
            ],
            [
                'titre' => 'Vos espaces',
                'texte' => 'Créez la fiche de chaque studio ou terrain : photos, tarif, localisation et horaires d’ouverture. Chaque espace est vérifié avant d’être visible des clients.',
                'cibles' => ['nav-espaces', 'menu'],
                'menu' => true,
            ],
            [
                'titre' => 'Vos réservations',
                'texte' => 'Le calendrier de tous vos créneaux. Ajoutez aussi les réservations prises au téléphone ou sur place, pour éviter les doublons.',
                'cibles' => ['nav-reservations', 'menu'],
                'menu' => true,
            ],
            [
                'titre' => 'Les demandes des clients',
                'texte' => 'Quand un client veut annuler ou déplacer une réservation confirmée, c’est vous qui acceptez ou refusez ici.',
                'cibles' => ['nav-demandes', 'menu'],
                'menu' => true,
            ],
            [
                'titre' => 'Messages',
                'texte' => 'Répondez aux questions de vos clients sur leurs réservations.',
                'cibles' => ['nav-messages', 'menu'],
                'menu' => true,
            ],
            [
                'titre' => 'Recevez vos revenus',
                'texte' => 'Dans votre profil, renseignez votre numéro Orange, Moov ou Telecel Money : BookSpace y verse votre part des acomptes après chaque créneau.',
                'cibles' => ['nav-profil', 'menu'],
                'menu' => true,
            ],
            [
                'titre' => 'Notifications',
                'texte' => 'Nouvelles réservations, paiements reçus et demandes arrivent ici et par SMS.',
                'cibles' => ['cloche'],
            ],
            [
                'titre' => 'À vous de jouer !',
                'texte' => $valide
                    ? 'Commencez par créer la fiche de votre premier espace.'
                    : 'Renseignez dès maintenant votre compte de versement Mobile Money.',
                'illustration' => 'fin',
                'lien' => $valide
                    ? ['libelle' => 'Créer mon premier espace', 'url' => '/gerant/espaces']
                    : ['libelle' => 'Renseigner mon compte', 'url' => '/profil/compte'],
            ],
        ];
    }

    private function prenom(User $user): string
    {
        return (string) str($user->name)->before(' ');
    }
}
