<?php

/*
 * Messages de validation en français (règles utilisées par BookSpace).
 * Les règles absentes retombent sur les messages anglais du framework.
 */

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'after' => 'Le champ :attribute doit être postérieur à :date.',
    'after_or_equal' => 'Le champ :attribute doit être postérieur ou égal à :date.',
    'array' => 'Le champ :attribute doit être une liste.',
    'between' => [
        'array' => 'Le champ :attribute doit contenir entre :min et :max éléments.',
        'file' => 'Le fichier :attribute doit peser entre :min et :max Ko.',
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string' => 'Le champ :attribute doit contenir entre :min et :max caractères.',
    ],
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'date' => "Le champ :attribute n'est pas une date valide.",
    'date_format' => 'Le champ :attribute ne correspond pas au format :format.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'exists' => 'Le champ :attribute sélectionné est invalide.',
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'Le champ :attribute sélectionné est invalide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'max' => [
        'array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
        'file' => 'Le fichier :attribute ne peut pas dépasser :max Ko.',
        'numeric' => 'Le champ :attribute ne peut pas être supérieur à :max.',
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'min' => [
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'file' => 'Le fichier :attribute doit peser au moins :min Ko.',
        'numeric' => 'Le champ :attribute doit être au moins égal à :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_if' => 'Le champ :attribute est obligatoire.',
    'required_with' => 'Le champ :attribute est obligatoire.',
    'size' => [
        'array' => 'Le champ :attribute doit contenir :size éléments.',
        'numeric' => 'Le champ :attribute doit valoir :size.',
        'string' => 'Le champ :attribute doit contenir :size caractères.',
    ],
    'string' => 'Le champ :attribute doit être un texte.',
    'unique' => 'Ce :attribute est déjà utilisé.',
    'uploaded' => "L'envoi du fichier :attribute a échoué.",

    'attributes' => [
        'name' => 'nom',
        'nom' => 'nom',
        'telephone' => 'numéro de téléphone',
        'email' => 'e-mail',
        'password' => 'mot de passe',
        'raison_sociale' => 'raison sociale',
        'adresse' => 'adresse',
        'ville' => 'ville',
        'tarif_horaire' => 'tarif horaire',
        'equipements' => 'équipements',
        'description' => 'description',
        'motif' => 'motif',
        'date' => 'date',
        'date_debut' => 'créneau',
        'date_fin' => 'fin',
        'duree' => 'durée',
        'duree_heures' => 'durée',
        'note' => 'note',
        'commentaire' => 'commentaire',
        'latitude' => 'position',
        'longitude' => 'position',
        'now' => 'maintenant',
    ],
];
