<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekening_ssh_items', function (Blueprint $table) {
            $table->id();
            $table->string('rekening_kode', 100)->index();
            $table->string('nama');
            $table->string('satuan', 100);
            $table->string('spesifikasi', 100)->nullable();
            $table->decimal('harga', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekening_ssh_items');
    }
};
