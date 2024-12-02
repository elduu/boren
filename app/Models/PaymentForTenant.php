<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentForTenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'unit_price',
        'monthly_paid',
        'area_m2',
        'utility_fee',
        'payment_made_until',
        'start_date',
        'due_date',
        'end_date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    public function floor()
{
    return $this->belongsTo(Floor::class); // Assuming each contract belongs to a floor
}
}