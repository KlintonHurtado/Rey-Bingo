<?php

if (! function_exists('systemGet')) {
    function systemGet(string $key): ?string {
        $db = \Config\Database::connect();
        $system = $db->table('system');

        $result = $system->select('value')->where('key', $key)->get()->getRow();

        return $result->value ?? null;
    }
}

if (! function_exists('lastGame')) {
    function lastGame(string $field): ?string {
        $db = \Config\Database::connect();

        $lastGame = $db->table('games')->orderBy('date', 'DESC')->get()->getRowArray();

        if (! $lastGame) {
            return null; 
        }

        if ($field === 'total') {
            $cartons = $db->table('cartons')->where('game', $lastGame['id'])->where('user !=', 0)->countAllResults();

            $accumulated = $cartons * $lastGame['price'];
            $total = $accumulated - ($accumulated * systemGet('rateEarnings'));

            return (string) $total;
        }

        return $lastGame[$field] ?? null;
    }
}

if (! function_exists('getLogo')) {
    function getLogo(): string {
        $db = \Config\Database::connect();
        $system = $db->table('system');

        $result = $system->select('value')->where('key', 'logo')->get()->getRow();

        if ($result && !empty($result->value)) {
            return site_url('uploads/system/' . $result->value);
        }

        return site_url('uploads/system/logo.png');
    }
}

if (!function_exists('translate_day')) {
    function translate_day($date) {
        $dateTime = new DateTime($date);

        $dayOfWeek = $dateTime->format('l');
        $month = $dateTime->format('F');
        $day = $dateTime->format('d');
        $hour = $dateTime->format('g');
        $minutes = $dateTime->format('i');
        $ampm = $dateTime->format('a') == 'am' ? 'AM' : 'PM';

        $dayOfWeekTranslation = translate(strtolower($dayOfWeek));
        $monthTranslation = translate(strtolower($month));
        $ampmTranslation = translate($ampm);

        $formattedDate = "{$dayOfWeekTranslation} - {$hour}:{$minutes} {$ampmTranslation}";

        return ucfirst($formattedDate);
    }
}

if (!function_exists('translate_date')) {
    function translate_date($date) {
        $dateTime = new DateTime($date);

        $dayOfWeek = $dateTime->format('l');
        $month = $dateTime->format('F');
        $day = $dateTime->format('d');
        $hour = $dateTime->format('g');
        $minutes = $dateTime->format('i');
        $ampm = $dateTime->format('a') == 'am' ? 'AM' : 'PM';

        $dayOfWeekTranslation = translate(strtolower($dayOfWeek));
        $monthTranslation = translate(strtolower($month));
        $ampmTranslation = translate($ampm);

        $formattedDate = "{$day} {$monthTranslation} {$dateTime->format('Y')}";

        return ucfirst($formattedDate);
    }
}

if (!function_exists('translate_time')) {
    function translate_time($date) {
        $dateTime = new DateTime($date);

        $dayOfWeek = $dateTime->format('l');
        $month = $dateTime->format('F');
        $day = $dateTime->format('d');
        $hour = $dateTime->format('g');
        $minutes = $dateTime->format('i');
        $ampm = $dateTime->format('a') == 'am' ? 'AM' : 'PM';

        $dayOfWeekTranslation = translate(strtolower($dayOfWeek));
        $monthTranslation = translate(strtolower($month));
        $ampmTranslation = translate($ampm);

        $formattedDate = "{$hour}:{$minutes} {$ampmTranslation}";

        return ucfirst($formattedDate);
    }
}

if (! function_exists('paypalCredentials')) {
    /**
     * Credenciales PayPal alineadas con el modo sandbox/production.
     *
     * @return array{env:string,sandbox_client_id:string,production_client_id:string,client_id:string,secret:string}
     */
    function paypalCredentials(): array
    {
        $paypalConfig = json_decode((string) systemGet('paypal'), true);
        $config = is_array($paypalConfig[0] ?? null) ? $paypalConfig[0] : [];

        $env = (string) ($config['mode'] ?? 'production');
        if (! in_array($env, ['sandbox', 'production'], true)) {
            $env = 'production';
        }

        $sandboxClientId = (string) ($config['sandbox_client_id'] ?? systemGet('idPayPal') ?? '');
        $productionClientId = (string) ($config['production_client_id'] ?? systemGet('idPayPal') ?? '');
        $sandboxSecret = (string) ($config['sandbox_secret_key'] ?? systemGet('secretPayPal') ?? '');
        $productionSecret = (string) ($config['production_secret_key'] ?? systemGet('secretPayPal') ?? '');

        $host = (string) (parse_url((string) base_url(), PHP_URL_HOST) ?: '');
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if (ENVIRONMENT === 'development' && $isLocalHost && $sandboxClientId !== '') {
            $env = 'sandbox';
        }

        return [
            'env' => $env,
            'sandbox_client_id' => $sandboxClientId,
            'production_client_id' => $productionClientId,
            'client_id' => $env === 'sandbox' ? $sandboxClientId : $productionClientId,
            'secret' => $env === 'sandbox' ? $sandboxSecret : $productionSecret,
        ];
    }
}

