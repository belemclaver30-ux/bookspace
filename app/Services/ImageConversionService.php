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
    public function versWebp(UploadedFile $fichier, int $largeurMax = self::LARGEUR_MAX): string
    {
        return $this->convertir($fichier, $largeurMax)['donnees'];
    }

    /**
     * Comme `versWebp`, avec les dimensions finales (null si l'image n'a pas
     * pu être décodée et est gardée telle quelle).
     *
     * @return array{donnees: string, largeur: ?int, hauteur: ?int}
     */
    public function convertir(UploadedFile $fichier, int $largeurMax = self::LARGEUR_MAX): array
    {
        $source = @imagecreatefromstring(file_get_contents($fichier->getRealPath()));

        // Format non décodable par GD (très rare) : on garde le fichier d'origine
        // plutôt que d'échouer l'upload.
        if ($source === false) {
            return ['donnees' => file_get_contents($fichier->getRealPath()), 'largeur' => null, 'hauteur' => null];
        }

        $source = $this->redresser($source, $fichier);
        $source = $this->redimensionnerSiNecessaire($source, $largeurMax);

        ob_start();
        imagewebp($source, null, self::QUALITE);
        $donnees = ob_get_clean();
        $dimensions = ['largeur' => imagesx($source), 'hauteur' => imagesy($source)];
        imagedestroy($source);

        return ['donnees' => $donnees] + $dimensions;
    }

    /**
     * Les téléphones enregistrent souvent la photo « couchée » avec une
     * étiquette EXIF d'orientation, que GD ignore : on applique la rotation.
     *
     * @param \GdImage $source
     */
    private function redresser($source, UploadedFile $fichier)
    {
        if (! function_exists('exif_read_data')) {
            return $source;
        }

        $orientation = (int) (@exif_read_data($fichier->getRealPath())['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        if ($angle === 0) {
            return $source;
        }

        $tournee = imagerotate($source, $angle, 0);
        imagedestroy($source);

        return $tournee;
    }

    /** @param \GdImage $source */
    private function redimensionnerSiNecessaire($source, int $largeurMax = self::LARGEUR_MAX)
    {
        $largeur = imagesx($source);
        $hauteur = imagesy($source);

        if ($largeur <= $largeurMax) {
            imagealphablending($source, false);
            imagesavealpha($source, true);

            return $source;
        }

        $nouvelleHauteur = (int) round($hauteur * $largeurMax / $largeur);
        $redim = imagecreatetruecolor($largeurMax, $nouvelleHauteur);
        imagealphablending($redim, false);
        imagesavealpha($redim, true);
        imagecopyresampled($redim, $source, 0, 0, 0, 0, $largeurMax, $nouvelleHauteur, $largeur, $hauteur);
        imagedestroy($source);

        return $redim;
    }
}
