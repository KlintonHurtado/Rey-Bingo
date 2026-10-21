<link rel="stylesheet" href="<?= site_url('assets/plyr/plyr.css'); ?>?v=<?= APP_VERSION ?>">
<style>
    /* ==========================================================================
       REY BINGO — ESTILOS OFICIALES DE LA SALA DE JUEGO (REPRODUCCIÓN EXACTA)
       ========================================================================== */
    .ball-slide-in {
        animation: ballSlideIn 0.45s ease-out;
    }

    @keyframes ballSlideIn {
        from {
            opacity: 0;
            transform: translateX(-12px) scale(0.85);
        }
        to {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    }

    /* Controles partida en la barra superior */
    .container-section--playing .btn-sliders,
    .container-section--playing .btn-volume,
    .container-section--playing .btn-microphone,
    .container-section--playing .btn-binary {
        position: fixed !important;
        z-index: 1080 !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    .container-section--playing .btn-volume {
        right: 12px !important;
        top: 12px !important;
        width: 50px !important;
        height: 50px !important;
        border-radius: 50% !important;
        background: rgba(255, 255, 255, 0.18) !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
        border: 2px solid rgba(255, 255, 255, 0.4) !important;
        color: #ffffff !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.35rem !important;
        cursor: pointer !important;
        transition: transform 0.2s ease, background 0.2s ease !important;
    }

    .container-section--playing .btn-volume:hover {
        transform: scale(1.08) !important;
        background: rgba(255, 255, 255, 0.3) !important;
    }

    .container-section--playing .btn-share-game {
        position: fixed !important;
        left: 130px !important;
        top: 10px !important;
        width: 55px !important;
        height: 55px !important;
        z-index: 1080 !important;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        border: 2px solid rgba(255, 255, 255, 0.75) !important;
        border-radius: 50% !important;
        color: #ffffff !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.45rem !important;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.45) !important;
        cursor: pointer !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }

    .container-section--playing .btn-share-game:hover {
        transform: scale(1.1) !important;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.65) !important;
        color: #ffffff !important;
    }

    /* Badge del espectador: integrado armónicamente junto a los botones superiores */
    .spectator-hud-badge {
        position: fixed !important;
        left: 195px !important;
        top: 14px !important;
        z-index: 1080 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        background: rgba(16, 185, 129, 0.25) !important;
        border: 1.5px solid #10b981 !important;
        backdrop-filter: blur(10px) !important;
        -webkit-backdrop-filter: blur(10px) !important;
        color: #ffffff !important;
        padding: 6px 14px !important;
        border-radius: 50px !important;
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.35) !important;
        pointer-events: none !important;
    }

    .spectator-hud-badge .spectator-avatar {
        width: 22px !important;
        height: 22px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 1.5px solid #ffffff !important;
    }

    .spectator-hud-badge .live-dot {
        width: 8px !important;
        height: 8px !important;
        border-radius: 50% !important;
        background: #10b981 !important;
        box-shadow: 0 0 8px #10b981 !important;
        animation: pulseSpectatorDot 1.4s infinite !important;
    }

    @keyframes pulseSpectatorDot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.3); }
    }

    /* Pastillas de bolas totales y acumulado a la izquierda */
    .container-section--playing .total-accumulated {
        right: auto !important;
        left: 10px !important;
        top: 120px !important;
        z-index: 1 !important;
    }

    .container-section--playing .total-balls {
        right: auto !important;
        left: 10px !important;
        top: 75px !important;
        z-index: 1 !important;
    }

    .container-section--playing .top-section.live .last-numbers,
    .container-section--playing.container-section--live-game .last-numbers {
        top: 130px !important;
        right: 10px !important;
        z-index: 2 !important;
    }

    @media (max-width: 700px) {
        .container-section--playing .btn-volume {
            right: 8px !important;
            top: 8px !important;
            width: 42px !important;
            height: 42px !important;
            font-size: 1.15rem !important;
        }

        .container-section--playing .btn-share-game {
            left: 95px !important;
            top: 5px !important;
            width: 42px !important;
            height: 42px !important;
            font-size: 1.15rem !important;
        }

        .spectator-hud-badge {
            left: 144px !important;
            top: 8px !important;
            padding: 4px 10px !important;
            font-size: 0.72rem !important;
            max-width: calc(100vw - 200px) !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        .container-section--playing .total-balls {
            left: 5px !important;
            top: 55px !important;
        }

        .container-section--playing .total-accumulated {
            left: 5px !important;
            top: 95px !important;
        }

        .container-section--playing .top-section.live .last-numbers,
        .container-section--playing.container-section--live-game .last-numbers {
            top: 115px !important;
        }
    }

    /* Layout playing: header + cartones a pantalla completa */
    .container-section.container-section--playing {
        display: flex !important;
        flex-direction: column !important;
        height: 100dvh !important;
        max-height: 100dvh !important;
        padding-bottom: 0 !important;
        overflow: hidden !important;
    }

    .container-section--playing > .top-section {
        flex: 0 0 auto !important;
    }

    .container-section--playing .top-section.live {
        flex: 0 0 auto !important;
        padding-top: 3rem !important;
        padding-bottom: 0.5rem !important;
        justify-content: flex-start !important;
        align-items: center !important;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        background: transparent !important;
    }

    /* Video centrado de tamaño proporcional */
    .container-section--playing .top-section.live .video-responsive {
        position: relative !important;
        width: min(100%, 560px) !important;
        max-width: min(92vw, 560px) !important;
        height: auto !important;
        max-height: none !important;
        aspect-ratio: 16 / 9 !important;
        padding-bottom: 0 !important;
        margin: 0 auto !important;
        border-radius: 12px !important;
        background: #0b0620;
        overflow: hidden !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    }

    .container-section--playing .top-section.live .video-responsive iframe {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        border: 0 !important;
    }

    .container-section--playing .top-section.live #plyr-video-player {
        display: block !important;
        width: min(100%, 560px) !important;
        max-width: min(92vw, 560px) !important;
        height: auto !important;
        max-height: none !important;
        aspect-ratio: 16 / 9 !important;
        margin: 0 auto !important;
        object-fit: cover !important;
        border-radius: 12px !important;
        background: #0b0620;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    }

    /* LIVE: columna (video arriba, cartones abajo) */
    .container-section--playing.container-section--live-game {
        display: flex !important;
        flex-direction: column !important;
    }

    .container-section--playing.container-section--live-game .center-section.center-section--playing {
        flex: 1 1 0 !important;
        min-height: 0 !important;
    }

    .container-section--playing.container-section--live-game .cartons-section.cartons-section--playing {
        flex: 1 1 0 !important;
        min-height: 180px !important;
        padding-top: 8px !important;
    }

    .container-section--playing .center-section.center-section--playing {
        flex: 1 1 0 !important;
        min-height: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        justify-content: flex-start !important;
        overflow: visible !important;
        width: 100% !important;
        font-size: inherit;
        font-weight: inherit;
    }

    .container-section--playing .cartons-section.cartons-section--playing {
        flex: 1 1 0 !important;
        min-height: 0 !important;
        height: 0 !important;
        overflow-y: scroll !important;
        overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch !important;
        align-items: flex-start !important;
        justify-content: flex-start !important;
        padding: 10px 10px calc(115px + env(safe-area-inset-bottom, 0px)) !important;
    }

    .container-section--playing .cartons-section.cartons-section--playing::-webkit-scrollbar {
        width: 8px !important;
        display: block !important;
    }

    .container-section--playing .cartons-section.cartons-section--playing::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.3) !important;
        border-radius: 10px !important;
    }

    .container-section--playing .cartons-section.cartons-section--playing::-webkit-scrollbar-thumb:hover {
        background-color: rgba(255, 255, 255, 0.45) !important;
    }

    .container-section--playing .cartons-section.cartons-section--playing::-webkit-scrollbar-track {
        background-color: rgba(0, 0, 0, 0.15) !important;
    }

    .container-section--playing .content-cartons {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 auto !important;
        gap: 14px !important;
    }

    @media (min-width: 701px) {
        .container-section--playing .content-cartons {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)) !important;
            justify-items: center !important;
            max-width: 1200px !important;
        }
    }

    .container-section--playing .bingo-border-carton {
        width: fit-content !important;
        max-width: min(320px, 88vw) !important;
        margin: 0 auto !important;
    }

    .container-section--playing .bingo-border-carton .carton-serial {
        font-size: 0.72rem !important;
        margin-bottom: 4px !important;
    }

    .container-section--playing .bingo-carton {
        width: min(290px, 84vw) !important;
        max-width: 100% !important;
        display: grid !important;
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 4px !important;
        padding: 5px 6px !important;
        box-sizing: border-box !important;
    }

    .container-section--playing .bingo-carton-number,
    .container-section--playing .bingo-carton-header {
        width: 100% !important;
        height: auto !important;
        aspect-ratio: 1;
        min-width: 0 !important;
        font-size: clamp(0.75rem, 2.4vw, 1rem) !important;
        border-radius: 6px !important;
    }

    .container-section--playing .bingo-carton-number.data-position-13,
    .container-section--playing .bingo-carton-number.modality {
        font-size: clamp(0.9rem, 3vw, 1.15rem) !important;
    }

    /* Regla del modo espectador: SOLO LECTURA en los cartones */
    .container-section--playing .bingo-carton-number {
        cursor: default !important;
        pointer-events: none !important;
        user-select: none !important;
    }

    /* Modalidades: patrón flotante en la esquina inferior izquierda */
    .btn-modalities {
        position: fixed !important;
        left: calc(10px + env(safe-area-inset-left, 0px)) !important;
        bottom: calc(10px + env(safe-area-inset-bottom, 0px)) !important;
        width: 80px !important;
        height: 80px !important;
        border-radius: 50% !important;
        background: transparent !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 1055 !important;
        border: none !important;
        transition: transform 0.3s ease !important;
        padding: 0 !important;
        cursor: pointer !important;
    }

    .btn-modalities:hover {
        transform: scale(1.08) !important;
    }

    /* Panel de modalidades */
    .modalities-display-container {
        display: none;
        flex-direction: column;
        position: fixed;
        bottom: 15px;
        left: 0;
        width: 330px;
        max-width: 100%;
        background: linear-gradient(180deg, rgba(24, 10, 84, 0.22) 0%, rgba(33, 16, 95, 0.72) 28%, rgba(55, 29, 146, 0.96) 100%);
        border: none;
        box-shadow: 0 -10px 28px rgba(0, 0, 0, 0.35);
        border-radius: 16px 16px 0 0;
        z-index: 1060;
        height: min(48vh, 420px);
        max-height: min(48vh, 420px);
        justify-content: space-between;
        overflow: hidden;
    }

    .modalities-display-container.is-open {
        display: flex !important;
    }

    .modalities-display-container__toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        padding: 8px 10px 4px;
    }

    .modalities-display-meta {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modalities-display-meta h6 {
        margin: 0;
        font-size: 0.92rem;
        color: #ffffff;
        font-weight: 700;
    }

    .modalities-display-meta .modalities-display__hint {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .modalities-display-close {
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 4px 8px;
    }

    .modalities-display {
        overflow-x: auto;
        overflow-y: hidden;
        padding: 6px 10px 10px;
        scroll-snap-type: x mandatory;
        flex: 1;
    }

    .modalities-display .container-cartons-modalities {
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 0.85rem !important;
        justify-content: flex-start !important;
        align-items: stretch !important;
        width: max-content !important;
        min-width: 100% !important;
        margin: 0 !important;
        padding: 4px 2px 8px !important;
    }

    .modalities-display .border-carton {
        flex: 0 0 auto !important;
        min-width: 200px !important;
        width: 80vw !important;
        max-width: 280px !important;
        scroll-snap-align: center;
        scroll-snap-stop: always;
        background: #ffffff !important;
        border: 2px solid #6236ff !important;
        border-radius: 14px !important;
        padding: 12px 10px 10px !important;
        box-shadow: 0 4px 14px rgba(98, 54, 255, 0.2) !important;
    }

    .modalities-display .border-carton.modality-won {
        background: #f5f5f5 !important;
        border-color: #cccccc !important;
        box-shadow: none !important;
        filter: grayscale(100%) opacity(0.7) !important;
        pointer-events: none;
    }

    .modalities-display .border-carton .modality-name {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 6px;
        text-align: center;
    }

    .modalities-display .border-carton .modality-prize {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        color: #6236ff;
        text-align: center;
        margin-top: 6px;
    }

    .modalities-display .carton {
        width: 100%;
        max-width: 180px;
        margin: 0 auto;
    }

    @media (min-width: 701px) {
        .modalities-display-container {
            width: 450px !important;
            height: 520px !important;
            max-height: calc(100vh - 120px) !important;
            top: auto !important;
            bottom: 20px !important;
            left: 20px !important;
            border-radius: 16px !important;
        }

        .modalities-display-container .modalities-display {
            overflow-x: hidden !important;
            overflow-y: auto !important;
        }

        .modalities-display-container .container-cartons-modalities {
            flex-direction: column !important;
            width: 100% !important;
        }

        .modalities-display-container .border-carton {
            width: 100% !important;
            max-width: 320px !important;
        }
    }

    /* Botón flotante Ver Ganadores */
    #btn-ver-ganadores-player {
        position: fixed !important;
        left: 50% !important;
        right: auto !important;
        bottom: calc(20px + env(safe-area-inset-bottom, 0px)) !important;
        transform: translateX(-50%) !important;
        z-index: 1056 !important;
        border-radius: 50px !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%) !important;
        color: #1a0b36 !important;
        border: 2px solid rgba(255, 255, 255, 0.75) !important;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45), 0 0 12px rgba(255, 193, 7, 0.4) !important;
        padding: 9px 18px !important;
        font-size: 14px !important;
        white-space: nowrap !important;
        max-width: calc(100vw - 180px) !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: inline-flex;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
        cursor: pointer !important;
    }

    #btn-ver-ganadores-player:hover {
        transform: translateX(-50%) scale(1.06) !important;
        box-shadow: 0 6px 22px rgba(0, 0, 0, 0.55), 0 0 15px rgba(255, 193, 7, 0.5) !important;
    }

    /* BOTÓN FLOTANTE: "👑 ¡Juega tú también! Regístrate aquí" */
    .btn-spectator-cta {
        position: fixed !important;
        right: calc(18px + env(safe-area-inset-right, 0px)) !important;
        bottom: calc(18px + env(safe-area-inset-bottom, 0px)) !important;
        z-index: 1060 !important;
        background: linear-gradient(135deg, #f59e0b 0%, #ea580c 50%, #d97706 100%) !important;
        color: #ffffff !important;
        border: 2px solid rgba(255, 255, 255, 0.9) !important;
        border-radius: 50px !important;
        padding: 11px 22px !important;
        font-size: 0.95rem !important;
        font-weight: 800 !important;
        box-shadow: 0 8px 24px rgba(245, 158, 11, 0.55), 0 0 16px rgba(234, 88, 12, 0.4) !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        cursor: pointer !important;
        text-decoration: none !important;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease !important;
        animation: ctaGlow 2.5s infinite !important;
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
        transform: scale(1.06) translateY(-2px) !important;
        color: #ffffff !important;
    }

    .btn-spectator-cta:active {
        transform: scale(0.96) !important;
    }

    @media (max-width: 700px) {
        .btn-spectator-cta {
            bottom: calc(14px + env(safe-area-inset-bottom, 0px)) !important;
            right: 12px !important;
            font-size: 0.82rem !important;
            padding: 8px 14px !important;
        }

        #btn-ver-ganadores-player {
            bottom: calc(65px + env(safe-area-inset-bottom, 0px)) !important;
            font-size: 12px !important;
            padding: 7px 14px !important;
        }
    }
