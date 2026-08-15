<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ajustes propios de Forte Towing
    |--------------------------------------------------------------------------
    |
    | Todo lo que el código necesita en tiempo de ejecución vive acá y no en
    | env() suelto: cuando se cachea la configuración (obligatorio en
    | producción), env() deja de leer el archivo .env y devuelve el default.
    |
    */

    /*
    | Hosts adicionales aceptados en la cabecera Host, además de APP_URL y sus
    | subdominios. Se usan con trustHosts() en bootstrap/app.php.
    | Laravel ignora esta lista en el entorno local y durante las pruebas.
    */
    'hosts_confiables' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('HOSTS_CONFIABLES', ''))),
    )),

    /*
    | Restablecer la contraseña por correo. Si no se define, se deduce del
    | mailer: con "log" o "array" no hay envío real, así que las rutas de
    | /forgot-password no se registran y el enlace del login desaparece solo.
    | Sin esto el usuario vería "te enviamos un enlace" y esperaría en vano.
    */
    'restablecer_password' => (bool) env(
        'RESET_PASSWORD_HABILITADO',
        ! in_array(env('MAIL_MAILER', 'log'), ['log', 'array'], true),
    ),

    /*
    | Vehículos de demostración. Solo en local: el seeder de demo arrastra las
    | 4 cuentas de prueba, que reescriben su contraseña en cada corrida.
    */
    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', false),

    /*
    | Redirección a HTTPS y cabecera HSTS. Activar recién cuando el certificado
    | esté emitido y estable: revertir HSTS lleva tiempo porque el navegador
    | recuerda la instrucción.
    */
    'forzar_https' => (bool) env('FORZAR_HTTPS', false),

];
