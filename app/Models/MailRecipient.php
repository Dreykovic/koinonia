<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailRecipient extends Model
{
    public const TYPE_REPAIR = 'reparation';

    /**
     * Types de mail dont les destinataires sont configurables (extensible).
     */
    public const TYPES = [
        self::TYPE_REPAIR => 'Demandes de réparation',
    ];

    protected $fillable = [
        'type',
        'email',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
