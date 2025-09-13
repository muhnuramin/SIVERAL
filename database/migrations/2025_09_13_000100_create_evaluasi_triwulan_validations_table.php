<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluasi_triwulan_validations', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun');
            $table->unsignedTinyInteger('triwulan'); // 1..4
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->unique(['tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_triwulan_validations');
    }
};