</style>

<div class="container-section container-section--playing<?= ($game['type'] == 3 || $game['type'] == 4) ? ' container-section--live-game' : '' ?>">
    <div class="top-section <?php if ($game['type'] == 3 || $game['type'] == 4): ?>live<?php endif; ?>">
        <!-- Botón de Inicio -->
        <a class="btn btn-small btn-home" href="<?= site_url(); ?>" title="<?= translate('home'); ?>">
            <i class="fa-duotone fa-solid fa-house"></i>
        </a>

        <!-- Botón de Registro / Billetera interactiva -->
        <button type="button" class="btn btn-small btn-wallet" onclick="openSpectatorRegisterModal();" title="Regístrate para jugar">
            <i class="fa-duotone fa-solid fa-wallet"></i>
        </button>

        <!-- Botón Compartir Partida -->
        <button type="button" class="btn btn-small btn-share-game" onclick="openShareGameModal();" title="Compartir Partida" aria-label="Compartir Partida">
            <i class="fa-solid fa-share-nodes"></i>
        </button>

        <!-- Badge Espectador: visualización en vivo -->
        <div class="spectator-hud-badge">
            <span class="live-dot"></span>
            <?php if (!empty($playerImage)): ?>
                <img src="<?= $playerImage; ?>" alt="Avatar" class="spectator-avatar">
            <?php endif; ?>
            <span>Viendo a: <strong><?= esc($playerName); ?></strong></span>
        </div>

        <!-- Botón de Sonido del Espectador -->
        <button type="button" class="btn btn-small btn-volume" id="btn-spectator-sound" onclick="toggleSpectatorAudio();" title="Activar/Silenciar sonido" aria-label="Sonido">
            <i class="fa-duotone fa-solid fa-volume" id="sound-icon"></i>
        </button>

        <!-- Contenedor del video o transmisión en vivo -->
        <?php if ($game['type'] == 3): ?>
            <div class="ratio ratio-16x9 video-responsive">
                <iframe src="<?= $game['url']; ?>" title="YouTube video player" frameborder="0"
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

        <!-- Bolas Totales -->
        <h6 class="total-balls m-0">
            <small><?= translate('total balls'); ?></small><br />
            <span id="balls-counter"><?= $totalNumbersGenerated ?> - <?= max(0, 75 - $totalNumbersGenerated) ?></span>
        </h6>

        <!-- Acumulado Oficial -->
        <h6 class="total-accumulated m-0"><?php
            $hudPrize = bingo_live_prize_hud($game, bingo_count_game_cartons((int) ($game['id'] ?? 0)));
        ?><small id="accumulated-label"><?= esc($hudPrize['label']); ?></small><br />
            <span id="accumulated-counter"><?= systemGet('currency'); ?> <?= esc($hudPrize['amount']); ?></span>
        </h6>

        <!-- Modo Bingo Tradicional: Gran balota central y carrusel de últimas balotas -->
        <?php if ($game['type'] != 3 && $game['type'] != 4): ?>
            <?php $class = $lastNumber ? $getClass($lastNumber) : 'STOP'; ?>
            <?php $letter = $lastNumber ? $getClass($lastNumber) : ''; ?>
            <div class="bingo-ball <?= $class ?> size-100" id="last-number">
                <small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;"><?= $letter ?></small>
                <span><?= $lastNumber ? $lastNumber : 'STOP'; ?></span>
            </div>
        <?php endif; ?>

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
            <h6 class="text-white text-center mb-0"><?= esc($game['description'] ?? ''); ?></h6>
            <h6 class="text-white text-center next-game mb-1 text-uppercase" style="font-size: 0.8rem;"></h6>
        <?php endif; ?>
    </div>

    <!-- SECCIÓN CENTRAL: CARTONES OFICIALES DEL JUGADOR COMPARTIDO -->
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
            <div class="<?= $playingCartonsGridClass; ?>">
                <?php if (isset($cartons) && count($cartons) > 0): ?>
                    <?php foreach ($cartons as $cartonData): ?>
                        <div class="bingo-border-carton" id="card-box-<?= $cartonData['cartonId']; ?>">
                            <h6 class="ms-2 mb-1 text-center text-muted carton-serial">SERIAL: C<?= esc($cartonData['serial']); ?></h6>
                            <div class="bingo-carton" id="carton-<?= $cartonData['cartonId']; ?>">
                                <div class="bingo-carton-header B"><span>B</span></div>
                                <div class="bingo-carton-header I"><span>I</span></div>
                                <div class="bingo-carton-header N"><span>N</span></div>
                                <div class="bingo-carton-header G"><span>G</span></div>
                                <div class="bingo-carton-header O"><span>O</span></div>

                                <?php foreach ($cartonData['numbers'] as $index => $number): ?>
                                    <?php
                                    $numVal = (int) $number['number'];
                                    $isMarked = ($number['status'] == 1) || in_array($numVal, $selectedNumbers);
                                    $classes = [];
                                    if ($isMarked) {
                                        $classes[] = 'marked';
                                    }
                                    ?>
                                    <?php if ($index === 12): ?>
                                        <div class="bingo-carton-number modality data-position-13" data-position="<?= $number['position']; ?>">⭐️</div>
                                    <?php else: ?>
                                        <div class="bingo-carton-number number-<?= $number['number']; ?> <?= implode(' ', $classes); ?>"
                                             data-position="<?= $number['position']; ?>"
                                             data-number="<?= $number['number']; ?>"
                                             id="number-<?= $number['number']; ?>">
                                            <?= $number['number']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-white text-center mt-4"><?= translate('there are no cards available for this game'); ?></p>
                <?php endif; ?>
                <div style="width: 100%; height: 60px; flex-shrink: 0; pointer-events: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- MODALIDADES DE JUEGO (IDÉNTICO A PLAYING) -->
