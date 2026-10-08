@php $email = config('bookspace.support.email'); @endphp
{{--
    PROJET DE TEXTE — à faire relire et valider par un juriste avant la mise en
    production (responsable de traitement, durées de conservation, formalités
    auprès de l'autorité de protection des données).
--}}
<x-page-legale titre="Politique de confidentialité" mise-a-jour="2 octobre 2026">
    <p>
        Cette page explique quelles données BookSpace collecte, pourquoi, et comment vous gardez la main dessus.
    </p>

    <h2>1. Données collectées</h2>
    <ul>
        <li><strong>Compte</strong> : nom, numéro de téléphone, adresse e-mail (facultative), mot de passe (conservé sous forme hachée, jamais en clair) ; pour un gérant, la raison sociale.</li>
        <li><strong>Réservations</strong> : espace, date, créneau, montants, demandes d'annulation ou de report, avis et signalements.</li>
        <li><strong>Paiement</strong> : montant, statut et référence de la transaction Mobile Money. BookSpace ne reçoit ni ne conserve votre code secret Mobile Money.</li>
        <li><strong>Espaces</strong> (gérants) : description, photos, adresse et position géographique de l'espace.</li>
        <li><strong>Position</strong> : uniquement si vous l'autorisez, pour trier les espaces par proximité ou calculer un itinéraire. Elle est utilisée sur le moment et n'est pas enregistrée.</li>
        <li><strong>Données techniques</strong> : date de dernière connexion et journal des opérations sensibles (création, paiement, annulation), à des fins de sécurité.</li>
    </ul>

    <h2>2. Utilisation</h2>
    <ul>
        <li>Créer et gérer votre compte, vos réservations et vos paiements.</li>
        <li>Vous prévenir par notification et par SMS : confirmation, rappel, report, annulation, réponse à une demande.</li>
        <li>Traiter les signalements et prévenir la fraude.</li>
        <li>Établir des statistiques d'activité pour les gérants et pour la plateforme.</li>
    </ul>
    <p>BookSpace n'utilise pas vos données à des fins publicitaires et ne les vend pas.</p>

    <h2>3. Partage</h2>
    <ul>
        <li><strong>Entre client et gérant</strong> : le gérant voit le nom et le téléphone du client qui réserve son espace ; le client voit les informations publiques de l'espace.</li>
        <li><strong>Prestataires</strong> : le service de paiement Mobile Money (Yenga) pour l'acompte, le prestataire d'envoi de SMS pour les notifications, et les services de cartographie OpenStreetMap pour les cartes et itinéraires.</li>
        <li><strong>Autorités</strong> : uniquement lorsque la loi l'exige.</li>
    </ul>

    <h2>4. Vos réglages</h2>
    <p>
        Dans <em>Profil &gt; Préférences</em>, vous pouvez désactiver les SMS et les rappels. La géolocalisation se
        gère depuis les autorisations de votre navigateur.
    </p>

    <h2>5. Conservation</h2>
    <p>
        Vos données sont conservées tant que votre compte est actif, puis pendant la durée nécessaire au respect des
        obligations légales et comptables et au traitement d'éventuels litiges.
    </p>

    <h2>6. Sécurité</h2>
    <p>
        L'accès à votre compte est protégé par mot de passe, chaque rôle n'accède qu'aux données qui le concernent et
        les échanges avec les services de paiement sont authentifiés.
    </p>

    <h2>7. Vos droits</h2>
    <p>
        Conformément à la législation burkinabè sur la protection des données à caractère personnel, vous disposez
        d'un droit d'accès, de rectification, d'opposition et de suppression de vos données.
        @if ($email) Pour l'exercer, écrivez à <a href="mailto:{{ $email }}">{{ $email }}</a>. @endif
        Vous pouvez également saisir la Commission de l'Informatique et des Libertés (CIL).
    </p>

    <h2>8. Évolution de cette politique</h2>
    <p>La version en vigueur est celle publiée sur cette page. Voir aussi les <a href="/conditions">Conditions d'utilisation</a>.</p>
</x-page-legale>
