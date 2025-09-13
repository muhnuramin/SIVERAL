<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pelaporan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id'); // relasi ke item SSH
            $table->string('bulan', 10); // contoh: jan, feb, dst
            $table->integer('vol')->default(0);
            $table->bigInteger('rupiah')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelaporan');
    }
};