<?php if (!empty($modalities)): ?>
    <button type="button" class="btn btn-small btn-modalities" id="toggle-modalities-btn"
        aria-label="<?= translate('modalities'); ?>" aria-expanded="false" onclick="toggleModalitiesPanel();">
        <img src="<?= site_url('assets/img/modalidades.png'); ?>" alt="<?= translate('modalities'); ?>"
            style="width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0px 3px 6px rgba(0,0,0,0.35));">
    </button>
    <div class="modalities-display-container" id="playing-modalities-panel" role="region"
        aria-label="<?= translate('modalities'); ?>" aria-hidden="true">
        <div class="modalities-display-container__toolbar">
            <div class="modalities-display-meta">
                <h6><img src="<?= site_url('assets/img/modalidades.png'); ?>" alt="<?= translate('modalities'); ?>"
                        style="width: 20px; height: 20px; object-fit: contain; margin-right: 6px; vertical-align: middle; filter: drop-shadow(0px 1px 3px rgba(0,0,0,0.25));">
                    <?= translate('modalities'); ?></h6>
                <span class="modalities-display__hint"><?= count($modalities); ?> <?= count($modalities) === 1 ? 'modalidad' : 'modalidades'; ?> · desliza →</span>
            </div>
            <button type="button" class="modalities-display-close" id="modalities-panel-close"
                aria-label="<?= translate('close'); ?>" onclick="toggleModalitiesPanel(false);">
                <i class="fa-duotone fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modalities-display">
            <div class="container-cartons-modalities<?= count($modalities) === 1 ? ' container-cartons-modalities--solo' : '' ?>">
                <?php foreach ($modalities as $modality): ?>
                    <?php $isSing = in_array($modality['id'], $singsModalities ?? []);
                    $positions = explode(',', $modality['positions']); ?>
                    <div class="border-carton <?= $isSing ? 'modality-won' : '' ?>" id="modality-card-<?= $modality['id']; ?>">
                        <span class="modality-name"><?= translate($modality['name']); ?></span>
                        <div class="carton <?= $isSing ? 'cartn-sing' : '' ?>" id="modality-<?= $modality['id']; ?>">
                            <div class="card-letter B"><span>B</span></div>
                            <div class="card-letter I"><span>I</span></div>
                            <div class="card-letter N"><span>N</span></div>
                            <div class="card-letter G"><span>G</span></div>
                            <div class="card-letter O"><span>O</span></div>
                            <?php for ($i = 1; $i <= 25; $i++): ?>
                                <?php $isMarked = in_array($i, $positions);
                                $showStar = ($isSing && $isMarked) || $i == 13; ?>
                                <?php if ($i == 13): ?>
                                    <div class="card-number" data-position="13">⭐️</div>
                                <?php else: ?>
                                    <div class="card-number <?= $isMarked ? 'modality-sing' : '' ?> <?= $isSing && $isMarked ? 'sing' : '' ?>"
                                        data-position="<?= $i; ?>"><?= $showStar ? '⭐️' : '' ?></div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <?php if ($game['award'] == 2): ?>
                            <span class="modality-prize" id="modality-amount-<?= $modality['id']; ?>"><?= systemGet('currency'); ?> <?= number_format($modality['amount'], 2) ?></span>
                        <?php else: ?>
                            <span class="modality-prize" id="modality-amount-<?= $modality['id']; ?>">—</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- BOTÓN FLOTANTE "VER GANADORES" -->
