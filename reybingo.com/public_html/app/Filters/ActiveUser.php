<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Expulsa usuarios baneados / inactivos / eliminados si aún tienen sesión.
 */
class ActiveUser implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('bingo');

        // No interferir en rutas públicas de auth
        $path = trim($request->getUri()->getPath(), '/');
        $public = [
            'signin',
            'signup',
            'restore',
            'cron',
        ];
        foreach ($public as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return null;
            }
        }

        return bingo_enforce_active_session();
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
