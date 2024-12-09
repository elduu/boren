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
        Schema::create('payment_for_tenants', function (Blueprint $table) {
            
                  $table->id();
                  $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade'); // Relation to tenant
                  $table->decimal('unit_price', 10, 2); // Unit price of the property
                  $table->decimal('monthly_paid', 10, 2); // Monthly payment amount
                  $table->decimal('area_m2', 10, 2); // Area in square meters
                  $table->decimal('utility_fee', 10, 2); // Utility fee for the tenant
                  $table->date('payment_made_until')->nullable(); // Date up to which the payment is made
                  $table->date('start_date'); 
                  $table->date('end_date')->nullable();  // Payment start date
                  $table->date('due_date'); 
                //  $table->enum('payment_status', ['paid', 'unpaid','overdue']);
                  $table->softDeletes(); // Soft delete support
                  $table->timestamps();
              });
      }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_for_tenants');
    }
};
