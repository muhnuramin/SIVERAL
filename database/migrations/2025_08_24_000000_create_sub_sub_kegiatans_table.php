<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_sub_kegiatans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sub_kegiatan_id');
            $table->string('kode', 60)->unique();
            $table->string('nama', 255);
            $table->timestamps();

            $table->foreign('sub_kegiatan_id')->references('id')->on('sub_kegiatans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sub_sub_kegiatans');
    }
};
