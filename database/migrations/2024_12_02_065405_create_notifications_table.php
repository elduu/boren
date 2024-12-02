<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
    
    class CreateNotificationsTable extends Migration
    {
        public function up()
        {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // e.g., contract_renewal, payment_due
                $table->text('message');
               // $table->unsignedBigInteger('employee_id')->nullable(); // To notify a specific employee
                $table->boolean('is_read')->default(false);
                $table->timestamps();
    
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    
        public function down()
        {
            Schema::dropIfExists('notifications');
        }
    }

