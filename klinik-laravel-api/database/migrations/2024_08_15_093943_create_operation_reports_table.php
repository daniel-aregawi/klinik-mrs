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
        Schema::create('operation_reports', function (Blueprint $table) {
            $table->id();
            $table->longText('operation_details');
            $table->date('date');
            $table->string('patient_email');
            $table->string('doctor_email');
            $table->string('patient_name');
            $table->string('doctor_name');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->unsignedBigInteger('doctor_id'); 
            $table->foreign('doctor_id')
            ->references('id')
            ->on('doctors')
            ->onDelete('cascade');
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
        Schema::dropIfExists('operation_reports');
    }
};
