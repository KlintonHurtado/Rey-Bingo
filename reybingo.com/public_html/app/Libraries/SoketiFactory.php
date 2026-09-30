<?php
namespace App\Libraries;

use RuntimeException;

/**
 * SoketiFactory
 *
 * Reemplaza PusherFactory para usar Soketi self-hosted en el VPS.
 * Usa el mismo SDK de PHP de Pusher (pusher/pusher-php-server) porque
 * Soketi implementa 100% el protocolo/API REST de Pusher.
 *
 * Variables de entorno necesarias en .env de CodeIgniter:
 *   SOKETI_KEY     = tu-app-key          (mismo valor que configuras en Soketi)
 *   SOKETI_SECRET  = tu-app-secret       (mismo valor que configuras en Soketi)
 *   SOKETI_APP_ID  = tu-app-id           (mismo valor que configuras en Soketi)
 *   SOKETI_HOST    = 127.0.0.1           (Soketi corre en el mismo VPS)
 *   SOKETI_PORT    = 6001                (puerto default de Soketi)
 *   SOKETI_USETLS  = false               (Nginx hace el TLS, Soketi habla HTTP internamente)
 */
class SoketiFactory
{
    public static function make()
    {
        if (! class_exists(\Pusher\Pusher::class)) {
            throw new RuntimeException(
                'Pusher PHP SDK no instalado. Ejecuta: composer require pusher/pusher-php-server'
            );
        }

        $key    = self::envVal('SOKETI_KEY');
        $secret = self::envVal('SOKETI_SECRET');
        $appId  = self::envVal('SOKETI_APP_ID');
        $host   = self::envVal('SOKETI_HOST')   ?: '127.0.0.1';
        $port   = (int) (self::envVal('SOKETI_PORT') ?: 6001);
        $useTls = filter_var(env('SOKETI_USETLS', false), FILTER_VALIDATE_BOOL);

        if ($key === '' || $secret === '' || $appId === '') {
            throw new RuntimeException(
                'Faltan SOKETI_KEY / SOKETI_SECRET / SOKETI_APP_ID en .env'
            );
        }

        return new \Pusher\Pusher($key, $secret, $appId, [
            'host'    => $host,
            'port'    => $port,
            'scheme'  => $useTls ? 'https' : 'http',
            'useTLS'  => $useTls,
            // Necesario para que el SDK no intente conectar a pusher.com
            'cluster' => '',
        ]);
    }

    private static function envVal(string $name): string
    {
        $v = trim((string) env($name, ''));
        if ($v !== '' && (
            (str_starts_with($v, '"') && str_ends_with($v, '"'))
            || (str_starts_with($v, "'") && str_ends_with($v, "'"))
        )) {
            $v = substr($v, 1, -1);
        }
        return trim($v);
    }
}
