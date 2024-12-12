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
        Schema::create('utilities', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->decimal('electric_bill_fee', 10, 2)->default(0.00);
            
            $table->decimal('other_fee', 10, 2)->default(0.00);
            
            $table->decimal('generator_bill', 10, 2)->default(0.00);
            
            $table->decimal('water_bill', 10, 2)->default(0.00);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('due_date')->nullable();
            $table->text('reason')->nullable();
            $table->enum('utility_type', ['electric_bill', 'water', 'generator', 'other'])->nullable();
            $table->softDeletes(); // Soft delete support
            $table->timestamps();    
           });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    
    {
        Schema::dropIfExists('utilities');
    }
};
