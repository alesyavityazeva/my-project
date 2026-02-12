<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_days_off', function (Blueprint $table) {
            $table->id('id_day_off');
            $table->unsignedBigInteger('id_master');
            $table->date('date_off');
            $table->text('reason')->nullable();
            $table->foreign('id_master')->references('id_user')->on('user')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_days_off');
    }
};
