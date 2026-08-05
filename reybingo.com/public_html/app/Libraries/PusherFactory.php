<?php
namespace App\Libraries;

use RuntimeException;

class PusherFactory
{
    /**
     * Cliente para trigger de eventos (cantar bolas).
     * El auth de canales NO usa esto: firma HMAC en PusherAuth.
     */
    public static function make()
    {
        if (! class_exists(\Pusher\Pusher::class)) {
            throw new RuntimeException('Pusher SDK no está instalado (falta vendor/pusher/pusher-php-server).');
        }

        if (! class_exists(\GuzzleHttp\Client::class)) {
            throw new RuntimeException('Falta GuzzleHttp (dependencia de Pusher).');
        }

        $key     = self::envVal('PUSHER_KEY');
        $secret  = self::envVal('PUSHER_SECRET');
        $appId   = self::envVal('PUSHER_APP_ID');
        $cluster = self::envVal('PUSHER_CLUSTER');

        if ($key === '' || $secret === '' || $appId === '' || $cluster === '') {
            throw new RuntimeException('Faltan PUSHER_KEY / PUSHER_SECRET / PUSHER_APP_ID / PUSHER_CLUSTER en .env');
        }

        return new \Pusher\Pusher($key, $secret, $appId, [
            'cluster' => $cluster,
            'useTLS'  => filter_var(env('PUSHER_USETLS', true), FILTER_VALIDATE_BOOL),
        ]);
    }

    private static function envVal(string $name): string
    {
        $v = trim((string) env($name));
        if ($v !== '' && (
            (str_starts_with($v, '"') && str_ends_with($v, '"'))
            || (str_starts_with($v, "'") && str_ends_with($v, "'"))
        )) {
            $v = substr($v, 1, -1);
        }

        return trim($v);
    }
}
