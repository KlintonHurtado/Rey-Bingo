<?php

if (! function_exists('systemGet')) {
    function systemGet(string $key): ?string {
        $db = \Config\Database::connect();
        $system = $db->table('system');

        $result = $system->select('value')->where('key', $key)->get()->getRow();

        $val = $result->value ?? null;
        if ($key === 'name' && (empty($val) || stripos($val, 'Bingo Family') !== false)) {
            return 'Reybingo.com';
        }

        return $val;
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