<button id="btn-ver-ganadores-player" type="button" style="display: <?= !empty($winners) ? 'inline-flex' : 'none' ?>;" onclick="openSpectatorWinnersModal();">
    <i class="fa-solid fa-trophy-star me-1"></i>
    <span id="winners-btn-text">Ver Ganadores (<?= count($winners); ?>)</span>
</button>

<!-- BOTÓN FLOTANTE CTA "REGÍSTRATE AQUÍ" -->
<button type="button" class="btn-spectator-cta" onclick="openSpectatorRegisterModal();">
    <i class="fa-solid fa-crown me-1 text-warning"></i>
    <span>¡Juega tú también! Regístrate aquí</span>
</button>

<!-- MODAL COMPARTIR PARTIDA -->
<div class="modal fade" id="modal-share-game" tabindex="-1" aria-labelledby="modalShareGameLabel" aria-hidden="true" style="z-index: 1095;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg text-dark" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
            <div class="modal-header text-white p-3 p-md-4" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-share-nodes fs-5 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalShareGameLabel" style="font-size: 1.15rem;">Compartir Partida</h5>
                        <small class="text-white-50">Invita a más personas a ver la partida en vivo</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="text-center mb-3">
                    <div class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2" style="font-size: 0.82rem;">
                        <i class="fa-solid fa-eye me-1"></i> Modo Espectador (Solo Lectura)
                    </div>
                    <p class="text-muted small mb-0">
                        Quien abra este enlace observará en vivo los cartones de <strong><?= esc($playerName); ?></strong> y las balotas cantadas sin poder intervenir.
                    </p>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Enlace de espectador:</label>
                    <div class="input-group shadow-sm rounded-3 overflow-hidden border">
                        <input type="text" id="spectator-share-input" class="form-control border-0 bg-light py-2" style="font-size: 0.88rem; font-family: monospace;" readonly value="<?= current_url(); ?>">
                        <button class="btn btn-primary px-3 fw-bold" type="button" onclick="copySpectatorLink();">
                            <i class="fa-solid fa-copy me-1"></i>
                            <span>Copiar</span>
                        </button>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="https://api.whatsapp.com/send?text=<?= rawurlencode("¡Hola! Te invito a ver la partida de " . $playerName . " en vivo en Rey Bingo aquí: " . current_url()) ?>" target="_blank" class="btn btn-success py-2 fw-bold d-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm" style="background: #25D366; border-color: #25D366;">
                        <i class="fa-brands fa-whatsapp fs-5"></i>
                        <span>Compartir por WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REGISTRO Y LOGIN DEL ESPECTADOR -->
