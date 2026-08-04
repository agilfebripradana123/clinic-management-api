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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            // Relasi ke users
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Nomor Rekam Medis
            $table->string('medical_record_number')->unique();

            // Biodata Pasien
            $table->enum('gender', ['L', 'P']);
            $table->date('birth_date');
            $table->string('phone', 20);
            $table->text('address');
            $table->boolean('is_active')->default(true);

            // Timestamp
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};