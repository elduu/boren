<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     */
    public function up(): void
    {
     
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            // Polymorphic relation columns
            $table->nullableMorphs('documentable'); // This will create 'documentable_id' and 'documentable_type'
            
            $table->string('uploaded_by')->nullable();
            $table->string('file_path'); // Path to the uploaded document
            $table->enum('document_type', ['payment_receipt', 'lease_agreement', 'tenant_info']); // Type of document
            $table->enum('document_format', ['pdf', 'word', 'image', 'excel']);
            $table->string('doc_name')->nullable(); // Stores original file name
            $table->unsignedBigInteger('doc_size')->nullable(); // Format of the document
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('cascade'); // Make contract_id nullable
            $table->foreignId('payment_for_tenant_id')->nullable()->constrained('Payment_for_tenants')->onDelete('cascade'); // Nullable foreign key
            $table->foreignId('payment_for_buyer_id')->nullable()->constrained('payment_for_buyers')->onDelete('cascade'); // Nullable foreign key>foreignId('uploaded_by')->constrained('users')->onDelete('cascade'); // The user who uploaded it
            $table->timestamps();
            $table->softDeletes(); 

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