<div class="modal fade" id="modal-spectator-register" tabindex="-1" aria-labelledby="modalSpectatorRegisterLabel" aria-hidden="true" style="z-index: 1099;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg text-dark" style="border-radius: 24px; overflow: hidden; background: #ffffff;">
            <div class="modal-header p-4 text-white" style="background: linear-gradient(135deg, #6236ff 0%, #4318ff 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 44px; height: 44px; border-radius: 14px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-crown text-warning fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalSpectatorRegisterLabel" style="font-size: 1.25rem;">¡Únete a Rey Bingo!</h5>
                        <small class="text-white-50">Crea tu cuenta para jugar, marcar y ganar premios reales</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Botón Continuar con Google -->
                <a href="<?= site_url('signup/google') ?>" class="btn w-100 py-2 mb-3 border shadow-sm d-flex align-items-center justify-content-center gap-2 fw-bold" style="background: #ffffff; color: #374151; border-radius: 12px; font-size: 0.95rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.66v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.15z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.24v3.15C3.26 21.36 7.33 24 12 24z"/>
                        <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.24C.45 8.15 0 9.92 0 12s.45 3.85 1.24 5.42l4.04-3.15z"/>
                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.24 6.58l4.04 3.15c.95-2.83 3.6-4.93 6.72-4.93z"/>
                    </svg>
                    <span>Continuar con Google</span>
                </a>

                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1 my-0 text-muted">
                    <span class="px-3 text-muted small fw-semibold">o con tu correo</span>
                    <hr class="flex-grow-1 my-0 text-muted">
                </div>

                <!-- Formulario Tradicional de Registro -->
                <form action="<?= site_url('signup/signupSubmit') ?>" method="POST" id="spectator-signup-form">
                    <?= csrf_field() ?>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Nombre</label>
                            <input type="text" name="firstname" class="form-control" required placeholder="Tu nombre" style="border-radius: 10px;">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Apellido</label>
                            <input type="text" name="lastname" class="form-control" required placeholder="Tu apellido" style="border-radius: 10px;">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" required placeholder="ejemplo@correo.com" style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold mb-1">Contraseña</label>
                        <input type="password" name="password" class="form-control" required placeholder="Crea una contraseña segura" style="border-radius: 10px;">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #6236ff 0%, #4318ff 100%); border-radius: 12px; border: none;">
                        Crear Cuenta y Jugar
                    </button>
                </form>

                <div class="text-center mt-3 pt-2 border-top">
                    <span class="text-muted small">¿Ya tienes cuenta?</span>
                    <a href="<?= site_url('signin') ?>" class="small fw-bold ms-1" style="color: #6236ff;">Iniciar Sesión</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL GANADORES DE LA PARTIDA -->
