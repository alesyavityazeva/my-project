<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('yslygi_id');
            $table->foreign('user_id')->references('id_user')->on('user')->onDelete('cascade');
            $table->foreign('yslygi_id')->references('id_yslygi')->on('yslygi')->onDelete('cascade');
            $table->unique(['user_id', 'yslygi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
