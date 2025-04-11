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
        Schema::create('appointments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email');
            $table->unsignedBigInteger('doctor_id');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->text('date');
            $table->text('description')->nullable();
            $table->string('payment_status');
            $table->string('order_id');
            $table->string('session_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('general_status');
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
