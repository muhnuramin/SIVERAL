<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sumber_anggaran_pagus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sumber_anggaran_id')->constrained('sumber_anggarans')->cascadeOnDelete();
            $table->integer('tahun');
            $table->decimal('pagu', 20, 2)->default(0);
            $table->timestamps();

            $table->unique(['sumber_anggaran_id', 'tahun'], 'uniq_sumber_tahun');
            $table->index('tahun');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sumber_anggaran_pagus');
    }
};
