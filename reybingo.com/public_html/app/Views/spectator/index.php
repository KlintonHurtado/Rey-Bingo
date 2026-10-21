<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= esc($game['description'] ?? 'Partida de Bingo') ?> — Modo Espectador (REY BINGO)</title>
    <link rel="icon" type="image/png" href="<?= site_url('assets/img/logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= site_url('assets/img/apple-touch-icon.png') ?>">
    <meta name="theme-color" content="#0d0622">

    <!-- Fuentes y CSS base -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="<?= site_url('assets/plyr/plyr.css'); ?>?v=<?= APP_VERSION ?>">

    <!-- Hojas de estilos del Bingo -->
    <link rel="stylesheet" href="<?= site_url('assets/css/styles.css'); ?>?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= site_url('assets/css/bingo.css'); ?>?v=<?= APP_VERSION ?>">

    <style>
        /* ==============================================================
           ESTILOS GENERALES Y ADAPTACIÓN AL MODO ESPECTADOR
           ============================================================== */
        :root {
            --spectator-accent: #10b981;
            --spectator-purple: #8b5cf6;
            --spectator-gold: #f59e0b;
        }

        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            background: #0d0622 !important;
            color: #ffffff;
            margin: 0;
            padding: 0;
            overflow: hidden;
            user-select: none;
            -webkit-user-select: none;
        }

        .container-section--spectator {
            display: flex !important;
            flex-direction: column !important;
            height: 100dvh !important;
            max-height: 100dvh !important;
            padding-bottom: 0 !important;
            overflow: hidden !important;
            position: relative;
        }

        /* Banner de Espectador Superior */
        .spectator-header-bar {
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.95) 0%, rgba(5, 150, 105, 0.95) 100%);
            color: #ffffff;
            padding: 6px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
            z-index: 1070;
            flex-shrink: 0;
        }

        .spectator-player-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .spectator-player-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid #ffffff;
            flex-shrink: 0;
        }

        .spectator-live-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 2px 10px;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .spectator-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ef4444;
            animation: pulseDot 1.4s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
        }

        /* Desactivar cualquier interacción sobre las celdas del cartón (Modo Solo Lectura) */
        .container-section--spectator .bingo-carton-number {
            cursor: default !important;
            pointer-events: none !important;
        }

        /* Cartón marcado con estilo vibrante */
        .container-section--spectator .bingo-carton-number.selected {
            background-color: #facc15 !important;
            color: #1a0b36 !important;
            font-weight: 900 !important;
            box-shadow: 0 0 10px rgba(250, 204, 21, 0.6) !important;
        }

        /* Animación suave de aparición de balota */
        .ball-slide-in {
            animation: ballSlideIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes ballSlideIn {
            from {
                opacity: 0;
                transform: scale(0.6) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* BOTÓN FLOTANTE REGÍSTRATE AQUÍ */
        .btn-spectator-cta {
            position: fixed;
            bottom: calc(16px + env(safe-area-inset-bottom, 0px));
            right: calc(16px + env(safe-area-inset-right, 0px));
            z-index: 1060;
            background: linear-gradient(135deg, #f59e0b 0%, #ea580c 50%, #d97706 100%);
            color: #ffffff !important;
            border: 2px solid rgba(255, 255, 255, 0.85);
            border-radius: 50px;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 800;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.5), 0 0 16px rgba(234, 88, 12, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none !important;
            transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
            animation: ctaGlow 2.5s infinite;
        }

        @keyframes ctaGlow {
            0%, 100% {
                box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45), 0 0 14px rgba(234, 88, 12, 0.35);
            }
            50% {
                box-shadow: 0 10px 28px rgba(245, 158, 11, 0.7), 0 0 22px rgba(234, 88, 12, 0.6);
            }
        }

        .btn-spectator-cta:hover {
            transform: scale(1.06) translateY(-2px);
            color: #ffffff !important;
        }

        .btn-spectator-cta:active {
            transform: scale(0.96);
        }

        @media (max-width: 576px) {
            .btn-spectator-cta {
                bottom: calc(12px + env(safe-area-inset-bottom, 0px));
                right: 50%;
                transform: translateX(50%);
                font-size: 0.85rem;
                padding: 9px 18px;
                white-space: nowrap;
            }
            .btn-spectator-cta:hover {
                transform: translateX(50%) scale(1.05);
            }
            .btn-spectator-cta:active {
                transform: translateX(50%) scale(0.95);
            }
        }

        /* Botón de Sonido para el Espectador (arriba a la derecha) */
        .btn-spectator-sound {
            position: absolute;
            top: 10px;
            right: 14px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 1065;
            transition: all 0.2s ease;
        }

        .btn-spectator-sound:hover {
            background: rgba(255, 255, 255, 0.28);
            transform: scale(1.08);
            color: #ffffff;
        }

        /* Banner de partida finalizada */
        #game-finished-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(13, 6, 34, 0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .game-finished-card {
            background: linear-gradient(145deg, rgba(30, 16, 69, 0.95) 0%, rgba(13, 6, 34, 0.98) 100%);
            border: 2px solid rgba(245, 158, 11, 0.5);
            border-radius: 24px;
            max-width: 460px;
            width: 100%;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7), 0 0 30px rgba(245, 158, 11, 0.25);
            animation: zoomIn 0.35s ease;
        }

        @keyframes zoomIn {
            from { transform: scale(0.85); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- 1. BARRA SUPERIOR DE MODO ESPECTADOR -->
    <header class="spectator-header-bar">
        <div class="spectator-player-badge">
            <img src="<?= esc($playerImage) ?>" alt="Avatar" class="spectator-player-avatar">
            <span class="text-truncate">
                Viendo a: <strong><?= esc($playerName) ?></strong>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="spectator-live-pill">
                <span class="spectator-live-dot"></span>
                <span>En Vivo</span>
            </span>
        </div>
    </header>

    <!-- 2. CONTENEDOR PRINCIPAL DEL JUEGO (IDÉNTICO A PLAYING) -->
    <div class="container-section container-section--playing container-section--spectator<?= ($game['type'] == 3 || $game['type'] == 4) ? ' container-section--live-game' : '' ?>">

        <!-- Botón de sonido espectador -->
        <button type="button" class="btn-spectator-sound" id="btn-sound-toggle" title="Activar/Silenciar sonido" aria-label="Sonido">
            <i class="fa-solid fa-volume-high" id="sound-icon"></i>
        </button>

        <!-- TOP SECTION: BALOTAS Y CONTADORES -->
        <div class="top-section <?php if ($game['type'] == 3 || $game['type'] == 4): ?>live<?php endif; ?>">

            <?php if ($game['type'] == 3): ?>
                <div class="ratio ratio-16x9 video-responsive">
                    <iframe src="<?= esc($game['url']); ?>" title="Transmisión en vivo" frameborder="0"
                        allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen>
                    </iframe>
                </div>
            <?php endif; ?>

            <?php if ($game['type'] == 4): ?>
                <video class="w-100"
                    poster="<?= !empty($game['cover']) ? site_url('uploads/covers/' . $game['cover']) : site_url('uploads/covers/image.jpg'); ?>"
                    id="plyr-video-player" playsinline="" controls="">
                    <source
                        src="<?= !empty($game['video']) ? site_url('uploads/videos/' . $game['video']) : site_url('uploads/videos/image.jpg'); ?>"
                        type="video/mp4">
                </video>
            <?php endif; ?>

            <!-- Contador de Balotas -->
            <h6 class="total-balls m-0">
                <small><?= translate('total balls'); ?></small><br />
                <span id="balls-counter"><?= (int)$totalNumbersGenerated ?> - <?= 75 - (int)$totalNumbersGenerated ?></span>
            </h6>

            <!-- Premio Acumulado -->
            <h6 class="total-accumulated m-0">
                <?php
                $hudPrize = bingo_live_prize_hud($game, bingo_count_game_cartons((int) ($game['id'] ?? 0)));
                ?>
                <small id="accumulated-label"><?= esc($hudPrize['label']); ?></small><br />
                <span id="accumulated-counter"><?= systemGet('currency'); ?> <?= esc($hudPrize['amount']); ?></span>
            </h6>

            <!-- Balota Principal Actual -->
            <?php if ($game['type'] != 3 && $game['type'] != 4): ?>
                <?php $class = $lastNumber ? $getClass($lastNumber) : 'STOP'; ?>
                <?php $letter = $lastNumber ? $getClass($lastNumber) : ''; ?>
                <div class="bingo-ball <?= $class ?> size-100" id="last-number">
                    <small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;"><?= $letter ?></small>
                    <span><?= $lastNumber ? $lastNumber : 'STOP'; ?></span>
                </div>
            <?php endif; ?>

            <!-- Historial de Últimas Balotas -->
            <div class="last-numbers">
                <span id="last-five-numbers">
                    <?php foreach ($fourNumbers as $number): ?>
                        <?php $class = in_array($number, $fourNumbers) ? $getClass($number) : ''; ?>
                        <div class="bingo-ball <?= $class ?> size-50">
                            <span><?= $number ?></span>
                        </div>
                    <?php endforeach; ?>
                </span>
            </div>

            <?php if ($game['type'] != 3 && $game['type'] != 4): ?>
                <h6 class="text-white text-center mb-0 mt-1" style="font-size: 0.9rem; font-weight: 700;"><?= esc($game['description']); ?></h6>
            <?php endif; ?>
        </div>

        <!-- CENTER SECTION: CARTONES DEL JUGADOR OBSERVADO -->
        <div class="center-section center-section--playing">
            <?php
            $playingCartonCount = isset($cartons) ? count($cartons) : 0;
            $cartonsSectionClass = 'cartons-section cartons-section--playing';
            if ($playingCartonCount > 1) {
                $cartonsSectionClass .= ' cartons-section--multi';
            }
            ?>
            <div class="<?= $cartonsSectionClass; ?>">
                <?php
                $playingCartonsGridClass = 'content-cartons';
                if ($playingCartonCount === 1) {
                    $playingCartonsGridClass .= ' one-carton';
                } elseif ($playingCartonCount === 2) {
                    $playingCartonsGridClass .= ' two-cartons';
                } elseif ($playingCartonCount === 3) {
                    $playingCartonsGridClass .= ' three-cartons';
                } elseif ($playingCartonCount === 4) {
                    $playingCartonsGridClass .= ' four-cartons';
                } elseif ($playingCartonCount > 4) {
                    $playingCartonsGridClass .= ' many-cartons';
                }
                ?>
                <div class="<?= $playingCartonsGridClass; ?>" id="cartons-container">
                    <?php if (!empty($cartons)): ?>
                        <?php foreach ($cartons as $cartonData): ?>
                            <div class="bingo-border-carton" id="carton-wrapper-<?= $cartonData['cartonId']; ?>">
                                <h6 class="ms-2 mb-1 text-center text-muted carton-serial">
                                    SERIAL: C<?= esc($cartonData['serial']); ?>
                                </h6>
                                <div class="bingo-carton" id="carton-<?= $cartonData['cartonId']; ?>">
                                    <div class="bingo-carton-header B"><span>B</span></div>
                                    <div class="bingo-carton-header I"><span>I</span></div>
                                    <div class="bingo-carton-header N"><span>N</span></div>
                                    <div class="bingo-carton-header G"><span>G</span></div>
                                    <div class="bingo-carton-header O"><span>O</span></div>

                                    <?php foreach ($cartonData['numbers'] as $index => $number): ?>
                                        <?php if ($number['position'] == 13): ?>
                                            <div class="bingo-carton-number data-position-13" data-carton="<?= $cartonData['cartonId']; ?>" data-position="13">⭐️</div>
                                        <?php else: ?>
                                            <?php
                                            $isNumberSelected = in_array((int)$number['number'], $selectedNumbers, true);
                                            ?>
                                            <div class="bingo-carton-number <?= $isNumberSelected ? 'selected' : ''; ?>"
                                                data-carton="<?= $cartonData['cartonId']; ?>"
                                                data-number="<?= $number['number']; ?>"
                                                data-position="<?= $number['position']; ?>">
                                                <?= esc($number['number']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-white-50">
                            <i class="fa-solid fa-ticket-simple fs-1 d-block mb-3 opacity-50"></i>
                            El jugador no tiene cartones asignados en esta partida.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. BOTÓN FLOTANTE "MODALIDADES" (INFERIOR IZQUIERDA) -->
    <?php if (!empty($modalities)): ?>
        <button type="button" class="btn btn-small btn-modalities" id="btn-toggle-modalities" title="Ver Modalidades">
            <img src="<?= site_url('assets/img/modalities.png'); ?>" alt="Modalidades" style="width: 100%; height: 100%; object-fit: contain;">
        </button>

        <!-- PANEL DE MODALIDADES FLOTANTE -->
        <div class="modalities-display-container" id="modalities-display-container" aria-hidden="true">
            <div class="modalities-display-container__toolbar">
                <div class="modalities-display-meta">
                    <h6>Modalidades en Juego</h6>
                    <span class="modalities-display__hint">Premios de la partida</span>
                </div>
                <button type="button" class="modalities-display-close" id="modalities-display-close" aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modalities-display" id="modalities-display">
                <div class="container-cartons-modalities">
                    <?php foreach ($modalities as $index => $modality): ?>
                        <?php
                        $positions = array_map('intval', explode(',', $modality['positions'] ?? ''));
                        $isSing = in_array((int)$modality['id'], $singsModalities, true);
                        ?>
                        <div class="border-carton me-3">
                            <h6 class="title-carton"><?= esc($modality['name']); ?></h6>
                            <div class="carton mb-2">
                                <?php for ($i = 1; $i <= 25; $i++): ?>
                                    <?php
                                    $isMarked = in_array($i, $positions, true);
                                    $showStar = ($isSing && $isMarked) || $i == 13;
                                    ?>
                                    <?php if ($i == 13): ?>
                                        <div class="card-number" data-position="13">⭐️</div>
                                    <?php else: ?>
                                        <div class="card-number <?= $isMarked ? 'modality-sing' : '' ?> <?= $isSing && $isMarked ? 'sing' : '' ?>"
                                            data-position="<?= $i; ?>"><?= $showStar ? '⭐️' : '' ?></div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="modality-prize" id="modality-amount-<?= $modality['id']; ?>">
                                <?= systemGet('currency'); ?> <?= number_format((float)($modality['amount'] ?? 0), 2) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4. BOTÓN "VER GANADORES" (CENTRO INFERIOR) -->
    <button type="button" class="btn" id="btn-ver-ganadores-player" onclick="openWinnersModal();">
        <i class="fa-solid fa-trophy text-warning"></i>
        <span>Tabla de Ganadores</span>
    </button>

    <!-- 5. BOTÓN DESTACADO "REGÍSTRATE AQUÍ" -->
    <button type="button" class="btn-spectator-cta" id="btn-open-register" data-bs-toggle="modal" data-bs-target="#modal-spectator-register">
        <i class="fa-solid fa-crown text-warning"></i>
        <span>¡Juega tú también! Regístrate aquí</span>
    </button>

    <!-- ==============================================================
         MODALES: REGISTRO/LOGIN Y TABLA DE GANADORES
         ============================================================== -->

    <!-- MODAL DE AUTENTICACIÓN / REGISTRO PARA ESPECTADORES -->
    <div class="modal fade" id="modal-spectator-register" tabindex="-1" aria-labelledby="modalSpectatorRegisterLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content text-dark border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <!-- Header con gradiente -->
                <div class="modal-header text-white p-3 p-md-4" style="background: linear-gradient(135deg, #1e1045 0%, #3b1d82 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(245, 158, 11, 0.25); display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-crown text-warning fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalSpectatorRegisterLabel" style="font-size: 1.15rem;">Únete a REY BINGO</h5>
                            <small class="text-white-50">Crea tu cuenta gratis y juega en las próximas partidas</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body p-3 p-md-4">
                    <!-- Botón Continuar con Google -->
                    <div class="mb-3 text-center">
                        <a href="<?= site_url('signup/google') ?>" class="btn w-100 py-2 d-flex align-items-center justify-content-center gap-2 border shadow-sm rounded-3" style="background: #ffffff; color: #374151; font-weight: 600; font-size: 0.95rem;">
                            <svg width="20" height="20" viewBox="0 0 48 48">
                                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                                <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                                <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0124 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 01-4.087 5.57l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                            </svg>
                            <span>Continuar con Google</span>
                        </a>
                    </div>

                    <div class="d-flex align-items-center gap-2 my-3">
                        <hr class="flex-grow-1 my-0">
                        <span class="text-muted small">o completa tu registro</span>
                        <hr class="flex-grow-1 my-0">
                    </div>

                    <!-- Mensajes de Alerta del Formulario -->
                    <div id="register-alert-box"></div>

                    <!-- Formulario Tradicional de Registro -->
                    <form id="spectator-register-form">
                        <?= csrf_field() ?>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Nombre *</label>
                                <input type="text" name="firstname" class="form-control form-control-sm" required placeholder="Tu nombre">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Apellido *</label>
                                <input type="text" name="lastname" class="form-control form-control-sm" required placeholder="Tu apellido">
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Cédula / Documento *</label>
                                <input type="text" name="document" class="form-control form-control-sm" required placeholder="1234567890">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Teléfono / WhatsApp *</label>
                                <input type="text" name="phone" class="form-control form-control-sm" required placeholder="0991234567">
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Usuario *</label>
                                <input type="text" name="username" class="form-control form-control-sm" required placeholder="tu_apodo">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Correo Electrónico *</label>
                                <input type="email" name="email" class="form-control form-control-sm" required placeholder="tu@correo.com">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Contraseña *</label>
                                <input type="password" name="password" class="form-control form-control-sm" required placeholder="Mínimo 6 carácteres">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Confirmar *</label>
                                <input type="password" name="password_confirm" class="form-control form-control-sm" required placeholder="Repite contraseña">
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="accept_terms" value="1" id="spectator-accept-terms" required checked>
                            <label class="form-check-label small text-muted" for="spectator-accept-terms">
                                Acepto los <a href="<?= site_url('terminos') ?>" target="_blank" class="text-primary text-decoration-none fw-semibold">Términos y Condiciones</a>
                            </label>
                        </div>

                        <button type="submit" class="btn w-100 py-2 text-white fw-bold shadow-sm rounded-3" id="btn-submit-register" style="background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);">
                            <i class="fa-solid fa-user-plus me-1" id="register-spinner-icon"></i>
                            <span>Crear Mi Cuenta Gratis</span>
                        </button>
                    </form>

                    <div class="text-center mt-3 pt-2 border-top">
                        <small class="text-muted">¿Ya tienes una cuenta?</small>
                        <a href="<?= site_url('signin') ?>" class="small text-primary fw-bold text-decoration-none ms-1">Inicia sesión aquí</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TABLA DE GANADORES -->
    <div class="modal fade" id="modal-spectator-winners" tabindex="-1" aria-labelledby="modalSpectatorWinnersLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content text-dark border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #1e1045 0%, #3b1d82 100%);">
                    <h5 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2" id="modalSpectatorWinnersLabel">
                        <i class="fa-solid fa-trophy text-warning"></i>
                        <span>Ganadores de la Partida</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-3">
                    <div id="spectator-winners-list">
                        <?php if (!empty($winners)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($winners as $w): ?>
                                    <div class="list-group-item d-flex align-items-center gap-3 px-2 py-2">
                                        <img src="<?= esc($w['image'] ?? site_url('assets/img/avatar.jpg')) ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" alt="Ganador">
                                        <div class="flex-grow-1">
                                            <strong class="d-block text-dark"><?= esc($w['player']) ?></strong>
                                            <span class="badge bg-primary-subtle text-primary fw-semibold"><?= esc($w['modality_name'] ?? $w['modalityName'] ?? 'Bingo') ?></span>
                                            <small class="text-muted ms-1">Cartón: C<?= esc($w['carton']) ?></small>
                                        </div>
                                        <i class="fa-solid fa-medal text-warning fs-4"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fa-solid fa-hourglass-half fs-2 d-block mb-2 opacity-50"></i>
                                Aún no se han registrado ganadores en esta partida.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OVERLAY DE PARTIDA FINALIZADA -->
    <div id="game-finished-overlay">
        <div class="game-finished-card">
            <div style="font-size: 3.5rem;" class="mb-2">🏆</div>
            <h3 class="fw-bold text-white mb-2">¡Partida Finalizada!</h3>
            <p class="text-white-50 mb-4" style="font-size: 0.92rem;">
                La partida ha concluido. Todos los premios y números han sido completados.
            </p>
            <div class="d-flex flex-column gap-2">
                <button type="button" class="btn btn-warning fw-bold py-2 rounded-pill shadow" data-bs-toggle="modal" data-bs-target="#modal-spectator-register">
                    <i class="fa-solid fa-crown me-1"></i> ¡Juega en la próxima partida!
                </button>
                <button type="button" class="btn btn-outline-light rounded-pill py-2" onclick="document.getElementById('game-finished-overlay').style.display = 'none';">
                    Ver cartones finales
                </button>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= site_url('assets/js/pusher.min.js') ?>?v=<?= APP_VERSION ?>"></script>
    <script src="<?= site_url('assets/js/pusher-client.js') ?>?v=<?= APP_VERSION ?>"></script>

    <script>
    (function() {
        const GAME_ID = <?= (int) $game['id'] ?>;
        const SPECTATOR_TOKEN = '<?= esc($token) ?>';
        const PUSHER_KEY = '<?= env("SOKETI_KEY") ?: env("PUSHER_KEY") ?>';
        const SOKETI_HOST = '<?= env("SOKETI_HOST") ?>';
        const SOKETI_PORT = '<?= env("SOKETI_PORT", 443) ?>';
        const AUTH_URL = '<?= site_url("pusher/auth") ?>';
        const STATUS_URL = '<?= site_url("espectador/status/" . $token) ?>';

        let audioEnabled = true;

        // 1. Control de Sonido Espectador
        const btnSound = document.getElementById('btn-sound-toggle');
        const iconSound = document.getElementById('sound-icon');
        if (btnSound && iconSound) {
            btnSound.addEventListener('click', function() {
                audioEnabled = !audioEnabled;
                if (audioEnabled) {
                    iconSound.className = 'fa-solid fa-volume-high';
                    btnSound.style.background = 'rgba(255, 255, 255, 0.15)';
                } else {
                    iconSound.className = 'fa-solid fa-volume-xmark text-danger';
                    btnSound.style.background = 'rgba(239, 68, 68, 0.2)';
                }
            });
        }

        // Sintetizador de audio para balotas
        function playBallSound(num) {
            if (!audioEnabled) return;
            try {
                if ('speechSynthesis' in window) {
                    const letter = getLetterByNumber(num);
                    const utter = new SpeechSynthesisUtterance(letter + ' ' + num);
                    utter.lang = 'es-ES';
                    utter.rate = 1.05;
                    window.speechSynthesis.speak(utter);
                }
            } catch (e) { /* ignore */ }
        }

        function getLetterByNumber(num) {
            const n = parseInt(num, 10);
            if (n <= 15) return 'B';
            if (n <= 30) return 'I';
            if (n <= 45) return 'N';
            if (n <= 60) return 'G';
            return 'O';
        }

        // 2. MODALIDADES PANEL TOGGLE
        const btnModalities = document.getElementById('btn-toggle-modalities');
        const modalitiesContainer = document.getElementById('modalities-display-container');
        const modalitiesClose = document.getElementById('modalities-display-close');

        if (btnModalities && modalitiesContainer) {
            btnModalities.addEventListener('click', function() {
                modalitiesContainer.classList.toggle('is-open');
            });
        }
        if (modalitiesClose && modalitiesContainer) {
            modalitiesClose.addEventListener('click', function() {
                modalitiesContainer.classList.remove('is-open');
            });
        }

        // 3. TABLA DE GANADORES MODAL
        window.openWinnersModal = function() {
            const el = document.getElementById('modal-spectator-winners');
            if (el && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                modal.show();
            }
        };

        // 4. REGISTRO TRADICIONAL VÍA AJAX
        const registerForm = document.getElementById('spectator-register-form');
        const registerAlertBox = document.getElementById('register-alert-box');
        const registerBtn = document.getElementById('btn-submit-register');
        const registerSpinner = document.getElementById('register-spinner-icon');

        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                registerAlertBox.innerHTML = '';
                registerBtn.disabled = true;
                if (registerSpinner) registerSpinner.className = 'fa-solid fa-spinner fa-spin me-1';

                const formData = new FormData(registerForm);

                fetch('<?= site_url("signup/signupSubmit") ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    registerBtn.disabled = false;
                    if (registerSpinner) registerSpinner.className = 'fa-solid fa-user-plus me-1';

                    if (data.success) {
                        registerAlertBox.innerHTML = '<div class="alert alert-success py-2 small mb-3">¡Registro exitoso! Redirigiendo...</div>';
                        setTimeout(() => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.href = '<?= site_url("play") ?>';
                            }
                        }, 1200);
                    } else {
                        let errorMsg = 'Error al registrarte.';
                        if (data.errors && typeof data.errors === 'object') {
                            errorMsg = Object.values(data.errors).join('<br>');
                        } else if (data.error) {
                            errorMsg = data.error;
                        }
                        registerAlertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">${errorMsg}</div>`;
                    }
                })
                .catch(err => {
                    registerBtn.disabled = false;
                    if (registerSpinner) registerSpinner.className = 'fa-solid fa-user-plus me-1';
                    registerAlertBox.innerHTML = '<div class="alert alert-danger py-2 small mb-3">Error de conexión al procesar el registro.</div>';
                });
            });
        }

        // 5. MARCAR NÚMERO EN CARTONES OBSERVADOS
        function markNumberOnCartons(number) {
            const num = parseInt(number, 10);
            if (!num || isNaN(num)) return;

            const cells = document.querySelectorAll(`.container-section--spectator .bingo-carton-number[data-number="${num}"]`);
            cells.forEach(cell => {
                if (!cell.classList.contains('selected')) {
                    cell.classList.add('selected', 'ball-slide-in');
                    setTimeout(() => cell.classList.remove('ball-slide-in'), 500);
                }
            });
        }

        // 6. ACTUALIZAR BALOTA PRINCIPAL E HISTORIAL
        function onNewBall(num, drawnList) {
            const n = parseInt(num, 10);
            if (!n || isNaN(n)) return;

            playBallSound(n);

            // Actualizar número actual
            const lastNumberEl = document.getElementById('last-number');
            if (lastNumberEl) {
                const letter = getLetterByNumber(n);
                lastNumberEl.className = 'bingo-ball ' + letter + ' size-100 ball-slide-in';
                lastNumberEl.innerHTML = `<small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;">${letter}</small><span>${n}</span>`;
            }

            // Marcar en los cartones del jugador observado
            markNumberOnCartons(n);

            // Actualizar contador
            if (Array.isArray(drawnList)) {
                const total = drawnList.length;
                const ballsCounter = document.getElementById('balls-counter');
                if (ballsCounter) {
                    ballsCounter.textContent = total + ' - ' + (75 - total);
                }

                // Actualizar carrusel de últimas balotas
                const lastFiveContainer = document.getElementById('last-five-numbers');
                if (lastFiveContainer) {
                    const recent = drawnList.slice(-5).reverse();
                    let html = '';
                    recent.forEach(b => {
                        const letClass = getLetterByNumber(b);
                        html += `<div class="bingo-ball ${letClass} size-50"><span>${b}</span></div>`;
                    });
                    lastFiveContainer.innerHTML = html;
                }
            }
        }

        // 7. WEBSOCKET: CONEXIÓN EN TIEMPO REAL VÍA PUSHERCLIENT / SOKETI
        if (typeof PusherClient !== 'undefined') {
            const wsClient = new PusherClient(GAME_ID, 0);

            wsClient.on('connection:success', function() {
                console.log('Modo Espectador: Conectado a WebSocket en tiempo real.');
            });

            wsClient.on('game:number_drawn', function(data) {
                console.log('Modo Espectador: Balota cantada', data);
                const ball = data.n || data.number;
                const drawn = data.drawn || data.drawnNumbers;
                if (ball) {
                    onNewBall(ball, drawn);
                }
            });

            wsClient.on('game:bingo_claimed', function(data) {
                console.log('Modo Espectador: Bingo reclamado', data);
                const modName = data.modalityName || data.modality_name || 'Bingo';
                const player = data.player || 'Un jugador';
                Toastify({
                    text: `🎉 ¡${player} cantó ${modName}!`,
                    duration: 4000,
                    gravity: "top",
                    position: "center",
                    style: {
                        background: "linear-gradient(135deg, #f59e0b, #d97706)",
                        borderRadius: "12px",
                        boxShadow: "0 6px 18px rgba(245, 158, 11, 0.4)",
                        fontWeight: "700",
                        fontSize: "1rem"
                    }
                }).showToast();
            });

            wsClient.on('game:bingo_accepted', function(data) {
                console.log('Modo Espectador: Bingo aceptado', data);
                const modName = data.modalityName || data.modality_name || 'Bingo';
                const player = data.player || 'Un jugador';
                Toastify({
                    text: `🏆 ¡BINGO CONFIRMADO! ${player} ganó ${modName}`,
                    duration: 5000,
                    gravity: "top",
                    position: "center",
                    style: {
                        background: "linear-gradient(135deg, #10b981, #059669)",
                        borderRadius: "12px",
                        boxShadow: "0 6px 18px rgba(16, 185, 129, 0.4)",
                        fontWeight: "700",
                        fontSize: "1.05rem"
                    }
                }).showToast();

                if (data.stopped || data.gameCompleted) {
                    showGameFinishedOverlay();
                }
            });

            wsClient.on('game:game_finished', function() {
                showGameFinishedOverlay();
            });

            wsClient.on('game:completed', function() {
                showGameFinishedOverlay();
            });

            wsClient.on('game:live_finalized', function() {
                showGameFinishedOverlay();
            });

            // Inicializar cliente WebSocket
            wsClient.init(
                PUSHER_KEY,
                '<?= env("PUSHER_CLUSTER", "us2") ?>',
                AUTH_URL,
                SOKETI_HOST,
                SOKETI_PORT
            );
        }

        function showGameFinishedOverlay() {
            const overlay = document.getElementById('game-finished-overlay');
            if (overlay) {
                overlay.style.display = 'flex';
            }
        }

        // Si la partida ya estaba finalizada al entrar
        <?php if ($gameIsFinished): ?>
            showGameFinishedOverlay();
        <?php endif; ?>

        // 8. SINCRONIZACIÓN AUTOMÁTICA EN CASO DE RECONEXIÓN
        window.addEventListener('online', syncState);
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                syncState();
            }
        });

        function syncState() {
            fetch(STATUS_URL)
                .then(r => r.json())
                .then(res => {
                    if (res && res.success) {
                        if (Array.isArray(res.drawnNumbers)) {
                            res.drawnNumbers.forEach(n => markNumberOnCartons(n));
                            const ballsCounter = document.getElementById('balls-counter');
                            if (ballsCounter) {
                                ballsCounter.textContent = res.totalNumbersGenerated + ' - ' + (75 - res.totalNumbersGenerated);
                            }
                        }
                        if (res.gameIsFinished) {
                            showGameFinishedOverlay();
                        }
                    }
                })
                .catch(() => {});
        }
    })();
    </script>
</body>
</html>
