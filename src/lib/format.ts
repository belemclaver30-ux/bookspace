export function fcfa(montant: number): string {
  return `${Math.round(montant).toLocaleString("fr-FR")} FCFA`;
}

const JOURS = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
const MOIS = [
  "janv.", "févr.", "mars", "avr.", "mai", "juin",
  "juil.", "août", "sept.", "oct.", "nov.", "déc.",
];

export function dateLongue(iso: string): string {
  const d = new Date(iso);
  return `${JOURS[d.getDay()]} ${d.getDate()} ${MOIS[d.getMonth()]} ${d.getFullYear()}`;
}

export function heure(iso: string): string {
  return new Date(iso).toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" });
}

export function plageHoraire(debut: string, fin: string): string {
  return `${heure(debut)} – ${heure(fin)}`;
}

export const LABEL_TYPE: Record<string, string> = {
  salle_repetition: "Salle de répétition",
  terrain_foot: "Terrain de football",
};

export const LABEL_STATUT: Record<string, string> = {
  en_attente: "En attente",
  confirmee: "Confirmée",
  annulee: "Annulée",
  terminee: "Terminée",
  valide: "Validé",
  rejete: "Rejeté",
  suspendu: "Suspendu",
};
