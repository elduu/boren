<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
    
    class CreateNotificationsTable extends Migration
    {
        public function up()
        {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id(); // Primary key
                $table->string('type'); // e.g., App\Notifications\ContractRenewalNotification
                $table->morphs('notifiable'); // For associating with User or other models
                $table->text('data'); // Stores JSON data for the notification
                $table->timestamp('read_at')->nullable(); // Marks the notification as read
                $table->timestamps(); // Created_at and updated_at
            });
        }
    
        public function down()
        {
            Schema::dropIfExists('notifications');
        }
    }

