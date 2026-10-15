<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finanzas_propietarios', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('xnova_id');
            $table->string('nombre', 120);
            $table->string('tipo', 40)->default('propietario');
            $table->string('correo')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('xnova_id')->references('id')->on('xnovas')->cascadeOnDelete();
            $table->unique(['xnova_id', 'nombre']);
        });

        Schema::create('finanzas_cuentas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('xnova_id');
            $table->foreignId('finanzas_propietario_id')->nullable()->constrained('finanzas_propietarios')->nullOnDelete();
            $table->string('nombre', 120);
            $table->string('tipo', 40)->default('banco');
            $table->string('moneda', 3)->default('USD');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('xnova_id')->references('id')->on('xnovas')->cascadeOnDelete();
            $table->unique(['xnova_id', 'nombre']);
        });

        Schema::create('finanzas_ingresos_fijos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('xnova_id');
            $table->foreignId('finanzas_propietario_id')->constrained('finanzas_propietarios')->cascadeOnDelete();
            $table->foreignId('finanzas_cuenta_id')->nullable()->constrained('finanzas_cuentas')->nullOnDelete();
            $table->string('nombre', 140);
            $table->decimal('monto', 12, 2);
            $table->string('frecuencia', 40)->default('mensual');
            $table->unsignedTinyInteger('dia_mes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('xnova_id')->references('id')->on('xnovas')->cascadeOnDelete();
        });

        Schema::create('finanzas_gastos_fijos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('xnova_id');
            $table->foreignId('finanzas_propietario_id')->constrained('finanzas_propietarios')->cascadeOnDelete();
            $table->foreignId('finanzas_cuenta_id')->nullable()->constrained('finanzas_cuentas')->nullOnDelete();
            $table->string('nombre', 140);
            $table->decimal('monto', 12, 2);
            $table->string('categoria', 80)->nullable();
            $table->string('frecuencia', 40)->default('mensual');
            $table->unsignedTinyInteger('dia_mes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('xnova_id')->references('id')->on('xnovas')->cascadeOnDelete();
        });

        Schema::create('finanzas_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('xnova_id');
            $table->foreignId('finanzas_cuenta_id')->constrained('finanzas_cuentas')->cascadeOnDelete();
            $table->foreignId('finanzas_cuenta_destino_id')->nullable()->constrained('finanzas_cuentas')->nullOnDelete();
            $table->foreignId('finanzas_propietario_id')->nullable()->constrained('finanzas_propietarios')->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('descripcion', 180);
            $table->string('categoria', 80)->nullable();
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->timestamps();

            $table->foreign('xnova_id')->references('id')->on('xnovas')->cascadeOnDelete();
            $table->index(['xnova_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finanzas_movimientos');
        Schema::dropIfExists('finanzas_gastos_fijos');
        Schema::dropIfExists('finanzas_ingresos_fijos');
        Schema::dropIfExists('finanzas_cuentas');
        Schema::dropIfExists('finanzas_propietarios');
    }
};
