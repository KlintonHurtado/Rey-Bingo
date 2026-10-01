<?php
namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * PusherAuth / SoketiAuth
 *
 * Autentica canales privados para Pusher Cloud O Soketi self-hosted.
 * Usa firma HMAC local (sin instanciar el SDK de Pusher/Guzzle)
 * para evitar errores 500 en entornos con Guzzle no instalado.
 *
 * Funciona con ambos servicios porque implementan el mismo protocolo de auth.
 */
class PusherAuth extends ResourceController
{
    protected $format = 'json';

    public function auth()
    {
        if (! session()->get('logged_in')) {
            return $this->respond(
                ['message' => 'No autenticado'],
                ResponseInterface::HTTP_UNAUTHORIZED
            );
        }

        // Leer parámetros (POST o JSON body)
        $request = $this->request->getPost();
        if (empty($request)) {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                $request = $json;
            }
        }

        $channelName = trim((string) ($request['channel_name'] ?? ''));
        $socketId    = trim((string) ($request['socket_id']    ?? ''));

        if ($channelName === '' || $socketId === '') {
            log_message('error', 'PusherAuth: faltan channel_name o socket_id');
            return $this->respond(
                ['message' => 'Faltan parámetros'],
                ResponseInterface::HTTP_BAD_REQUEST
            );
        }

        $userId = (int) session()->get('id');
        $isGameChannel = strpos($channelName, 'private-game-') === 0;
        $isUserChannel = $userId > 0 && ($channelName === 'private-user-' . $userId);

        if (!$isGameChannel && !$isUserChannel) {
            log_message('error', 'PusherAuth: canal no permitido ' . $channelName);
            return $this->respond(
                ['message' => 'Canal no permitido'],
                ResponseInterface::HTTP_FORBIDDEN
            );
        }

        if (! preg_match('/\A\d+\.\d+\z/', $socketId)) {
            log_message('error', 'PusherAuth: socket_id inválido ' . $socketId);
            return $this->respond(
                ['message' => 'socket_id inválido'],
                ResponseInterface::HTTP_BAD_REQUEST
            );
        }

        try {
            // Detectar automáticamente si usamos Soketi o Pusher Cloud
            $soketiKey = $this->envVal('SOKETI_KEY');
            if ($soketiKey !== '') {
                $key    = $soketiKey;
                $secret = $this->envVal('SOKETI_SECRET');
            } else {
                $key    = $this->envVal('PUSHER_KEY');
                $secret = $this->envVal('PUSHER_SECRET');
            }

            if ($key === '' || $secret === '') {
                log_message('error', 'PusherAuth: faltan credenciales en .env (SOKETI_KEY o PUSHER_KEY)');
                return $this->respond(
                    ['message' => 'Servidor de tiempo real no configurado'],
                    ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // Firma HMAC estándar del protocolo Pusher (compatible con Soketi)
            $signature = hash_hmac('sha256', $socketId . ':' . $channelName, $secret);

            return $this->respond(['auth' => $key . ':' . $signature]);

        } catch (\Throwable $e) {
            log_message('error', 'PusherAuth exception: ' . $e->getMessage());
            return $this->respond(
                [
                    'message' => 'Error de autenticación',
                    'error'   => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                ],
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function envVal(string $name): string
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
