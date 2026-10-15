<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion', 255)->nullable();
            $table->decimal('precio', 12, 2);
            $table->unsignedSmallInteger('xnova_id');
            $table->foreign('xnova_id')->references('id')->on('xnovas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
