<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('lastname');
            $table->string('name');
            $table->string('firstname')->nullable();
            $table->string('nomber_tel')->unique();
            $table->string('password');
            $table->integer('id_roli')->default(0); // 0=Client, 1=Master, 2=Admin
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
