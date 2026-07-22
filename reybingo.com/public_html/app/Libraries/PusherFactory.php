<?php
namespace App\Libraries;

use RuntimeException;

class PusherFactory
{
    public static function make()
    {
        if (! class_exists(\Pusher\Pusher::class)) {
            throw new RuntimeException('Pusher SDK no está instalado (falta vendor/pusher/pusher-php-server).');
        }

        $options = [
            'cluster' => env('PUSHER_CLUSTER'),
            'useTLS'  => filter_var(env('PUSHER_USETLS', true), FILTER_VALIDATE_BOOL),
        ];

        return new \Pusher\Pusher(
            env('PUSHER_KEY'),
            env('PUSHER_SECRET'),
            env('PUSHER_APP_ID'),
            $options
        );
    }
}
