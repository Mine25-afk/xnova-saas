<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xnovas', function (Blueprint $table) {
            $table->string('reservacion_uid', 40)->nullable()->after('nombre');
        });

        DB::table('xnovas')
            ->select(['id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $xnova): void {
                DB::table('xnovas')
                    ->where('id', $xnova->id)
                    ->update([
                        'reservacion_uid' => (string) Str::lower(Str::ulid()),
                    ]);
            });

        Schema::table('xnovas', function (Blueprint $table) {
            $table->string('reservacion_uid', 40)->nullable(false)->change();
            $table->unique('reservacion_uid');
        });
    }

    public function down(): void
    {
        Schema::table('xnovas', function (Blueprint $table) {
            $table->dropUnique(['reservacion_uid']);
            $table->dropColumn('reservacion_uid');
        });
    }
};
