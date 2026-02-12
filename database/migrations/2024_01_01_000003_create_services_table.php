<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yslygi', function (Blueprint $table) {
            $table->id('id_yslygi');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->text('opisanie');
            $table->string('foto');
            $table->integer('duration_minutes')->default(60);
            $table->unsignedBigInteger('id_kategori')->nullable();
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yslygi');
    }
};
