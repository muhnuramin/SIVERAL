<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sumber_anggarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->string('kode', 50)->nullable();
            $table->boolean('stAktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sumber_anggarans');
    }
};
