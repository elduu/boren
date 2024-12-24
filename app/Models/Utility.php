<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Utility extends Model
{
    use HasFactory ; 
    use SoftDeletes; 
    protected $fillable = [
        'tenant_id',
        'utility_number',
        'other_fee',
        'electric_bill_fee',
        'generator_bill',
        'water_bill',
        'start_date',
        'end_date',
        'due_date',
        'reason',
        'utility_type',
        'room_number',
    ];
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function tenantType() { return $this->belongsTo(Tenant::class); }
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    public function floor()
{
    return $this->belongsTo(Floor::class); // Assuming each contract belongs to a floor
}
public function auditLogs()
{
    return $this->morphMany(AuditLog::class, 'auditable');
}
public function getUtilityStatusAttribute()
{
    $currentDate = now();
    if ($currentDate->lt($this->due_date)) {
        return 'paid';
    } elseif ($currentDate->gte($this->due_date) && $currentDate->lt($this->end_date)) {
        return 'Overdue';
    } else {
        return 'unpaid';
    }
}
public function category()
{
    return $this->belongsTo(Category::class);
}
public function building()
{
    return $this->belongsTo(Building::class);
}
}
