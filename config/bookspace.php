<?php

return [
    // Valeurs de repli si le paramètre n'est pas défini en base (table `parametres`).
    'commission_rate' => env('PLATFORM_COMMISSION_RATE', 10),
    'deposit_rate' => env('BOOKING_DEPOSIT_RATE', 30),
    'annulation_delai_heures' => env('BOOKING_CANCEL_HOURS', 24),

    // Délai (minutes) laissé au client pour régler l'acompte : au-delà, la
    // réservation « en_attente » est annulée automatiquement et le créneau libéré
    // (commande `reservations:expirer`, planifiée toutes les 5 min).
    'reservation_expiration_minutes' => env('RESERVATION_EXPIRE_MINUTES', 30),

    // Délai avant le créneau auquel le rappel SMS (client + gérant) est envoyé.
    'rappel_minutes_avant' => env('RESERVATION_RAPPEL_MINUTES', 30),

    // Passerelle Mobile Money (TDR §10.3). `driver = fake` simule les paiements
    // en développement ; `orange` / `moov` branchent les API opérateur réelles.
    'mobile_money' => [
        'driver' => env('MOMO_DRIVER', 'fake'),
        // Secret partagé pour authentifier le webhook opérateur (signature
        // HMAC-SHA256 du corps brut, en-tête `X-Signature`). Vide en dev/fake :
        // la vérification est alors désactivée.
        'webhook_secret' => env('MOMO_WEBHOOK_SECRET'),
        'orange' => [
            'base_url' => env('ORANGE_MOMO_URL'),
            'client_id' => env('ORANGE_MOMO_CLIENT_ID'),
            'client_secret' => env('ORANGE_MOMO_CLIENT_SECRET'),
            'merchant_key' => env('ORANGE_MOMO_MERCHANT_KEY'),
        ],
        'moov' => [
            'base_url' => env('MOOV_MOMO_URL'),
            'token' => env('MOOV_MOMO_TOKEN'),
            'merchant_id' => env('MOOV_MOMO_MERCHANT_ID'),
        ],
        'yenga' => [
            'group_id' => env('YENGA_GROUP_ID'),
            'project_id' => env('YENGA_PROJECT_ID'),
            'api_key' => env('YENGA_API_KEY'),
            // Secret du webhook IPN Yenga (en-tête X-Webhook-Hash), distinct du
            // webhook_secret générique ci-dessus.
            'webhook_secret' => env('YENGA_WEBHOOK_SECRET'),
        ],
    ],

    // SMS transactionnels (TDR §10.3). `log` écrit dans les logs ; `http` appelle
    // un agrégateur SMS.
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'africastalking' => [
            'username' => env('AT_USERNAME'),
            'api_key' => env('AT_API_KEY'),
            'sender' => env('SMS_SENDER'),
        ],
        'http' => [
            'url' => env('SMS_HTTP_URL'),
            'token' => env('SMS_HTTP_TOKEN'),
            'sender' => env('SMS_SENDER', 'BookSpace'),
        ],
    ],

    // Coordonnées du support affichées dans Profil > Centre d'aide. Un moyen
    // de contact non renseigné n'est pas affiché.
    'support' => [
        'email' => env('SUPPORT_EMAIL', 'contact@bookspace.bf'),
        'telephone' => env('SUPPORT_TELEPHONE'),
        'whatsapp' => env('SUPPORT_WHATSAPP'), // numéro international, ex. +22670000000
        'horaires' => env('SUPPORT_HORAIRES', 'Du lundi au samedi, 8 h – 18 h'),
    ],

    // Disque de stockage des photos d'espaces (local en dev, s3 en prod).
    'photos_disk' => env('PHOTOS_DISK', 'public'),

    // Géolocalisation & itinéraires (OpenStreetMap — sans clé).
    // En prod : héberger sa propre instance Nominatim/OSRM ou passer par un
    // fournisseur avec quota (OpenRouteService…) pour tenir la charge.
    'geo' => [
        'nominatim_url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
        'osrm_url' => env('OSRM_URL', 'https://router.project-osrm.org'),
        'pays' => env('GEO_COUNTRY', 'bf'), // Burkina Faso
        'contact' => env('GEO_CONTACT', 'contact@bookspace.bf'),
    ],
];
