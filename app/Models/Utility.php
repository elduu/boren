<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Utility extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id',
 
        'utility_fee',
        
        'start_date',
        'end_date',
        'due_date',
        'reason',
        'utility_type',
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
}
