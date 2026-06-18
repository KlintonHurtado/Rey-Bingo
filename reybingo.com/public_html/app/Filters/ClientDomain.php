<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ClientDomain implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('system');

        bingo_apply_dynamic_base_url($request);
        bingo_register_allowed_hostnames();

        if (!bingo_client_domain_enabled()) {
            return;
        }

        $clientDomain = bingo_client_domain();
        if ($clientDomain === '') {
            return;
        }

        $host = bingo_request_hostname($request);
        if (bingo_is_development_hostname($host)) {
            return;
        }

        if (!bingo_is_public_auth_route($request)) {
            return;
        }

        if ($host === $clientDomain) {
            return;
        }

        $primaryHost = bingo_primary_hostname();
        if ($host === $primaryHost) {
            $path  = $request->getUri()->getPath() ?: '/';
            $query = $request->getUri()->getQuery();
            $target = bingo_build_url_for_host($clientDomain, $path, $query);

            return redirect()->to($target);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
