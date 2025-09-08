<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekening_ssh_item_hargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekening_ssh_item_id')->constrained('rekening_ssh_items')->onDelete('cascade');
            $table->integer('tahun')->index();
            $table->decimal('harga', 18,2)->default(0);
            $table->timestamps();
            $table->unique(['rekening_ssh_item_id','tahun']);
        });
        // migrate existing data: move tahun & harga rows
        if (Schema::hasColumn('rekening_ssh_items','tahun')) {
            $rows = DB::table('rekening_ssh_items')->select('id','tahun','harga')->get();
            foreach ($rows as $r) {
                if ($r->tahun) {
                    DB::table('rekening_ssh_item_hargas')->insert([
                        'rekening_ssh_item_id' => $r->id,
                        'tahun' => $r->tahun,
                        'harga' => $r->harga,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        // drop columns after migration
        Schema::table('rekening_ssh_items', function(Blueprint $table){
            if (Schema::hasColumn('rekening_ssh_items','tahun')) $table->dropColumn('tahun');
            if (Schema::hasColumn('rekening_ssh_items','harga')) $table->dropColumn('harga');
        });
    }

    public function down(): void
    {
        // add columns back
        Schema::table('rekening_ssh_items', function(Blueprint $table){
            $table->integer('tahun')->nullable()->index();
            $table->decimal('harga',18,2)->default(0);
        });
        // attempt to restore latest harga per item
        $latest = DB::table('rekening_ssh_item_hargas')
            ->select('rekening_ssh_item_id', DB::raw('MAX(tahun) as tahun'))
            ->groupBy('rekening_ssh_item_id')->get();
        foreach ($latest as $l) {
            $harga = DB::table('rekening_ssh_item_hargas')
                ->where('rekening_ssh_item_id',$l->rekening_ssh_item_id)
                ->where('tahun',$l->tahun)
                ->value('harga');
            DB::table('rekening_ssh_items')->where('id',$l->rekening_ssh_item_id)
                ->update(['tahun'=>$l->tahun,'harga'=>$harga]);
        }
        Schema::dropIfExists('rekening_ssh_item_hargas');
    }
};
