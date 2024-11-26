<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes; // Enable soft delete functionality

    protected $fillable = [
        'documentable_id', 'documentable_type', 'file_path', 'document_type', 'document_format'];// 'uploaded_by'
    

    // Polymorphic relationship to Contract, Payment, or Tenant
    public function documentable()
    {
        return $this->morphTo();
    }
    public function tenant()
{
    return $this->belongsTo(Tenant::class, 'documentable_id');
}
public function floor()
{
    return $this->belongsTo(Floor::class); // Assuming each contract belongs to a floor
}

}
