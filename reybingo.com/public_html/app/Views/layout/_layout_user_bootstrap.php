<?php
/**
 * Asegura $user e $imagePath en el layout cuando hay sesión activa.
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
}
