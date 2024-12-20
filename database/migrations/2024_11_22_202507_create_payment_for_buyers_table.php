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
        Schema::create('payment_for_buyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade'); // Relation to tenant
            $table->decimal('property_price', 15, 2); // Price of the purchased property
            $table->decimal('utility_fee', 10, 2); // Utility fee for the buyer
            $table->date('start_date');
            $table->string('room_number');
           // $table->enum('utility_status', ['paid', 'unpaid']);
              // Start date, also serving as the due date for the utility fee
            $table->softDeletes(); // Soft delete support
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_for_buyers');
    }
};
