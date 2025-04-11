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
        Schema::create('vaccines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')
                ->references('id')
                ->on('patients')
                ->onDelete('cascade');
            $table->unsignedBigInteger('given_by');
            $table->foreign('given_by')
                ->references('id')
                ->on('nurses')
                ->onDelete('cascade');
            $table->string('patient_email'); 
            $table->string('nurse_email');
            $table->string('patient_name'); 
            $table->string('nurse_name');
            $table->string('vaccine_name');
            $table->string('serial_number')->nullable();
            $table->string('dose_number');
            $table->date('date_given');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccines');
    }
};
