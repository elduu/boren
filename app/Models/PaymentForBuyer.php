<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

   
class PaymentForBuyer extends Model 
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'tenant_id',
        'property_price',
        'payment_number',
        'utility_fee',
        'start_date',
        'room_number',
        'activate_status',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
    public function floor()
{
    return $this->belongsTo(Floor::class); // Assuming each contract belongs to a floor
}
}
