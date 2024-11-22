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
    }