<?php

namespace Config;

use CodeIgniter\Events\Events;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            // Hostinger y otros hostings suelen tener zlib activo; no tumbar la app.
            log_message('warning', 'zlib.output_compression está activo en el servidor.');
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    // Debug Toolbar desactivado (no mostrar barra inferior en el navegador)
});

Events::on('post_controller_constructor', static function (): void {
    if (function_exists('bingo_ensure_games_schema')) {
        bingo_ensure_games_schema();
    }
    if (function_exists('bingo_ensure_deposits_schema')) {
        bingo_ensure_deposits_schema();
    }
    if (function_exists('bingo_ensure_users_schema')) {
        bingo_ensure_users_schema();
    }
    if (function_exists('bingo_ensure_retires_schema')) {
        bingo_ensure_retires_schema();
    }
    if (function_exists('bingo_ensure_system_settings_schema')) {
        bingo_ensure_system_settings_schema();
    }
    if (function_exists('bingo_ensure_roulettes_schema')) {
        bingo_ensure_roulettes_schema();
    }
    if (function_exists('bingo_ensure_low_balance_grants_schema')) {
        bingo_ensure_low_balance_grants_schema();
    }
    if (function_exists('bingo_ensure_affiliate_ggr_schema')) {
        bingo_ensure_affiliate_ggr_schema();
    }
});
