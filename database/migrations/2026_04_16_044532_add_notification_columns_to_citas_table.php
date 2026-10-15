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
        Schema::table('citas', function (Blueprint $table) {
            $table->timestamp('notificacion_creada_at')->nullable()->after('comentario');
            $table->timestamp('notificacion_reprogramada_at')->nullable()->after('notificacion_creada_at');
            $table->timestamp('notificacion_cancelada_at')->nullable()->after('notificacion_reprogramada_at');
            $table->timestamp('recordatorio_24h_at')->nullable()->after('notificacion_cancelada_at');
            $table->timestamp('recordatorio_2h_at')->nullable()->after('recordatorio_24h_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn([
                'notificacion_creada_at',
                'notificacion_reprogramada_at',
                'notificacion_cancelada_at',
                'recordatorio_24h_at',
                'recordatorio_2h_at',
            ]);
        });
    }
};
