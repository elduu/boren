<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['category_id', 'name'];

    // Relationship with Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function floors()
{
    return $this->hasMany(Floor::class);
}
}
