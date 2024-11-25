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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('floor_id')->constrained('floors')->onDelete('cascade');
            $table->string('tenant_number')->unique(); // Unique tenant identifier
            $table->string('name');
            $table->enum('gender', ['male', 'female']);
            $table->string('phone_number')->unique();
            $table->string('email')->unique();
            $table->string('room_number');
            $table->enum('tenant_type', ['buyer', 'tenant']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes(); // Include soft delete support
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
