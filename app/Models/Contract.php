<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'type',
        'status',
        'signing_date',
        'expiring_date',
        'due_date',
    ];

    // Define the relationship to the Tenant model
    public function tenant()
{
    return $this->belongsTo(Tenant::class);
}

    // Define the relationship to the Building model

    // Accessor for formatted dates (optional)
    public function getDueDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }   public function documents()
    {
        return $this->hasMany(Document::class);
    }
}

