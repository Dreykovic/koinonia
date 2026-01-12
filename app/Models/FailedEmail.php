<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedEmail extends Model
{
    protected $fillable = [
        'mailable_class',
        'recipient_email',
        'recipient_name',
        'subject',
        'error_message',
        'related_model_type',
        'related_model_id',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * Scope pour les emails non résolus.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope pour les emails résolus.
     */
    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Marquer comme résolu.
     */
    public function markAsResolved(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Obtenir le nom lisible de la classe Mailable.
     */
    public function getMailableNameAttribute(): string
    {
        $className = class_basename($this->mailable_class);
        return preg_replace('/([a-z])([A-Z])/', '$1 $2', $className);
    }

    /**
     * Obtenir le modèle lié.
     */
    public function relatedModel()
    {
        if ($this->related_model_type && $this->related_model_id) {
            return $this->related_model_type::find($this->related_model_id);
        }
        return null;
    }
}

