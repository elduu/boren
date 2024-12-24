<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('auditable');  // Polymorphic relation for the auditable model (Document, etc.)
            $table->unsignedBigInteger('user_id');  // The ID of the user who performed the action
            $table->string('event');  // Event type (e.g., created, updated, deleted)
            $table->string('document_for')->nullable();  // The document or related entity for the event
            $table->timestamps();  // Created at and updated at
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');  // User foreign key
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}