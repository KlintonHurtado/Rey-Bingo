<?php
namespace App\Controllers;

use App\Libraries\PusherFactory;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class PusherAuth extends ResourceController
{
    protected $format = 'json';

    public function auth()
    {
        // Agregar logs para debug
        log_message('info', 'PusherAuth::auth called');
        log_message('info', 'POST data: ' . json_encode($this->request->getPost()));
        log_message('info', 'Headers: ' . json_encode($this->request->getHeaders()));
        
        $request = $this->request->getPost();
        $channelName = $request['channel_name'] ?? '';
        $socketId    = $request['socket_id'] ?? '';

        if (empty($channelName) || empty($socketId)) {
            log_message('error', 'Missing channel_name or socket_id');
            return $this->respond(['message' => 'Faltan parámetros'], ResponseInterface::HTTP_BAD_REQUEST);
        }

        if (strpos($channelName, 'private-game-') !== 0) {
            log_message('error', 'Invalid channel: ' . $channelName);
            return $this->respond(['message' => 'Canal no permitido'], ResponseInterface::HTTP_FORBIDDEN);
        }

        try {
            $key = (string) env('PUSHER_KEY');
            $secret = (string) env('PUSHER_SECRET');
            $appId = (string) env('PUSHER_APP_ID');
            $cluster = (string) env('PUSHER_CLUSTER');

            if ($key === '' || $secret === '' || $appId === '' || $cluster === '') {
                log_message('error', 'Pusher auth: faltan PUSHER_KEY/SECRET/APP_ID/CLUSTER en .env');
                return $this->respond([
                    'message' => 'Pusher no configurado en el servidor',
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $pusher = PusherFactory::make();
            $auth = $pusher->authorizeChannel($channelName, $socketId);

            // authorizeChannel puede devolver string JSON o array
            if (is_string($auth)) {
                $decoded = json_decode($auth, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $auth = $decoded;
                }
            }

            log_message('info', 'Auth successful for channel: ' . $channelName);

            return $this->respond($auth);
        } catch (\Throwable $e) {
            log_message('error', 'Pusher auth error: ' . $e->getMessage());
            return $this->respond([
                'message' => 'Error de autenticación',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