if (! function_exists('bingo_normalize_hostname')) {
    function bingo_normalize_hostname(?string $value): string
    {
        $value = trim(strtolower((string) $value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('#^https?://#i', '', $value);
        $value = explode('/', $value)[0];
        $value = preg_replace('/:\d+$/', '', $value);

        return $value;
    }
}

if (! function_exists('bingo_primary_hostname')) {
    function bingo_primary_hostname(): string
    {
        return bingo_normalize_hostname(parse_url((string) base_url(), PHP_URL_HOST));
    }
}

if (! function_exists('bingo_client_domain')) {
    function bingo_client_domain(): string
    {
        return bingo_normalize_hostname(systemGet('clientDomain'));
    }
}

if (! function_exists('bingo_client_domain_enabled')) {
    function bingo_client_domain_enabled(): bool
    {
        return (int) systemGet('activateClientDomain') === 1 && bingo_client_domain() !== '';
    }
}

if (! function_exists('bingo_request_hostname')) {
    function bingo_request_hostname(?\CodeIgniter\HTTP\RequestInterface $request = null): string
    {
        $request = $request ?? service('request');
        $host = $request->getServer('HTTP_HOST') ?? $request->getUri()->getHost();

        return bingo_normalize_hostname($host);
    }
}

if (! function_exists('bingo_is_development_hostname')) {
    function bingo_is_development_hostname(?string $host = null): bool
    {
        $host = $host ?? bingo_request_hostname();

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}

if (! function_exists('bingo_official_login_host')) {
    function bingo_official_login_host(): string
    {
        if (bingo_client_domain_enabled()) {
            return bingo_client_domain();
        }

        return bingo_primary_hostname();
    }
}

if (! function_exists('bingo_client_login_url')) {
    function bingo_client_login_url(string $path = '/signin'): string
    {
        $host = bingo_official_login_host();
        if ($host === '') {
            return site_url(ltrim($path, '/'));
        }

        $request = service('request');
        $scheme = $request->isSecure() ? 'https' : 'http';

        return rtrim($scheme . '://' . $host, '/') . '/' . ltrim($path, '/');
    }
}

if (! function_exists('bingo_build_url_for_host')) {
    function bingo_build_url_for_host(string $host, string $path = '/', ?string $query = null): string
    {
        $request = service('request');
        $scheme = $request->isSecure() ? 'https' : 'http';
        $url = rtrim($scheme . '://' . bingo_normalize_hostname($host), '/') . '/' . ltrim($path, '/');

        if ($query) {
            $url .= '?' . $query;
        }

        return $url;
    }
}

if (! function_exists('bingo_register_allowed_hostnames')) {
    function bingo_register_allowed_hostnames(): void
    {
        $config = config('App');
        $allowed = [];

        $clientDomain = bingo_client_domain();
        if ($clientDomain !== '' && $clientDomain !== bingo_primary_hostname()) {
            $allowed[] = $clientDomain;
        }

        $config->allowedHostnames = array_values(array_unique($allowed));
    }
}

if (! function_exists('bingo_apply_dynamic_base_url')) {
    function bingo_apply_dynamic_base_url(?\CodeIgniter\HTTP\RequestInterface $request = null): void
    {
        $request = $request ?? service('request');
        $host = bingo_request_hostname($request);
        $clientDomain = bingo_client_domain();

        if ($clientDomain !== '' && $host === $clientDomain) {
            $scheme = $request->isSecure() ? 'https' : 'http';
            config('App')->baseURL = rtrim($scheme . '://' . $clientDomain, '/') . '/';
        }
    }
}

if (! function_exists('bingo_is_public_auth_route')) {
    function bingo_is_public_auth_route(?\CodeIgniter\HTTP\RequestInterface $request = null): bool
    {
        $request = $request ?? service('request');
        $path = '/' . trim($request->getUri()->getPath(), '/');
        if ($path === '//') {
            $path = '/';
        }

        if (in_array($path, ['/', '/signin', '/signup', '/restore'], true)) {
            return true;
        }

        if (str_starts_with($path, '/signup/')) {
            return true;
        }

        if (str_starts_with($path, '/restore/')) {
            return true;
        }

        if (str_starts_with($path, '/verify/')) {
            return true;
        }

        return false;
    }
}

if (! function_exists('bingo_can_authenticate_on_host')) {
    function bingo_can_authenticate_on_host(?string $host = null): bool
    {
        $host = bingo_normalize_hostname($host ?? bingo_request_hostname());

        if (bingo_is_development_hostname($host)) {
            return true;
        }

        if (!bingo_client_domain_enabled()) {
            return $host === bingo_primary_hostname() || $host === bingo_client_domain();
        }

        return $host === bingo_client_domain();
    }
}
