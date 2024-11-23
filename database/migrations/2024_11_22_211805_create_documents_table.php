<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            // Polymorphic relation columns
            $table->nullableMorphs('documentable'); // This will create 'documentable_id' and 'documentable_type'
            
            // File columns
            $table->string('file_path'); // Path to the uploaded document
            $table->enum('document_type', ['payment_receipt', 'lease_agreement', 'tenant_info']); // Type of document
            $table->enum('document_format', ['pdf', 'word', 'image', 'excel']); // Format of the document
            
            // Metadata
           // $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade'); // The user who uploaded it
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