<div class="modal fade" id="modal-spectator-winners" tabindex="-1" aria-labelledby="modalWinnersLabel" aria-hidden="true" style="z-index: 1098;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-trophy fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0 text-white" id="modalWinnersLabel">Tabla de Ganadores</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-3">
                <div id="spectator-winners-list" class="d-flex flex-column gap-2">
                    <?php if (!empty($winners)): ?>
                        <?php foreach ($winners as $w): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= esc($w['image'] ?? site_url('assets/img/avatar.jpg')) ?>" class="rounded-circle border" style="width: 36px; height: 36px; object-fit: cover;">
                                    <div>
                                        <div class="fw-bold small text-dark"><?= esc($w['player'] ?? 'Jugador') ?></div>
                                        <small class="text-muted">Modalidad: <span class="badge bg-primary"><?= esc($w['modality_name'] ?? 'Bingo') ?></span></small>
                                    </div>
                                </div>
                                <div class="badge bg-warning text-dark fw-bold px-2 py-1">👑 Ganador</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fa-solid fa-trophy-star fs-1 text-muted opacity-50 mb-2"></i>
                            <p class="mb-0 small">Aún no se han registrado ganadores en esta partida.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT Y SINCRONIZACIÓN EN TIEMPO REAL CON SOKETI / PUSHER -->
