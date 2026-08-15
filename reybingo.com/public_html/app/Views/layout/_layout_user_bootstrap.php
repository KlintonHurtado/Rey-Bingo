<?php
/**
 * Asegura $user e $imagePath en el layout cuando hay sesión activa.
 * Actualiza last_seen_at (máx. 1 vez por minuto) para "usuarios activos al instante".
 */
if (session()->get('logged_in')) {
    if (! isset($user) || ! is_array($user) || $user === []) {
        $layoutUserModel = new \App\Models\UsersModel();
        $user = $layoutUserModel->find(session()->get('id')) ?? [];
    }
    if (! isset($imagePath) || $imagePath === '') {
        helper('bingo');
        $imagePath = function_exists('bingo_user_image_url')
            ? bingo_user_image_url(is_array($user) ? $user : null)
            : (! empty($user['image'])
                ? site_url('uploads/users/' . $user['image'])
                : site_url('assets/img/avatar.jpg'));
    }

    $lastTouch = (int) (session()->get('last_seen_touch') ?? 0);
    $needsMacUpdate = empty($user['last_mac']) || empty($user['last_ip']);
    if (($lastTouch < (time() - 60) || $needsMacUpdate) && ! empty($user['id'])) {
        try {
            if (function_exists('bingo_ensure_users_schema')) {
                bingo_ensure_users_schema();
            }
            $seenModel = new \App\Models\UsersModel();
            $updateData = ['last_seen_at' => date('Y-m-d H:i:s')];
            if (empty($user['last_mac'])) {
                $autoMac = function_exists('bingo_capture_client_mac') ? bingo_capture_client_mac() : '';
                if ($autoMac !== '') {
                    $updateData['last_mac'] = $autoMac;
                    $user['last_mac'] = $autoMac;
                }
            }
            if (empty($user['last_ip'])) {
                $autoIp = (string) (service('request')->getIPAddress() ?: ($_SERVER['REMOTE_ADDR'] ?? ''));
                if ($autoIp !== '') {
                    $updateData['last_ip'] = $autoIp;
                    $user['last_ip'] = $autoIp;
                }
            }
            $seenModel->update((int) $user['id'], $updateData);
            session()->set('last_seen_touch', time());
            $user['last_seen_at'] = date('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            // no bloquear la página
        }
    }
}
