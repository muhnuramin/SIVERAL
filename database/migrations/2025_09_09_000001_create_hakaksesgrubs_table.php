<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hakaksesgrubs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->json('hak_akses')->nullable();
            $table->timestamps();
        });

        // add relation field to users if not exists
        if (!Schema::hasColumn('users', 'hakaksesgrub_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('hakaksesgrub_id')->nullable()->constrained('hakaksesgrubs')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'hakaksesgrub_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['hakaksesgrub_id']);
                $table->dropColumn('hakaksesgrub_id');
            });
        }
        Schema::dropIfExists('hakaksesgrubs');
    }
};
