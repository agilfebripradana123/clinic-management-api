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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();

            // Relasi ke booking
            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete()
                ->unique();

            // Hasil Pemeriksaan
            $table->text('complaint');
            $table->text('diagnosis');
            $table->text('treatment');
            $table->text('prescription')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};