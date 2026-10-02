<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Paramètres plateforme (TDR §5.3). Accès mémoïsé : Parametre::valeur('commission_rate').
 */
class Parametre extends Model
{
    protected $primaryKey = 'cle';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['cle', 'valeur', 'libelle'];

    public static function valeur(string $cle, mixed $defaut = null): mixed
    {
        return Cache::rememberForever("parametre:{$cle}", function () use ($cle, $defaut) {
            return static::query()->find($cle)->valeur ?? $defaut;
        });
    }

    public static function definir(string $cle, mixed $valeur, ?string $libelle = null): void
    {
        static::query()->updateOrCreate(
            ['cle' => $cle],
            array_filter(['valeur' => $valeur, 'libelle' => $libelle], fn ($v) => $v !== null),
        );
        Cache::forget("parametre:{$cle}");
    }

    protected static function booted(): void
    {
        static::saved(fn (Parametre $p) => Cache::forget("parametre:{$p->cle}"));
    }
}
