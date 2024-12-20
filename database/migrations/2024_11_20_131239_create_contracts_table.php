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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');  // Tenant relationship
            $table->string('room_number');
            $table->enum('type', ['rental', 'purchased']);  // Rental or Purchased
          //  $table->enum('status', ['active', 'expired','overdue']);  // Active or Expired
            $table->date('signing_date');  // Date when contract is signed
            $table->date('expiring_date');  // Date when contract will expire
            $table->date('due_date');  // Due date (1 month before expiration date)
            $table->softDeletes();  // Soft delete
            $table->timestamps();  // Created and Updated timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
