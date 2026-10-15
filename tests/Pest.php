<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Configuración de Pest
|--------------------------------------------------------------------------
|
| Feature: aplicación completa sobre SQLite en memoria (RefreshDatabase).
| Unit: aplicación arrancada (helpers como now()) pero sin base de datos.
|
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

pest()->extend(TestCase::class)->in('Unit');
