<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadikan biodata pasien nullable agar registrasi mandiri
     * (role pasien) dapat dibuat tanpa melengkapi biodata.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->enum('gender', ['L', 'P'])->nullable()->change();
            $table->date('birth_date')->nullable()->change();
            $table->string('phone', 20)->nullable()->change();
            $table->text('address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->enum('gender', ['L', 'P'])->nullable(false)->change();
            $table->date('birth_date')->nullable(false)->change();
            $table->string('phone', 20)->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });
    }
};
