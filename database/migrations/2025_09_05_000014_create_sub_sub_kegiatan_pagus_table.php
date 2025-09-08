<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_sub_kegiatan_pagus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_sub_kegiatan_id')->constrained('sub_sub_kegiatans')->cascadeOnDelete();
            $table->foreignId('sumber_anggaran_id')->default(1)->constrained('sumber_anggarans')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedBigInteger('pagu')->default(0);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['sub_sub_kegiatan_id', 'tahun', 'sumber_anggaran_id'], 'ssk_pagus_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_sub_kegiatan_pagus');
    }
};
