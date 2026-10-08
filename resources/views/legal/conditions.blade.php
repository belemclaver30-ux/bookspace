@php
    $acompte = heures_lisibles(\App\Models\Parametre::valeur('deposit_rate', config('bookspace.deposit_rate')));
    $commission = heures_lisibles(\App\Models\Parametre::valeur('commission_rate', config('bookspace.commission_rate')));
    $delaiAnnulation = (int) \App\Models\Parametre::valeur('annulation_delai_heures', config('bookspace.annulation_delai_heures'));
    $delaiPaiement = (int) config('bookspace.reservation_expiration_minutes', 30);
    $email = config('bookspace.support.email');
@endphp
{{--
    PROJET DE TEXTE — à faire relire et valider par un juriste avant la mise en
    production (identité de l'éditeur, politique de remboursement, droit applicable).
--}}
<x-page-legale titre="Conditions d'utilisation" mise-a-jour="2 octobre 2026">
    <h2>1. Objet</h2>
    <p>
        BookSpace est une plateforme de réservation d'espaces — salles de répétition et terrains de football — à
        Ouagadougou. Elle met en relation des clients et des gérants d'espaces. Les présentes conditions encadrent
        l'utilisation du service ; créer un compte vaut acceptation de ces conditions.
    </p>

    <h2>2. Comptes</h2>
    <ul>
        <li>L'inscription se fait avec un numéro de téléphone valide, qui sert d'identifiant et reçoit les SMS du service.</li>
        <li>Vous êtes responsable de la confidentialité de votre mot de passe et des actions réalisées depuis votre compte.</li>
        <li>Un compte gérant doit être validé par BookSpace avant de pouvoir publier un espace.</li>
        <li>BookSpace peut suspendre un compte en cas de fraude, d'informations inexactes ou de non-respect de ces conditions.</li>
    </ul>

    <h2>3. Réservation et acompte</h2>
    <ul>
        <li>Une réservation porte sur un espace, une date et un créneau horaire choisis parmi les disponibilités affichées.</li>
        <li>Elle n'est confirmée qu'après paiement en ligne d'un acompte de {{ $acompte }} % du prix, par Mobile Money.</li>
        <li>Sans paiement de l'acompte dans les {{ $delaiPaiement }} minutes, la réservation est annulée automatiquement et le créneau est libéré.</li>
        <li>Le solde est réglé sur place, directement au gérant.</li>
    </ul>

    <h2>4. Annulation et report</h2>
    <ul>
        <li>Tant que l'acompte n'est pas payé, le client peut annuler sa réservation librement.</li>
        <li>Une réservation confirmée ne peut être annulée ou reportée par le client que sur demande, validée par le gérant.</li>
        <li>Le gérant peut annuler ou reporter une réservation en indiquant un motif ; le client en est informé.</li>
        <li>Le remboursement éventuel de l'acompte est traité par BookSpace selon les circonstances de l'annulation, notamment son auteur et le respect d'un délai de {{ $delaiAnnulation }} heures avant le début du créneau.</li>
    </ul>

    <h2>5. Obligations des gérants</h2>
    <ul>
        <li>Publier des informations exactes : description, photos, tarif, équipements, localisation et horaires.</li>
        <li>Tenir leurs disponibilités à jour et honorer les réservations confirmées.</li>
        <li>Accepter le prélèvement d'une commission de {{ $commission }} % sur l'acompte payé en ligne. Aucune commission n'est due sur les réservations saisies manuellement par le gérant.</li>
    </ul>

    <h2>6. Obligations des clients</h2>
    <ul>
        <li>Se présenter à l'heure, respecter les lieux, le matériel et le règlement propre à chaque espace.</li>
        <li>Régler le solde au gérant et répondre des dégradations dont ils sont à l'origine.</li>
    </ul>

    <h2>7. Avis</h2>
    <p>
        Un client peut noter et commenter un espace après une réservation terminée. Les avis doivent refléter une
        expérience réelle et rester courtois ; BookSpace peut retirer un avis injurieux, mensonger ou hors sujet.
    </p>

    <h2>8. Signalements et litiges</h2>
    <p>
        En cas de difficulté liée à une réservation, client et gérant peuvent la signaler depuis
        <em>Profil &gt; Centre d'aide</em>. BookSpace examine le signalement et intervient comme médiateur entre les parties.
    </p>

    <h2>9. Responsabilité</h2>
    <p>
        BookSpace est un intermédiaire technique : la prestation est fournie par le gérant, seul responsable de son
        espace et de son exploitation. BookSpace met tout en œuvre pour assurer la disponibilité du service mais ne peut
        garantir l'absence d'interruption, notamment en cas de panne des réseaux ou des services de paiement.
    </p>

    <h2>10. Données personnelles</h2>
    <p>Le traitement de vos données est décrit dans la <a href="/confidentialite">Politique de confidentialité</a>.</p>

    <h2>11. Modification des conditions</h2>
    <p>
        Ces conditions peuvent évoluer. La version en vigueur est celle publiée sur cette page ; les changements
        importants sont portés à la connaissance des utilisateurs.
    </p>

    <h2>12. Droit applicable et contact</h2>
    <p>
        Les présentes conditions sont soumises au droit burkinabè.
        @if ($email) Pour toute question : <a href="mailto:{{ $email }}">{{ $email }}</a>. @endif
    </p>
</x-page-legale>
