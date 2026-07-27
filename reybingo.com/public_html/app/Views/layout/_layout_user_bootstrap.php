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
        $imagePath = ! empty($user['image'])
            ? site_url('uploads/users/' . $user['image'])
            : site_url('assets/img/avatar.jpg');
    }

    $lastTouch = (int) (session()->get('last_seen_touch') ?? 0);
    if ($lastTouch < (time() - 60) && ! empty($user['id'])) {
        try {
            if (function_exists('bingo_ensure_users_schema')) {
                bingo_ensure_users_schema();
            }
            $seenModel = new \App\Models\UsersModel();
            $seenModel->update((int) $user['id'], ['last_seen_at' => date('Y-m-d H:i:s')]);
            session()->set('last_seen_touch', time());
            $user['last_seen_at'] = date('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            // no bloquear la página
        }
    }
}
