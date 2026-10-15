<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Correos de superadministradores
    |--------------------------------------------------------------------------
    |
    | Usuarios autorizados a entrar al panel de superadministración (gestión de
    | empresas, planes y módulos). Se define con SUPERADMIN_EMAILS (separados
    | por coma) o SUPERADMIN_EMAIL. No hay valor por defecto: sin variable, nadie
    | es superadministrador. La comparación es insensible a mayúsculas.
    |
    */

    'emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SUPERADMIN_EMAILS', (string) env('SUPERADMIN_EMAIL', '')))
    ))),

];
