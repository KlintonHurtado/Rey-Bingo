<?php

if (!function_exists('send_firebase_push_to_all')) {
    function send_firebase_push_to_all($title, $body, $url = '')
    {
        $tokenModel = new \App\Models\FirebaseTokenModel();
        $tokens = $tokenModel->findAll();

        if (empty($tokens)) {
            return false;
        }

        // IMPORTANTE: Aquí debes colocar la Server Key de tu proyecto Firebase
        // Puedes encontrarla en la Consola de Firebase -> Configuración del proyecto -> Cloud Messaging
        $serverKey = systemGet('firebase_server_key') ?: 'PON_TU_SERVER_KEY_AQUI';

        if ($serverKey == 'PON_TU_SERVER_KEY_AQUI') {
            log_message('error', 'Firebase Server Key no configurada. No se enviarán notificaciones push.');
            return false;
        }

        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        
        $registrationIds = array_column($tokens, 'token');

        // FCM permite hasta 1000 tokens por petición, los dividimos si hay más
        $chunks = array_chunk($registrationIds, 1000);
        
        foreach ($chunks as $chunk) {
            $notification = [
                'title' => $title,
                'body' => $body,
                'icon' => base_url('assets/img/logo.png'),
                'sound' => 'default'
            ];

            $extraNotificationData = ["message" => $notification, "moredata" => 'dd'];

            $fcmNotification = [
                'registration_ids' => $chunk,
                'notification' => $notification,
                'data' => $extraNotificationData
            ];

            $headers = [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fcmUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
            $result = curl_exec($ch);
            curl_close($ch);
        }

        return true;
    }
}
