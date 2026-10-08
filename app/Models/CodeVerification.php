<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Code à usage unique envoyé par SMS (voir CodeVerificationService). */
class CodeVerification extends Model
{
    protected $table = 'codes_verification';

    protected $fillable = ['user_id', 'objet', 'telephone', 'code_hash', 'tentatives', 'expire_at', 'utilise_at'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'tentatives' => 'integer',
            'expire_at' => 'datetime',
            'utilise_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
