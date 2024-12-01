<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes; // Enable soft delete functionality

    protected $fillable = [
        'documentable_id', 'documentable_type', 'file_path', 'document_type', 'document_format', 'contract_id',           // Associated contract ID, if applicable
        'payment_for_tenant_id', 'payment_for_buyer_id','doc_name','doc_size',// Associated payment ID for tenant, if applicable
    ];// 'uploaded_by'
    

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
public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Payment for tenant relationship.
     */
    public function paymentForTenant()
    {
        return $this->belongsTo(PaymentForTenant::class, 'payment_for_tenant_id');
    }
    public function paymentForBuyer()
    {
        return $this->belongsTo(PaymentForBuyer::class, 'payment_for_buyer_id');
    }

}
