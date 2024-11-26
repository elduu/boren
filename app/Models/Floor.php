<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model

{
    use HasFactory, SoftDeletes;
    
        protected $fillable = ['building_id', 'category_id', 'name'];
    
        // Relationships
        public function building()
        {
            return $this->belongsTo(Building::class);
        }
    
        public function category()
        {
            return $this->belongsTo(Category::class);
        }

public function tenants()
{
    return $this->hasMany(Tenant::class);
}

// Define the relationship between Floor and Contract (through Tenant)
public function contracts()
{
    return $this->hasManyThrough(Contract::class, Tenant::class);
}

// Define the relationship between Floor and Payment (through Tenant)
public function paymentfortenants()
{
    return $this->hasManyThrough(PaymentForTenant::class, Tenant::class);
}

public function paymentforbuyers()
{
    return $this->hasManyThrough(PaymentForBuyer::class, Tenant::class);
}

public function documents()
{
    return $this->hasManyThrough(Document::class, Tenant::class);
}
    }