<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Exige sesión admin + uno o más permisos.
 * Uso en rutas: 'permission:users.manage' o 'permission:stores.view,stores.manage'
 */
class AdminPermission implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['bingo', 'permissions']);

        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)->setJSON([
                    'success' => false,
                    'message' => 'Sesión no válida',
                ]);
            }

            return redirect()->to('/signin');
        }

        if (empty($arguments)) {
            return null;
        }

        $needed = [];
        foreach ($arguments as $arg) {
            foreach (explode(',', (string) $arg) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $needed[] = $p;
                }
            }
        }

        if ($needed === [] || bingo_can_any($needed)) {
            return null;
        }

        return bingo_deny_permission_response();
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
