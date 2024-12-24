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
       // 'status',
        'signing_date',
        'expiring_date',
        'contract_number',
        'due_date',
        'room_number',
    ];

    // Define the relationship to the Tenant model
    public function tenant()
{
    return $this->belongsTo(Tenant::class);
}
public function auditLogs()
{
    return $this->morphMany(AuditLog::class, 'auditable');
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
    public function floor()
{
    return $this->belongsTo(Floor::class); // Assuming each contract belongs to a floor
}
public function getContractStatusAttribute()
{
    $currentDate = now();
    if ($currentDate->lt($this->due_date)) {
        return 'Active';
    } elseif ($currentDate->gte($this->due_date) && $currentDate->lt($this->expiring_date)) {
        return 'Overdue';
    } else {
        return 'Expired';
    }
}
public function category()
{
    return $this->belongsTo(Category::class);
}

}

