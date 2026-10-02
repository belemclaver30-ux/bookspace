<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Convertit les photos uploadées en WebP (poids nettement réduit vs JPEG/PNG)
 * et les redimensionne si elles dépassent une largeur raisonnable — les deux
 * gros contributeurs au temps de chargement des fiches espace côté client.
 */
class ImageConversionService
{
    private const LARGEUR_MAX = 1920;
    private const QUALITE = 82;

    public function extension(): string
    {
        return 'webp';
    }

    /** Renvoie les octets WebP de l'image uploadée (redimensionnée si nécessaire). */
    public function versWebp(UploadedFile $fichier): string
    {
        $source = @imagecreatefromstring(file_get_contents($fichier->getRealPath()));

        // Format non décodable par GD (très rare) : on garde le fichier d'origine
        // plutôt que d'échouer l'upload.
        if ($source === false) {
            return file_get_contents($fichier->getRealPath());
        }

        $source = $this->redimensionnerSiNecessaire($source);

        ob_start();
        imagewebp($source, null, self::QUALITE);
        $donnees = ob_get_clean();
        imagedestroy($source);

        return $donnees;
    }

    /** @param \GdImage $source */
    private function redimensionnerSiNecessaire($source)
    {
        $largeur = imagesx($source);
        $hauteur = imagesy($source);

        if ($largeur <= self::LARGEUR_MAX) {
            imagealphablending($source, false);
            imagesavealpha($source, true);

            return $source;
        }

        $nouvelleHauteur = (int) round($hauteur * self::LARGEUR_MAX / $largeur);
        $redim = imagecreatetruecolor(self::LARGEUR_MAX, $nouvelleHauteur);
        imagealphablending($redim, false);
        imagesavealpha($redim, true);
        imagecopyresampled($redim, $source, 0, 0, 0, 0, self::LARGEUR_MAX, $nouvelleHauteur, $largeur, $hauteur);
        imagedestroy($source);

        return $redim;
    }
}
