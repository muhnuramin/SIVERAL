<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_sub_kegiatan_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_sub_kegiatan_id')->constrained('sub_sub_kegiatans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['sub_sub_kegiatan_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_sub_kegiatan_user');
    }
};
