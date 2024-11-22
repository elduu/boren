<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'building_id',
        'category_id',
        'floor_id',
        'tenant_number',
        'name',
        'gender',
        'phone_number',
        'email',
        'room_number',
        'type',
        'status',
    ];

    // Relationships
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
