<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_master');
            $table->unsignedBigInteger('id_yslygi');
            $table->foreign('id_master')->references('id_user')->on('user')->onDelete('cascade');
            $table->foreign('id_yslygi')->references('id_yslygi')->on('yslygi')->onDelete('cascade');
            $table->unique(['id_master', 'id_yslygi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_services');
    }
};
