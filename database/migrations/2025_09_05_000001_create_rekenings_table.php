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
        Schema::create('rekenings', function (Blueprint $table) {
            $table->id();
            $table->string('koderekening');
            $table->string('namarekening');
            // Non-standard FK name as requested: id_sub_sub_kegiatan
            $table->unsignedBigInteger('id_sub_sub_kegiatan');
            $table->foreign('id_sub_sub_kegiatan')
            ->references('id')
            ->on('sub_sub_kegiatans')
            ->cascadeOnDelete();
            $table->boolean('stAktif')->default(true);
            $table->timestamps();

            $table->index('id_sub_sub_kegiatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekenings');
    }
};
