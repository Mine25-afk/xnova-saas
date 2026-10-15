<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('Role_Id')->constrained('roles')->cascadeOnDelete();
            $table->string('Accion', 20);
            $table->timestamp('Fecha')->useCurrent();
            $table->foreignId('User_Modifica_Id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['Role_Id', 'Accion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_log');
    }
};