<script src="<?= site_url('assets/plyr/plyr.js'); ?>?v=<?= APP_VERSION ?>"></script>
<script src="<?= function_exists('asset_url') ? asset_url('js/pusher.min.js') : (site_url('assets/js/pusher.min.js') . '?v=' . time()) ?>"></script>

<script>
    (function () {
        const GAME_ID = '<?= (int) $game["id"] ?>';
        const TOKEN = '<?= esc($token, "js") ?>';
        const SOKETI_KEY = '<?= env("SOKETI_KEY") ?>';
        const SOKETI_HOST = '<?= env("SOKETI_HOST") ?>';
        const SOKETI_PORT = parseInt('<?= env("SOKETI_PORT", 443) ?>', 10);
        const PUSHER_KEY = SOKETI_KEY || '<?= env("PUSHER_KEY") ?>';
        const PUSHER_CLUSTER = '<?= env("PUSHER_CLUSTER", "us2") ?>';
        const AUTH_URL = '<?= site_url("pusher/auth") ?>';

        let audioEnabled = true;
        const chimeAudio = new Audio('<?= site_url("assets/sounds/bingo.mp3") ?>');
        chimeAudio.volume = 0.5;

        // Toggle del panel de modalidades
        window.toggleModalitiesPanel = function (forceState) {
            const panel = document.getElementById('playing-modalities-panel');
            if (!panel) return;
            const isOpen = panel.classList.contains('is-open');
            const shouldOpen = forceState !== undefined ? forceState : !isOpen;
            if (shouldOpen) {
                panel.classList.add('is-open');
            } else {
                panel.classList.remove('is-open');
            }
        };

        // Modales de espectador
        window.openSpectatorRegisterModal = function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const el = document.getElementById('modal-spectator-register');
                if (el) {
                    const m = bootstrap.Modal.getOrCreateInstance(el);
                    m.show();
                }
            }
        };

        window.openShareGameModal = function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const el = document.getElementById('modal-share-game');
                if (el) {
                    const m = bootstrap.Modal.getOrCreateInstance(el);
                    m.show();
                }
            }
        };

        window.openSpectatorWinnersModal = function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const el = document.getElementById('modal-spectator-winners');
                if (el) {
                    const m = bootstrap.Modal.getOrCreateInstance(el);
                    m.show();
                }
            }
        };

        window.copySpectatorLink = function () {
            const input = document.getElementById('spectator-share-input');
            if (!input) return;
            input.select();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(onCopied).catch(() => {
                    try { document.execCommand('copy'); onCopied(); } catch (e) { alert('Enlace: ' + input.value); }
                });
            } else {
                try { document.execCommand('copy'); onCopied(); } catch (e) { alert('Enlace: ' + input.value); }
            }
            function onCopied() {
                if (typeof Toastify === 'function') {
                    Toastify({
                        text: "¡Enlace copiado al portapapeles!",
                        duration: 2500,
                        gravity: "top",
                        position: "center",
                        style: { background: "linear-gradient(135deg, #10b981, #059669)", borderRadius: "10px" }
                    }).showToast();
                } else {
                    alert('¡Enlace copiado!');
                }
            }
        };

        window.toggleSpectatorAudio = function () {
            audioEnabled = !audioEnabled;
            const icon = document.getElementById('sound-icon');
            if (icon) {
                if (audioEnabled) {
                    icon.className = 'fa-duotone fa-solid fa-volume';
                } else {
                    icon.className = 'fa-duotone fa-solid fa-volume-slash';
                }
            }
        };

        // Función para marcar automáticamente un número en todos los cartones mostrados
        function markNumberInCartons(num) {
            const n = parseInt(num, 10);
            if (!n || isNaN(n)) return;

            // Buscar celdas con el número en todos los cartones
            const cells = document.querySelectorAll('.bingo-carton-number.number-' + n + ', [data-number="' + n + '"]');
            cells.forEach(function (cell) {
                if (!cell.classList.contains('marked')) {
                    cell.classList.add('marked', 'ball-slide-in');
                }
            });

            // Reproducir sonido de balota si está activado
            if (audioEnabled) {
                try {
                    chimeAudio.currentTime = 0;
                    chimeAudio.play().catch(function () {});
                } catch (e) {}
            }
        }

        // Actualizar interfaz al salir una balota
        function handleBallDrawn(data) {
            const num = data.n ?? data.number;
            const total = data.totalNumbersGenerated ?? (data.drawnNumbers ? data.drawnNumbers.length : null);

            if (total !== null) {
                const counter = document.getElementById('balls-counter');
                if (counter) {
                    counter.textContent = total + ' - ' + Math.max(0, 75 - total);
                }
            }

            if (num) {
                markNumberInCartons(num);

                // Si es bingo tradicional con balota central
                const lastBall = document.getElementById('last-number');
                if (lastBall) {
                    const span = lastBall.querySelector('span');
                    if (span) span.textContent = num;
                    const letterEl = lastBall.querySelector('small');
                    let letter = 'O';
                    const n = parseInt(num, 10);
                    if (n <= 15) letter = 'B';
                    else if (n <= 30) letter = 'I';
                    else if (n <= 45) letter = 'N';
                    else if (n <= 60) letter = 'G';
                    if (letterEl) letterEl.textContent = letter;
                    lastBall.className = 'bingo-ball ' + letter + ' size-100 ball-slide-in';
                }
            }

            if (Array.isArray(data.drawnNumbers)) {
                data.drawnNumbers.forEach(function (n) {
                    markNumberInCartons(n);
                });
            }
        }

        // Manejar notificación de ganador
        function handleWinnerAnnounced(data) {
            const btn = document.getElementById('btn-ver-ganadores-player');
            if (btn) btn.style.display = 'inline-flex';

            const modalityId = data.modality ?? data.modalityId;
            if (modalityId) {
                const modCard = document.getElementById('modality-card-' + modalityId);
                if (modCard) {
                    modCard.classList.add('modality-won');
                }
            }

            if (typeof Toastify === 'function') {
                const playerName = data.player || ('Jugador #' + (data.user || ''));
                const modName = data.modality_name || data.modalityName || 'Bingo';
                Toastify({
                    text: '👑 ¡BINGO! ' + playerName + ' ganó ' + modName + '!',
                    duration: 5000,
                    gravity: 'top',
                    position: 'center',
                    style: {
                        background: 'linear-gradient(135deg, #f59e0b, #ea580c)',
                        borderRadius: '12px',
                        fontWeight: 'bold',
                        fontSize: '1rem',
                        boxShadow: '0 8px 24px rgba(0,0,0,0.4)'
                    }
                }).showToast();
            }
        }

        // Conectar WebSocket vía Pusher / Soketi
        if (typeof Pusher !== 'undefined' && PUSHER_KEY) {
            const pConfig = {
                channelAuthorization: {
                    endpoint: AUTH_URL,
                    transport: 'ajax'
                }
            };
            if (SOKETI_HOST) {
                pConfig.wsHost = SOKETI_HOST;
                pConfig.wsPort = SOKETI_PORT;
                pConfig.wssPort = SOKETI_PORT;
                pConfig.forceTLS = (SOKETI_PORT === 443);
                pConfig.disableStats = true;
                pConfig.enabledTransports = ['ws', 'wss'];
            } else {
                pConfig.cluster = PUSHER_CLUSTER;
            }

            try {
                const pusher = new Pusher(PUSHER_KEY, pConfig);
                const publicChannel = pusher.subscribe('game-' + GAME_ID);
                const privateChannel = pusher.subscribe('private-game-' + GAME_ID);

                const onBall = function (data) {
                    if (data) handleBallDrawn(data);
                };

                const onWin = function (data) {
                    if (data) handleWinnerAnnounced(data);
                };

                publicChannel.bind('game:number_drawn', onBall);
                privateChannel.bind('game:number_drawn', onBall);

                publicChannel.bind('game:sing_accepted', onWin);
                privateChannel.bind('game:sing_accepted', onWin);
                publicChannel.bind('game:modality_won', onWin);
                privateChannel.bind('game:modality_won', onWin);
            } catch (e) {
                console.warn('Pusher spectator error:', e);
            }
        }

        // Polling ligero de sincronización de seguridad cada 12 segundos
        setInterval(function () {
            if (document.hidden) return;
            fetch('<?= site_url("espectador/status/" . $token) ?>', { cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.success) {
                        if (Array.isArray(res.drawnNumbers)) {
                            res.drawnNumbers.forEach(function (n) {
                                markNumberInCartons(n);
                            });
                        }
                        if (res.totalNumbersGenerated !== undefined) {
                            const counter = document.getElementById('balls-counter');
                            if (counter) {
                                counter.textContent = res.totalNumbersGenerated + ' - ' + Math.max(0, 75 - res.totalNumbersGenerated);
                            }
                        }
                    }
                })
                .catch(function () {});
        }, 12000);
    })();
</script>
