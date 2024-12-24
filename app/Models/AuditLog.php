<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'auditable_id', 'auditable_type', 'user_id', 'event', 'document_for'
    ];

    // Polymorphic relationship
    public function auditable()
    {
        return $this->morphTo();
    }

    // User relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
