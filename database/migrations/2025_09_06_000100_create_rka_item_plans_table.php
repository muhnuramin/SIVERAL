<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rka_item_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_sub_kegiatan_id')->constrained('sub_sub_kegiatans')->cascadeOnDelete();
            $table->foreignId('rekening_ssh_item_id')->constrained('rekening_ssh_items')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            // monthly volumes (can be decimal if needed later; keep integer for now)
            foreach (['jan','feb','mar','apr','mei','jun','jul','agu','sep','okt','nov','des'] as $m) {
                $table->unsignedInteger($m.'_vol')->default(0);
            }
            $table->timestamps();
            $table->unique(['sub_sub_kegiatan_id','rekening_ssh_item_id','tahun'], 'rka_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rka_item_plans');
    }
};
