<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zapis', function (Blueprint $table) {
            $table->id('id_zapis');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('yslygi_id');
            $table->dateTime('date_time');
            $table->unsignedBigInteger('id_master');
            $table->foreign('user_id')->references('id_user')->on('user')->onDelete('cascade');
            $table->foreign('yslygi_id')->references('id_yslygi')->on('yslygi')->onDelete('cascade');
            $table->foreign('id_master')->references('id_user')->on('user')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zapis');
    }
};
