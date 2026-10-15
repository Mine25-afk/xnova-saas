<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('password');
        });

        DB::table('users')->update([
            'active' => true,
        ]);

        Schema::create('User_Logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('User_Id')->constrained('users')->cascadeOnDelete();
            $table->string('Accion', 20);
            $table->timestamp('Fecha')->useCurrent();
            $table->foreignId('User_Modifica_Id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['User_Id', 'Accion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('User_Logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
