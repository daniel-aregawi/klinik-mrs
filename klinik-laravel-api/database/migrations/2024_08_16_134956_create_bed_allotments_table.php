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
        Schema::create('bed_allotments', function (Blueprint $table) {
            $table->id();
            $table->integer('bed_number');
            $table->string('bed_type');
            $table->string('patient_email');
            $table->string('patient_name');
            $table->date('allotment_time');
            $table->date('discharge_time');
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')
                ->references('id')
                ->on('patients')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bed_allotments');
    }
};
