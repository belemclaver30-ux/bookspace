// Formes de données renvoyées par l'API BookSpace.

export type Role = "client" | "gerant" | "admin";

export interface Utilisateur {
  id: number;
  name: string;
  telephone: string;
  email: string | null;
  role: Role;
  avatar: string | null;
  gerant: {
    id: number;
    raison_sociale: string;
    statut_validation: "en_attente" | "valide" | "rejete";
  } | null;
}

export type TypeEspace = "salle_repetition" | "terrain_foot";

export interface Espace {
  id: number;
  type: TypeEspace;
  nom: string;
  description: string | null;
  adresse: string;
  ville: string;
  tarif_horaire: number;
  statut: "en_attente" | "valide" | "rejete" | "suspendu";
  equipements: string[];
  note_moyenne: number;
  nb_avis: number;
  mis_en_avant: boolean;
  position: { lat: number; lng: number } | null;
  distance_km?: number;
  photos?: string[];
  photo_principale?: string | null;
  medias?: { id: number; url: string }[];
  salle?: { capacite: number | null; isolation_phonique: boolean };
  terrain?: { type_pelouse: string | null; eclairage: boolean; dimensions: string | null };
  gerant?: { id: number; raison_sociale: string; verifie: boolean };
  disponibilites?: { jour_semaine: number; heure_ouverture: string; heure_fermeture: string }[];
  avis?: Avis[];
}

export interface Avis {
  id: number;
  note: number;
  commentaire: string | null;
  auteur?: string;
  date: string;
}

export type StatutReservation = "en_attente" | "confirmee" | "annulee" | "terminee";

export interface Reservation {
  id: number;
  reference: string;
  statut: StatutReservation;
  date_debut: string;
  date_fin: string;
  duree_heures: number;
  prix_total: number;
  taux_acompte: number;
  montant_acompte: number;
  montant_commission: number;
  montant_reverse_gerant: number;
  motif_annulation: string | null;
  annulable: boolean;
  reportable?: boolean;
  evaluable?: boolean;
  a_evalue?: boolean;
  mon_avis?: { note: number; commentaire: string | null };
  demande?: DemandeModification | null;
  confirmee_at: string | null;
  created_at: string;
  espace?: Espace;
  client?: { id?: number; name: string; telephone: string } | null;
  // "gerant" = réservation créée manuellement par le gérant (client sans compte).
  origine?: "client" | "gerant";
  paiement?: {
    statut: string;
    mode: string;
    montant: number;
    reference_operateur: string;
    lien_paiement: string | null;
    paye_at: string | null;
  } | null;
}

export interface Creneau {
  heure: string;
  disponible: boolean;
}

export type TypeDemande = "annulation" | "report";
export type StatutDemande = "en_attente" | "acceptee" | "refusee" | "retiree";

export interface DemandeModification {
  id: number;
  type: TypeDemande;
  statut: StatutDemande;
  motif: string | null;
  nouvelle_date_debut: string | null;
  nouvelle_duree_heures?: number | null;
  reponse_gerant: string | null;
  created_at?: string;
  reservation?: Reservation;
  demandeur?: { name: string; telephone: string };
}

export interface NotificationItem {
  id: number;
  type: string;
  titre: string;
  contenu: string;
  lu_at: string | null;
  created_at: string;
}

export interface Paginated<T> {
  data: T[];
  links?: unknown;
  meta?: { current_page: number; last_page: number; total: number };
}
