<?php
namespace App\Controllers;

use App\Libraries\PusherFactory;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class PusherAuth extends ResourceController
{
    protected $format = 'json';

    /**
     * Auth de canales privados.
     * Firma HMAC local (igual que authorizeChannel) sin instanciar el SDK/Guzzle,
     * que en Hostinger suele causar el 500.
     */
    public function auth()
    {
        if (! session()->get('logged_in')) {
            return $this->respond([
                'message' => 'No autenticado',
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $request = $this->request->getPost();
        if (empty($request)) {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                $request = $json;
            }
        }

        $channelName = trim((string) ($request['channel_name'] ?? $this->request->getPost('channel_name') ?? ''));
        $socketId    = trim((string) ($request['socket_id'] ?? $this->request->getPost('socket_id') ?? ''));

        if ($channelName === '' || $socketId === '') {
            log_message('error', 'Pusher auth: faltan channel_name o socket_id');
            return $this->respond(['message' => 'Faltan parámetros'], ResponseInterface::HTTP_BAD_REQUEST);
        }

        if (strpos($channelName, 'private-game-') !== 0) {
            log_message('error', 'Pusher auth: canal no permitido ' . $channelName);
            return $this->respond(['message' => 'Canal no permitido'], ResponseInterface::HTTP_FORBIDDEN);
        }

        if (! preg_match('/\A\d+\.\d+\z/', $socketId)) {
            log_message('error', 'Pusher auth: socket_id inválido ' . $socketId);
            return $this->respond(['message' => 'socket_id inválido'], ResponseInterface::HTTP_BAD_REQUEST);
        }

        try {
            $key     = $this->envVal('PUSHER_KEY');
            $secret  = $this->envVal('PUSHER_SECRET');
            $appId   = $this->envVal('PUSHER_APP_ID');
            $cluster = $this->envVal('PUSHER_CLUSTER');

            if ($key === '' || $secret === '' || $appId === '' || $cluster === '') {
                log_message('error', 'Pusher auth: faltan PUSHER_* en .env');
                return $this->respond([
                    'message' => 'Pusher no configurado en el servidor',
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $signature = hash_hmac('sha256', $socketId . ':' . $channelName, $secret);

            return $this->respond([
                'auth' => $key . ':' . $signature,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Pusher auth error: ' . $e->getMessage());
            return $this->respond([
                'message' => 'Error de autenticación',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function envVal(string $name): string
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
