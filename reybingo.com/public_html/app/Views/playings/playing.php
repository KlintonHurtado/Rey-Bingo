<link rel="stylesheet" href="<?= site_url('assets/plyr/plyr.css'); ?>?v=<?= APP_VERSION ?>">
<style>
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

    /* Controles partida: engranaje + silencio + micrófono + marcado manual (2x2) */
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
        right: 70px !important;
        top: 12px !important;
    }

    .container-section--playing .btn-microphone {
        right: 70px !important;
        top: 68px !important;
    }

    .container-section--playing .btn-binary {
        right: 12px !important;
        top: 68px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .container-section--playing .btn-binary.hidden {
        display: none !important;
    }

    .container-section--playing .btn-sliders {
        right: 12px !important;
        top: 12px !important;
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

    .container-section--playing .btn-share-game:active {
        transform: scale(0.95) !important;
    }

    /* Acumulado a la izquierda para no tapar el botón manual (derecha) */
    .container-section--playing .total-accumulated {
        right: auto !important;
        left: 10px !important;
        top: 120px !important;
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
            right: 52px !important;
            top: 8px !important;
            width: 42px !important;
            height: 42px !important;
        }

        .container-section--playing .btn-microphone {
            right: 52px !important;
            top: 56px !important;
            width: 42px !important;
            height: 42px !important;
        }

        .container-section--playing .btn-binary {
            right: 8px !important;
            top: 56px !important;
            width: 42px !important;
            height: 42px !important;
        }

        .container-section--playing .btn-sliders {
            right: 8px !important;
            top: 8px !important;
        }

        .container-section--playing .btn-share-game {
            left: 95px !important;
            top: 5px !important;
            width: 42px !important;
            height: 42px !important;
            font-size: 1.15rem !important;
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

    /* Layout playing: header + cartones a pantalla completa; chat y modalidades flotantes */
    .container-section.container-section--playing {
        display: flex !important;
        flex-direction: column !important;
        height: 100dvh !important;
        max-height: 100dvh !important;
        padding-bottom: 0 !important;
        overflow: hidden !important;
    }

    .container-section--playing>.top-section {
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

    /* Video solo del tamaño del reproductor (centrado), sin franja negra a todo el ancho */
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

    /* LIVE: misma columna que móvil (video arriba, cartones abajo) en cualquier ancho */
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

    /* Habilitar y estilizar scrollbar visible para PC/Web */
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

    /* El contenedor global usa 5 columnas; en playing debe ser 1 cartón por fila */
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

        /* Optimización estética y de proporciones para el panel de chat en Web/PC */
        .message-display-container {
            width: 500px !important;
            height: 520px !important;
            bottom: 20px !important;
            left: auto !important;
            right: 20px !important;
            border-radius: 16px !important;
            background: linear-gradient(180deg, rgba(135, 103, 250, 0.40) 0%, rgba(98, 54, 255, 0.50) 100%) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            padding: 10px !important;
            overflow: hidden !important;
        }

        .message-display-container .message-display {
            max-height: 250px !important;
            padding-top: 10px !important;
        }

        .message-display-container .emoji-message-panel .chat-quick-box {
            padding: 8px 4px !important;
            gap: 16px !important;
        }

        .message-display-container .emoji-message-panel .message-list {
            max-height: 200px !important;
            gap: 8px !important;
        }

        .message-display-container .emoji-message-panel .message-btn {
            padding: 10px 14px !important;
            font-size: 0.85rem !important;
            border-radius: 12px !important;
            background: rgba(255, 255, 255, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            transition: all 0.2s ease !important;
        }

        .message-display-container .emoji-message-panel .message-btn:hover {
            background: rgba(255, 255, 255, 0.18) !important;
            transform: translateY(-1px);
        }

        .message-display-container .emoji-message-panel .emoji-grid {
            max-height: 200px !important;
            gap: 10px !important;
        }

        .message-display-container .emoji-message-panel .emoji-btn {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            font-size: 1.35rem !important;
            background: rgba(255, 255, 255, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 10px !important;
            transition: all 0.2s ease !important;
        }

        .message-display-container .emoji-message-panel .emoji-btn:hover {
            background: rgba(255, 255, 255, 0.18) !important;
            transform: scale(1.1);
        }

        /* Barras de scroll personalizadas y modernas para los paneles de chat en escritorio */
        .message-display-container .message-list::-webkit-scrollbar,
        .message-display-container .emoji-grid::-webkit-scrollbar {
            width: 6px !important;
            display: block !important;
        }

        .message-display-container .message-list::-webkit-scrollbar-thumb,
        .message-display-container .emoji-grid::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2) !important;
            border-radius: 4px !important;
        }

        .message-display-container .message-list::-webkit-scrollbar-thumb:hover,
        .message-display-container .emoji-grid::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.35) !important;
        }

        .message-display-container .message-list::-webkit-scrollbar-track,
        .message-display-container .emoji-grid::-webkit-scrollbar-track {
            background-color: rgba(0, 0, 0, 0.1) !important;
            border-radius: 4px !important;
        }

        /* Panel de modalidades en Web/PC: Ubicación y tamaño tipo chat, pero con colores de móvil */
        .modalities-display-container {
            width: 450px !important;
            height: 520px !important;
            max-height: calc(100vh - 120px) !important;
            top: auto !important;
            bottom: 20px !important;
            left: 20px !important;
            right: auto !important;
            transform: none !important;
            border-radius: 16px !important;
            /* Fondo degradado azul idéntico al móvil */
            background: linear-gradient(180deg, rgba(24, 10, 84, 0.22) 0%, rgba(33, 16, 95, 0.72) 28%, rgba(55, 29, 146, 0.96) 100%) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.35) !important;
            border: none !important;
            flex-direction: column !important;
            z-index: 1060 !important;
            display: none;
            /* Por defecto oculto */
        }

        /* Para que funcione el toggle al dar click */
        .modalities-display-container.is-open {
            display: flex !important;
        }

        .modalities-display-container .modalities-display-container__toolbar {
            background: transparent !important;
            border-bottom: none !important;
            border-radius: 0 !important;
            padding: 12px 16px !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
        }

        .modalities-display-container .modalities-display-meta {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modalities-display-container .modalities-display-meta h6 {
            color: #ffffff !important;
            font-size: 1rem !important;
            margin: 0 !important;
        }

        .modalities-display-container .modalities-display-meta .modalities-display__hint {
            display: none !important;
            /* Oculto porque ahora es vertical */
        }

        /* En desktop con esta altura se muestra VERTICALMENTE para llenar el espacio */
        .modalities-display-container .modalities-display {
            overflow-x: hidden !important;
            overflow-y: auto !important;
            padding: 16px !important;
            scroll-snap-type: none !important;
            display: block !important;
        }

        .modalities-display-container .container-cartons-modalities {
            flex-direction: column !important;
            flex-wrap: nowrap !important;
            gap: 16px !important;
            width: 100% !important;
            min-width: unset !important;
            display: flex !important;
            align-items: center !important;
        }

        /* Scrollbar vertical */
        .modalities-display-container .modalities-display::-webkit-scrollbar {
            width: 6px !important;
            height: auto !important;
            display: block !important;
        }

        .modalities-display-container .modalities-display::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.45) !important;
            border-radius: 4px !important;
        }

        .modalities-display-container .modalities-display::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.6) !important;
        }

        .modalities-display-container .modalities-display::-webkit-scrollbar-track {
            background-color: transparent !important;
        }

        /* Tarjetas de modalidad en desktop: Colores móviles (blanco) */
        .modalities-display-container .border-carton {
            min-width: 280px !important;
            max-width: 320px !important;
            width: 100% !important;
            background: #ffffff !important;
            border: 2px solid #6236ff !important;
            border-radius: 12px !important;
            padding: 16px !important;
            box-shadow: 0 4px 14px rgba(98, 54, 255, 0.2) !important;
            scroll-snap-align: unset !important;
            flex: 0 0 auto !important;
            backdrop-filter: none !important;
        }

        .modalities-display-container .border-carton.modality-won {
            background: #f5f5f5 !important;
            border-color: #cccccc !important;
            box-shadow: none !important;
            filter: grayscale(100%) opacity(0.7) !important;
            pointer-events: none;
        }

        .modalities-display-container .border-carton.modality-won .modality-name,
        .modalities-display-container .border-carton.modality-won .modality-prize {
            color: #555555 !important;
        }

        .modalities-display-container .border-carton .modality-name {
            color: #333 !important;
            font-size: 0.95rem !important;
            font-weight: bold !important;
            text-align: center;
            display: block;
            margin-bottom: 12px;
        }

        .modalities-display-container .border-carton .modality-prize {
            color: #6236ff !important;
            font-size: 0.95rem !important;
            font-weight: bold !important;
            text-align: center;
            display: block;
            margin-top: 12px;
        }

        .modalities-display-container .carton {
            max-width: 100% !important;
            width: 100% !important;
            aspect-ratio: 1;
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

    /* Modalidades: mismo patrón flotante que el chat */
    .btn-modalities {
        position: fixed;
        left: calc(10px + env(safe-area-inset-left, 0px));
        bottom: calc(10px + env(safe-area-inset-bottom, 0px));
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1055;
        border: none;
        transition: transform 0.3s ease;
        padding: 0;
    }

    .btn-modalities:hover {
        transform: scale(1.08);
    }

    body .btn-chat {
        left: auto !important;
        right: calc(10px + env(safe-area-inset-right, 0px)) !important;
        bottom: calc(10px + env(safe-area-inset-bottom, 0px)) !important;
        width: 80px !important;
        height: 80px !important;
        min-width: 80px !important;
        font-size: 2.2rem !important;
    }

    body.chat-panel-open .btn-chat {
        z-index: 1058 !important;
    }

    body.modalities-panel-open .btn-modalities,
    body.chat-panel-open .btn-modalities {
        display: none !important;
    }

    /* Botón flotante para imprimir cartones: parte inferior derecha, alineado sobre el chat */
    .btn-print-cartons {
        position: fixed !important;
        right: calc(18px + env(safe-area-inset-right, 0px)) !important;
        bottom: calc(98px + env(safe-area-inset-bottom, 0px)) !important;
        width: 64px !important;
        height: 64px !important;
        min-width: 64px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0369a1 100%) !important;
        border: 2.5px solid rgba(255, 255, 255, 0.9) !important;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4), 0 0 14px rgba(14, 165, 233, 0.5) !important;
        color: #ffffff !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.65rem !important;
        z-index: 1056 !important;
        cursor: pointer !important;
        padding: 0 !important;
        outline: none !important;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease, background 0.25s ease !important;
        text-decoration: none !important;
    }

    .btn-print-cartons:hover {
        transform: scale(1.1) !important;
        background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 50%, #0284c7 100%) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5), 0 0 20px rgba(56, 189, 248, 0.7) !important;
        color: #ffffff !important;
    }

    .btn-print-cartons:active {
        transform: scale(0.94) !important;
    }

    body.modalities-panel-open .btn-print-cartons,
    body.chat-panel-open .btn-print-cartons {
        opacity: 0 !important;
        pointer-events: none !important;
        transform: scale(0.8) !important;
    }

    @media (max-width: 700px) {
        .btn-print-cartons {
            right: calc(18px + env(safe-area-inset-right, 0px)) !important;
            bottom: calc(96px + env(safe-area-inset-bottom, 0px)) !important;
            width: 64px !important;
            height: 64px !important;
            min-width: 64px !important;
            font-size: 1.55rem !important;
        }
    }

    /* Botón flotante Ver Ganadores para jugador: centrado abajo entre Modalidades (izq) y Chat (der) */
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
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease !important;
    }

    #btn-ver-ganadores-player:hover {
        transform: translateX(-50%) scale(1.06) !important;
        box-shadow: 0 6px 22px rgba(0, 0, 0, 0.55), 0 0 15px rgba(255, 193, 7, 0.5) !important;
    }

    #btn-ver-ganadores-player:active {
        transform: translateX(-50%) scale(0.96) !important;
    }

    body.modalities-panel-open #btn-ver-ganadores-player,
    body.chat-panel-open #btn-ver-ganadores-player {
        opacity: 0 !important;
        pointer-events: none !important;
    }

    @media (max-width: 480px) {
        #btn-ver-ganadores-player {
            font-size: 13px !important;
            padding: 8px 14px !important;
            bottom: calc(18px + env(safe-area-inset-bottom, 0px)) !important;
            max-width: calc(100vw - 170px) !important;
        }
    }

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
        z-index: 1054;
        height: min(48vh, 420px);
        max-height: min(48vh, 420px);
        justify-content: space-between;
        overflow: hidden;
    }

    .modalities-display-container.is-open {
        display: flex;
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
    }

    .modalities-display-meta h6 {
        margin: 0;
        font-size: 0.92rem;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.1;
    }

    .modalities-display-meta .modalities-display__hint {
        display: block;
        margin-top: 2px;
        font-size: 0.68rem;
        color: rgba(255, 255, 255, 0.8);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .modalities-display-close {
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1rem;
        flex-shrink: 0;
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .modalities-display-close:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: scale(1.05);
    }

    .modalities-display {
        flex-grow: 1;
        min-height: 0;
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x;
        padding: 2px 10px 10px;
        scrollbar-width: thin;
        scroll-snap-type: x mandatory;
        scroll-padding: 10px;
    }

    .modalities-display::-webkit-scrollbar {
        display: block;
        height: 4px;
    }

    .modalities-display::-webkit-scrollbar {
        height: 6px;
    }

    .modalities-display::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.45);
        border-radius: 4px;
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
        grid-template-columns: none !important;
    }

    .modalities-display .container-cartons-modalities--solo {
        justify-content: center;
    }

    .modalities-display .border-carton {
        flex: 0 0 auto !important;
        min-width: 200px !important;
        /* Más ancho para que abarque casi toda la pantalla */
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

    .modalities-display .border-carton.modality-won .modality-name,
    .modalities-display .border-carton.modality-won .modality-prize {
        color: #555555 !important;
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

    @media (max-width: 700px) {
        .container-section--playing>.top-section {
            flex-shrink: 0 !important;
        }

        .container-section--playing .top-section.live {
            max-height: none !important;
            padding-top: 3rem !important;
            padding-bottom: 0.4rem !important;
            overflow: visible !important;
            background: transparent !important;
        }

        .container-section--playing .top-section.live .video-responsive,
        .container-section--playing .top-section.live #plyr-video-player {
            width: min(100%, 100vw) !important;
            max-width: 100% !important;
            height: auto !important;
            max-height: none !important;
            aspect-ratio: 16 / 9 !important;
            border-radius: 0 0 10px 10px !important;
            box-shadow: none !important;
        }

        .container-section--playing .center-section.center-section--playing {
            flex: 1 1 0 !important;
            min-height: 0 !important;
            height: 0 !important;
        }

        .container-section--playing .cartons-section.cartons-section--playing {
            justify-content: center !important;
            align-items: flex-start !important;
            padding: 10px 10px calc(115px + env(safe-area-inset-bottom, 0px)) !important;
            height: 100% !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .container-section--playing .bingo-border-carton {
            max-width: min(318px, 86vw) !important;
            padding: 8px 10px 10px !important;
            background-color: rgba(255, 255, 255, 0.92) !important;
            border-radius: 14px !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15) !important;
        }

        .container-section--playing .bingo-border-carton .carton-serial {
            font-size: 0.72rem !important;
        }

        .container-section--playing .bingo-carton {
            width: min(296px, 82vw) !important;
            gap: 4px !important;
            padding: 6px 7px !important;
        }

        .container-section--playing .bingo-carton-number,
        .container-section--playing .bingo-carton-header {
            font-size: clamp(0.78rem, 2.7vw, 1rem) !important;
            border-radius: 6px !important;
        }

        .container-section--playing .bingo-carton-number.data-position-13,
        .container-section--playing .bingo-carton-number.modality {
            font-size: clamp(0.92rem, 3.2vw, 1.12rem) !important;
        }

        .modalities-display-container {
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            bottom: 0 !important;
            height: min(48vh, 400px) !important;
            max-height: min(48vh, 400px) !important;
            background: linear-gradient(180deg, rgba(24, 10, 84, 0.22) 0%, rgba(33, 16, 95, 0.72) 28%, rgba(55, 29, 146, 0.96) 100%) !important;
            border: none !important;
            box-shadow: 0 -10px 28px rgba(0, 0, 0, 0.35) !important;
            border-radius: 16px 16px 0 0 !important;
            z-index: 1054 !important;
            justify-content: space-between !important;
        }

        .modalities-display-container .modalities-display {
            flex-grow: 1 !important;
            width: 100% !important;
            height: calc(100% - 52px) !important;
            margin: 0 !important;
            padding: 2px 10px calc(8px + env(safe-area-inset-bottom, 0px)) !important;
        }

        .modalities-display .container-cartons-modalities {
            width: 100% !important;
            min-width: 100% !important;
            justify-content: flex-start !important;
            /* Para que funcione el scroll-snap */
            gap: 0.75rem !important;
        }

        .modalities-display .container-cartons-modalities--solo {
            justify-content: center !important;
        }

        .modalities-display .container-cartons-modalities--solo .border-carton {
            min-width: min(260px, 85vw) !important;
            max-width: 85vw !important;
            width: 85vw !important;
        }

        .modalities-display .border-carton {
            min-width: 75vw !important;
        }

        .modalities-display .carton {
            width: 100% !important;
            max-width: 170px !important;
        }

        .message-display-container {
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            bottom: calc(62px + env(safe-area-inset-bottom, 0px)) !important;
            height: min(52vh, 430px) !important;
            max-height: min(52vh, 430px) !important;
            background: linear-gradient(180deg, rgba(24, 10, 84, 0.2) 0%, rgba(32, 15, 92, 0.65) 22%, rgba(55, 29, 146, 0.94) 100%) !important;
            border: none !important;
            box-shadow: 0 -10px 28px rgba(0, 0, 0, 0.35) !important;
            border-radius: 16px 16px 0 0 !important;
            z-index: 1054 !important;
            justify-content: space-between !important;
            padding-top: 36px !important;
        }

        .message-display-container .message-display-container__toolbar {
            position: absolute !important;
            top: 2px !important;
            right: 4px !important;
            z-index: 2 !important;
            padding: 0 !important;
        }

        .message-display-container .message-display {
            flex-grow: 1 !important;
            min-height: 120px !important;
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 10px 6px !important;
            background: transparent !important;
            gap: 6px !important;
        }

        .message-display-container .message-bubble {
            margin-bottom: 0 !important;
            padding: 8px 10px !important;
            border-radius: 16px !important;
            max-width: 92% !important;
        }

        .message-display-container .message-bubble span {
            font-size: 0.9rem !important;
            line-height: 1.25 !important;
        }

        /* En móvil el avatar recarga mucho visualmente */
        .message-display-container .message-bubble .profile-pic {
            display: none !important;
        }

        .message-display-container .emoji-message-panel {
            flex-shrink: 0 !important;
            width: 100% !important;
            padding: 6px 10px calc(22px + env(safe-area-inset-bottom, 0px)) !important;
            background: linear-gradient(180deg, rgba(98, 54, 255, 0.12) 0%, rgba(98, 54, 255, 0.85) 35%) !important;
            border-radius: 14px 14px 0 0 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 6px !important;
        }

        .message-display-container .message-bubble-slider {
            display: flex !important;
            flex-wrap: nowrap !important;
            overflow-x: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            gap: 8px !important;
            padding: 6px 2px 10px 2px !important;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .message-display-container .message-bubble-slider::-webkit-scrollbar {
            display: none;
        }

        .message-display-container .emoji-slider {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            width: 100% !important;
            max-width: 100% !important;
            gap: 8px !important;
            padding: 4px 2px !important;
        }

        .message-display-container .emoji-btn,
        .message-display-container .message-btn {
            background: rgba(255, 255, 255, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            color: #fff !important;
            transition: all 0.2s ease !important;
            cursor: pointer;
        }

        .message-display-container .emoji-btn:active,
        .message-display-container .message-btn:active {
            background: rgba(255, 255, 255, 0.3) !important;
            transform: scale(0.95);
        }

        .message-display-container .emoji-btn {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            border-radius: 12px !important;
            font-size: 1.35rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
        }

        .message-display-container .message-btn {
            padding: 8px 14px !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            white-space: normal !important;
            text-align: left !important;
            line-height: 1.2 !important;
            width: 100% !important;
        }

        .message-display-container .emoji-message-panel .input-group {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .message-display-container.message-display-container--live {
            height: min(58vh, 480px) !important;
            max-height: min(58vh, 480px) !important;
        }

        .message-display-container .emoji-message-panel .live-chat-input {
            padding-top: 4px !important;
        }

        /* Estilos de alineación de scroll integrados en el contenedor principal */
        .container-section--playing .content-cartons:not(.one-carton) {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 6px 8px !important;
            width: 100% !important;
            max-width: 100% !important;
            align-items: start !important;
        }

        .container-section--playing .content-cartons:not(.one-carton) .bingo-border-carton {
            width: 100% !important;
            max-width: 100% !important;
            padding: 4px 6px 6px !important;
            border-radius: 10px !important;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15) !important;
            margin: 0 !important;
        }

        .container-section--playing .content-cartons:not(.one-carton) .bingo-border-carton .carton-serial {
            font-size: clamp(0.5rem, 2vw, 0.65rem) !important;
            margin-bottom: 2px !important;
            font-weight: 700 !important;
        }

        .container-section--playing .content-cartons:not(.one-carton) .bingo-carton {
            width: 100% !important;
            max-width: 100% !important;
            gap: 2px !important;
            padding: 3px 4px !important;
        }

        .container-section--playing .content-cartons:not(.one-carton) .bingo-carton-number,
        .container-section--playing .content-cartons:not(.one-carton) .bingo-carton-header {
            font-size: clamp(0.55rem, 2.8vw, 0.85rem) !important;
            border-radius: 4px !important;
        }

        .container-section--playing .content-cartons:not(.one-carton) .bingo-carton-number.data-position-13,
        .container-section--playing .content-cartons:not(.one-carton) .bingo-carton-number.modality {
            font-size: clamp(0.65rem, 3.2vw, 0.95rem) !important;
        }
    }

    /* ========================================================
       ESTILOS DE IMPRESIÓN PROFESIONAL DE CARTONES DE BINGO
       ======================================================== */
    #printable-cartons-area {
        display: none !important;
    }

    @page {
        size: portrait;
        margin: 8mm 10mm;
    }

    @media print {
        /* Reset para página impresa limpia y sin fondo degradado */
        html, body {
            background: #ffffff !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow: visible !important;
            position: static !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Ocultar interfaz interactiva y multimedia de la partida */
        .container-section.container-section--playing,
        .container-section--playing,
        .container-section,
        .top-section,
        .center-section,
        .cartons-section,
        .btn-modalities,
        .modalities-display-container,
        .btn-chat,
        .message-display-container,
        .btn-print-cartons,
        #btn-ver-ganadores-player,
        .modal,
        .modal-backdrop,
        #countdown-container,
        #game-finalized,
        #whatsapp-plugin,
        .czm-chat-support,
        .preloader,
        .notifications,
        #notificationsContainer,
        #notificationIndicator,
        header, footer, nav, .navbar,
        video, iframe,
        .ratio,
        #toast-container {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            max-height: 0 !important;
            min-height: 0 !important;
            width: 0 !important;
            overflow: hidden !important;
            opacity: 0 !important;
            position: absolute !important;
            top: -99999px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #content-page {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
        }

        /* Mostrar única y exclusivamente el área de cartones con el estilo auténtico del juego */
        #printable-cartons-area {
            display: block !important;
            visibility: visible !important;
            position: static !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #printable-cartons-area * {
            visibility: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-doc-header {
            text-align: center !important;
            margin: 0 auto 10px auto !important;
            padding-bottom: 6px !important;
            border-bottom: 2px solid #e0d7ff !important;
            width: 100% !important;
            max-width: 680px !important;
        }

        .print-doc-title {
            font-size: 18pt !important;
            font-weight: 900 !important;
            color: #1e1b4b !important;
            margin: 0 !important;
            padding: 0 !important;
            letter-spacing: 0.5px !important;
            line-height: 1.2 !important;
        }

        .print-doc-gamename {
            font-size: 11pt !important;
            font-weight: 700 !important;
            color: #6236ff !important;
            margin: 2px 0 0 0 !important;
            padding: 0 !important;
            line-height: 1.2 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }

        .print-cartons-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12mm 10mm !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }

        .print-cartons-grid.one-card {
            grid-template-columns: 1fr !important;
            max-width: 110mm !important;
            margin: 0 auto !important;
        }

        .print-carton-item {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            background: #ffffff !important;
            border: 1.5px solid #d0c3ff !important;
            border-radius: 14px !important;
            padding: 10px 12px 12px !important;
            box-sizing: border-box !important;
        }

        .print-carton-serial {
            text-align: center !important;
            font-size: 11pt !important;
            font-weight: 800 !important;
            color: #4a4a5a !important;
            margin: 0 0 8px 0 !important;
            letter-spacing: 0.5px !important;
            line-height: 1.2 !important;
        }

        .print-bingo-carton {
            display: grid !important;
            grid-template-columns: repeat(5, 1fr) !important;
            gap: 5px !important;
            width: 100% !important;
            background: #ffffff !important;
            border-radius: 8px !important;
            padding: 0 !important;
            box-sizing: border-box !important;
        }

        .print-carton-header {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            aspect-ratio: 1 / 1 !important;
            min-height: 38px !important;
            font-size: 16pt !important;
            font-weight: 900 !important;
            color: #ffffff !important;
            border-radius: 6px !important;
            line-height: 1 !important;
            text-align: center !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-carton-header.B { background-color: #FF4D4D !important; }
        .print-carton-header.I { background-color: #F39C12 !important; }
        .print-carton-header.N { background-color: #32CD32 !important; }
        .print-carton-header.G { background-color: #1E90FF !important; }
        .print-carton-header.O { background-color: #A020F0 !important; }

        .print-carton-number {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            aspect-ratio: 1 / 1 !important;
            min-height: 38px !important;
            background-color: #d0c3ff !important;
            color: #2b1b54 !important;
            font-weight: 800 !important;
            font-size: 15pt !important;
            border-radius: 6px !important;
            line-height: 1 !important;
            text-align: center !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-carton-number.data-position-13 {
            font-size: 18pt !important;
            line-height: 1 !important;
        }

        .print-cartons-grid.one-card .print-carton-header,
        .print-cartons-grid.one-card .print-carton-number {
            min-height: 54px !important;
            font-size: 22pt !important;
            border-radius: 8px !important;
        }

        .print-cartons-grid.one-card .print-carton-number.data-position-13 {
            font-size: 26pt !important;
        }

        .print-cartons-grid.one-card .print-carton-serial {
            font-size: 13pt !important;
            margin-bottom: 10px !important;
        }
    }
</style>
<div class="container-section container-section--playing<?= ($game['type'] == 3 || $game['type'] == 4) ? ' container-section--live-game' : '' ?>">
    <div class="top-section <?php if ($game['type'] == 3 || $game['type'] == 4): ?>live<?php endif; ?>">
        <a class="btn btn-small btn-home" href="<?= site_url('play'); ?>"><i
                class="fa-duotone fa-solid fa-house"></i></a>

        <button type="button" class="btn btn-small btn-wallet" onclick="paymentsGet();">
            <i class="fa-duotone fa-solid fa-wallet"></i>
        </button>

        <button type="button" class="btn btn-small btn-share-game" onclick="openShareGameModal();" title="Compartir Partida" aria-label="Compartir Partida">
            <i class="fa-solid fa-share-nodes"></i>
        </button>

        <button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();" title="Sonido" aria-label="Sonido">
            <?php if (!isset($user['sounds']) || (int)$user['sounds'] !== 0): ?>
                <i class="fa-duotone fa-solid fa-volume"></i>
            <?php else: ?>
                <i class="fa-duotone fa-solid fa-volume-slash"></i>
            <?php endif; ?>
        </button>

        <button class="btn btn-small btn-microphone hidden" onclick="RemoveMicrophone();" title="Narración de balotas" aria-label="Narración">
            <?php if (!isset($user['narration']) || (int)$user['narration'] !== 0): ?>
                <i class="fa-duotone fa-solid fa-microphone"></i>
            <?php else: ?>
                <i class="fa-duotone fa-solid fa-microphone-slash"></i>
            <?php endif; ?>
        </button>

        <button class="btn btn-small btn-binary hidden" id="btn-auto-mark" onclick="RemoveCheck();" title="Marcado automático / manual" aria-label="Marcado manual">
            <?php if ($user['autodial'] == 1): ?>
                <i class="fa-duotone fa-solid fa-wand-magic-sparkles"></i>
            <?php else: ?>
                <i class="fa-duotone fa-solid fa-hand"></i>
            <?php endif; ?>
        </button>

        <button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i
                class="fa-duotone fa-solid fa-sliders-simple"></i></button>

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

        <h6 class="total-balls m-0"><small><?= translate('total balls'); ?></small> <br /><span
                id="balls-counter"><?= $totalNumbersGenerated ?> - <?= 75 - $totalNumbersGenerated ?></span></h6>



        <h6 class="total-accumulated m-0"><?php
            $hudPrize = bingo_live_prize_hud($game, bingo_count_game_cartons((int) ($game['id'] ?? 0)));
        ?><small id="accumulated-label"><?= esc($hudPrize['label']); ?></small> <br /><span
                id="accumulated-counter" data-counter="<?= esc($hudPrize['amount']); ?>"><?= systemGet('currency'); ?> <?= esc($hudPrize['amount']); ?></span></h6>

        <?php if ($game['type'] != 3 && $game['type'] != 4): ?>
            <?php $class = $lastNumber ? $getClass($lastNumber) : 'STOP'; ?>
            <?php $letter = $lastNumber ? $getClass($lastNumber) : ''; ?>
            <div class="bingo-ball <?= $class ?> size-100" id="last-number"><small
                    style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;"><?= $letter ?></small><span><?= $lastNumber ? $lastNumber : 'STOP'; ?></span>
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
            <h6 class="text-white text-center mb-0"><?= $game['description']; ?></h6>
            <h6 class="text-white text-center next-game mb-1 text-uppercase" style="font-size: 0.8rem;"></h6><span
                class="cursor"></span>
        <?php endif; ?>
    </div>
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
                        <?php
                        $singMatches = [];
                        foreach ($singsUser as $sing) {
                            if ($sing['carton'] == $cartonData['cartonId']) {
                                $singMatches[] = array_map('intval', explode(',', $sing['numbers']));
                            }
                        }

                        $singNumbers = [];
                        foreach ($singMatches as $match) {
                            $singNumbers = array_merge($singNumbers, $match);
                        }
                        $singNumbers = array_unique($singNumbers);
                        ?>
                        <div class="bingo-border-carton">
                            <h6 class="ms-2 mb-1 text-center text-muted carton-serial">SERIAL: C<?= $cartonData['serial']; ?>
                            </h6>
                            <div class="bingo-carton" id="carton-<?= $cartonData['cartonId']; ?>">
                                <div class="bingo-carton-header B"><span>B</span></div>
                                <div class="bingo-carton-header I"><span>I</span></div>
                                <div class="bingo-carton-header N"><span>N</span></div>
                                <div class="bingo-carton-header G"><span>G</span></div>
                                <div class="bingo-carton-header O"><span>O</span></div>

                                <?php foreach ($cartonData['numbers'] as $index => $number): ?>
                                    <?php
                                    $classes = [];
                                    if ($number['status'] == 1) {
                                        $classes[] = 'marked';
                                    }

                                    if (in_array((int) $number['number'], $singNumbers)) {
                                        $classes[] = 'carton-sing';
                                    }
                                    ?>
                                    <?php if ($index === 12): ?>
                                        <div class="bingo-carton-number modality data-position-13"
                                            data-position="<?= $number['position']; ?>">⭐️</div>
                                    <?php else: ?>
                                        <div class="bingo-carton-number number-<?= $number['number']; ?> <?= implode(' ', $classes); ?>"
                                            data-position="<?= $number['position']; ?>" id="number-<?= $number['number']; ?>"
                                            onclick="dialNumber(<?= $number['number']; ?>);">
                                            <?= $number['number']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?= translate('there are no cards available for this game'); ?></p>
                <?php endif; ?>
                <div class="playing-cartons-bottom-spacer" style="width: 100%; height: 30px; flex-shrink: 0; pointer-events: none;"></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($modalities)): ?>
    <button type="button" class="btn btn-small btn-modalities" id="toggle-modalities-btn"
        aria-label="<?= translate('modalities'); ?>" aria-expanded="false">
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
                <span class="modalities-display__hint"><?= count($modalities); ?>
                    <?= count($modalities) === 1 ? 'modalidad' : 'modalidades'; ?> · desliza →</span>
            </div>
            <button type="button" class="modalities-display-close" id="modalities-panel-close"
                aria-label="<?= translate('close'); ?>">
                <i class="fa-duotone fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modalities-display">
            <div
                class="container-cartons-modalities<?= count($modalities) === 1 ? ' container-cartons-modalities--solo' : '' ?>">
                <?php foreach ($modalities as $modality): ?>
                    <?php $isSing = in_array($modality['id'], $singsModalities);
                    $positions = explode(',', $modality['positions']); ?>
                    <div class="border-carton <?= $isSing ? 'modality-won' : '' ?>">
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
                            <span class="modality-prize" id="modality-amount-<?= $modality['id']; ?>"><?= systemGet('currency'); ?>
                                <?= number_format($modality['amount'], 2) ?></span>
                        <?php else: ?>
                            <span class="modality-prize" id="modality-amount-<?= $modality['id']; ?>">—</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<button class="btn btn-small btn-chat" id="toggle-messages-btn"><i
        class="fa-duotone fa-solid fa-comments-question"></i></button>

<!-- Botón flotante para imprimir cartones (inferior derecha) -->
<button type="button" class="btn btn-small btn-print-cartons" id="btn-print-cartons"
    onclick="printBingoCartons();"
    title="<?= translate('Imprimir cartones'); ?>"
    aria-label="<?= translate('Imprimir cartones'); ?>">
    <i class="fa-duotone fa-solid fa-print"></i>
</button>

<!-- Área exclusiva de impresión de cartones (visible únicamente al imprimir) -->
<div id="printable-cartons-area" class="printable-cartons-area" aria-hidden="true">
    <div class="print-doc-header">
        <div class="print-doc-title">👑 REY BINGO</div>
        <div class="print-doc-gamename"><?= esc(!empty($game['description']) ? $game['description'] : translate('Partida de Bingo')); ?></div>
    </div>

    <?php
    $totalPrintCartons = isset($cartons) ? count($cartons) : 0;
    $gridPrintClass = 'print-cartons-grid';
    if ($totalPrintCartons === 1) {
        $gridPrintClass .= ' one-card';
    }
    ?>
    <div class="<?= $gridPrintClass; ?>">
        <?php if ($totalPrintCartons > 0): ?>
            <?php foreach ($cartons as $cartonData): ?>
                <?php
                $numbersList = array_values($cartonData['numbers'] ?? []);
                ?>
                <div class="print-carton-item">
                    <h6 class="carton-serial print-carton-serial">SERIAL: C<?= esc($cartonData['serial']); ?></h6>
                    <div class="bingo-carton print-bingo-carton">
                        <div class="bingo-carton-header print-carton-header B"><span>B</span></div>
                        <div class="bingo-carton-header print-carton-header I"><span>I</span></div>
                        <div class="bingo-carton-header print-carton-header N"><span>N</span></div>
                        <div class="bingo-carton-header print-carton-header G"><span>G</span></div>
                        <div class="bingo-carton-header print-carton-header O"><span>O</span></div>

                        <?php foreach ($numbersList as $index => $number): ?>
                            <?php if ($index === 12): ?>
                                <div class="bingo-carton-number print-carton-number data-position-13">⭐️</div>
                            <?php else: ?>
                                <div class="bingo-carton-number print-carton-number">
                                    <?= esc($number['number'] ?? ''); ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="message-display-container<?= ($game['type'] == 3 || $game['type'] == 4) ? ' message-display-container--live' : '' ?>"
    id="message-display-container" aria-hidden="true">
    <div class="message-display-container__toolbar">
        <button type="button" class="message-display-close" id="message-display-close"
            aria-label="<?= translate('close'); ?>">
            <i class="fa-duotone fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="message-display" id="message-display" aria-live="polite"></div>
    <div class="emoji-message-panel">
        <div class="chat-quick-box"
            style="display: flex; flex-direction: row; gap: 12px; width: 100%; align-items: flex-start; padding: 4px 8px;">
            <div class="message-list"
                style="flex: 1; display: flex; flex-direction: column; gap: 10px; max-height: <?= ($game['type'] == 3 || $game['type'] == 4) ? '120px' : '190px' ?>; overflow-y: auto; padding-right: 6px; padding-bottom: 8px;">
                <button type="button" class="message-btn"
                    style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;"
                    onclick="sendEmoji('¡Oe, me falta solo una! 😱', 20)">¡Oe, me falta solo una! 😱</button>
                <button type="button" class="message-btn"
                    style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;"
                    onclick="sendEmoji('¡Bravo, salió mi número! 🥳', 21)">¡Bravo, salió mi número! 🥳</button>
                <button type="button" class="message-btn"
                    style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;"
                    onclick="sendEmoji('¡Este premio es mío! 🤑', 22)">¡Este premio es mío! 🤑</button>
                <button type="button" class="message-btn"
                    style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;"
                    onclick="sendEmoji('¡Suerte para todos! 🍀', 23)">¡Suerte para todos! 🍀</button>
                <button type="button" class="message-btn"
                    style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;"
                    onclick="sendEmoji('¡Mi Rey, Bingo! 👑', 24)">¡Mi Rey, Bingo! 👑</button>
            </div>
            <div class="emoji-grid"
                style="flex: 1; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; align-content: flex-start; max-height: <?= ($game['type'] == 3 || $game['type'] == 4) ? '120px' : '180px' ?>; overflow-y: auto;">
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('🥳', 1)">🥳</button>
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('🎉', 2)">🎉</button>
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('😎', 3)">😎</button>
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('🍀', 4)">🍀</button>
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('🤑', 5)">🤑</button>
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('🌟', 6)">🌟</button>
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('😡', 7)">😡</button>
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('🔥', 8)">🔥</button>
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('👑', 9)">👑</button>
                <button type="button" class="emoji-btn"
                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;"
                    onclick="sendEmoji('💵', 10)">💵</button>
            </div>
        </div>
        <?php if ($game['type'] == 3 || $game['type'] == 4): ?>
            <div class="input-group live-chat-input" style="padding: 0 8px 4px;">
                <input type="text" class="form-control" id="message-send-new" placeholder="Escribe un mensaje..."
                    aria-label="Escribe un mensaje" autocomplete="off" maxlength="500">
                <button class="btn btn-primary" type="button" id="btn-send-message-new"><i
                        class="fa-duotone fa-solid fa-paper-plane-top"></i></button>
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="modal fade" id="modalBoard" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered max-w-45 max-w-50-xs mx-auto">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-table-cells"></i>
                    <?= translate('board'); ?></h6>
                <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i
                        class="fa-duotone fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body pt-0 text-center">
                <div class="board-number">
                    <div class="column">
                        <div class="bingo-ball B size-30"><span>B</span></div>
                        <?php foreach (range(1, 15) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball I size-30"><span>I</span></div>
                        <?php foreach (range(16, 30) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball N size-30"><span>N</span></div>
                        <?php foreach (range(31, 45) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball G size-30"><span>G</span></div>
                        <?php foreach (range(46, 60) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball O size-30"><span>O</span></div>
                        <?php foreach (range(61, 75) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="countdown-container" style="position: fixed; display: none;">
    <div class="countdown-container">
        <div id="countdown">10</div>
        <div id="text-countdown"></div>
    </div>
</div>

<div id="game-finalized" style="position: fixed; display: none;">
    <div class="game-finalized">
        <div id="finalized"></div>
    </div>
</div>

<div class="modal fade" id="modalExit" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title ps-2"><?= translate('Warning!'); ?></h6>
                <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i
                        class="fa-duotone fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body pt-0 text-center">
                <?= translate('if you exit the game you could lose your game data. We recommend you stay in the game.'); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary d-block w-50 btn-bingo mt-3 pe-2"
                    id="cancelExit"><?= translate('cancel'); ?></button>
                <a href="javascript:void(0);" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="confirmExit">
                    <?= translate('accept'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGameFinalized" tabindex="-1" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title ps-2">🎉 <?= translate('game finished!'); ?></h6>
            </div>
            <div class="modal-body pt-2 text-center" id="modalGameFinalizedBody">
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary d-block w-100 btn-bingo mt-1" id="btnVolverInicio">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> <?= translate('Salir'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAwards" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false"></div>

<button id="btn-ver-ganadores-player" class="btn btn-warning btn-bingo" type="button" style="display: none;" onclick="if(typeof window.awardsGet === 'function'){ window.awardsGet(); }">
    <i class="fa-solid fa-trophy-star me-1"></i><span><?= translate('Ver Ganadores'); ?></span>
</button>

<script src="<?= site_url('assets/plyr/plyr.js'); ?>?v=<?= APP_VERSION ?>"></script>
<script src="<?= function_exists('asset_url') ? asset_url('js/html2pdf.bundle.min.js') : site_url('assets/js/html2pdf.bundle.min.js') ?>"></script>

<script type="text/javascript">
    // Silenciar y detener de inmediato cualquier canción de fondo en la partida
    try {
        if (window.__bingoSoundtrack) {
            window.__bingoSoundtrack.pause();
            window.__bingoSoundtrack.currentTime = 0;
            window.__bingoSoundtrack.src = '';
            window.__bingoSoundtrack = null;
        }
        window.startBingoSoundtrack = function() {};
        if (typeof window.stopBingoSoundtrack === 'function') {
            window.stopBingoSoundtrack();
        }
    } catch (e) {}

    window.gameId = '<?= $game["id"] ?>';
    window.singBall = "<?= systemGet('singBall'); ?>";
    window.timeBallGet = singBall.split('-')[0];
    //window.timeBallLast = singBall.split('-')[1];
    window.timeBallLast = 1000;
    window.totalNumbersGenerated = <?= (int) ($totalNumbersGenerated ?? 0); ?>;
    window.fiveNumbers = <?= $lastNumbersJson ?? '[]' ?>;
    window.winners = <?= json_encode($winners) ?>;
    window.numberSingsLimit = <?= (int) (function_exists('bingo_get_number_sings_limit') ? bingo_get_number_sings_limit() : 1) ?>;
    window.gameDate = '<?= esc(function_exists('bingo_game_start_iso') ? bingo_game_start_iso($game) : ($game['date'] . 'T' . $game['time']), 'js') ?>';
    window.gameStatus = <?= (int) ($game['status'] ?? 0) ?>;
    window.isLiveGame = <?= (!empty($isLiveGame) || ((int) ($game['type'] ?? 0) === 3 || (int) ($game['type'] ?? 0) === 4)) ? 'true' : 'false' ?>;
    window.isGameCompleted = <?= !empty($isGameCompleted) ? 'true' : 'false' ?>;
    window.isLiveFinalized = <?= (!empty($isLiveFinalized) || ((int) ($game['status'] ?? 0) === 0)) ? 'true' : 'false' ?>;
    window.isAdmin = <?= bingo_is_admin() ? 'true' : 'false' ?>;
    window.gameIsFinished = <?= !empty($gameIsFinished) ? 'true' : 'false' ?>;
    window.activeModalities = <?= json_encode($modalities ?? []) ?>;
    window.autoMarkEnabled = <?= (isset($user['autodial']) && $user['autodial'] == 1) ? 'true' : 'false' ?>;
    window.singBingoOnlyLastBall = <?= systemGet('singBingoOnlyLastBall') == 1 ? 'true' : 'false' ?>;
    window.drawnNumbers = <?= json_encode(array_values(array_map('intval', $selectedNumbers ?? []))) ?>;
    window.currentUserId = <?= (int) session()->get('id') ?>;
    window.allowGameUnload = window.gameIsFinished;
    window.playerGroup = 0;

    // Auto-expulsión en tiempo real al finalizar Live:
    // Monitorea continuamente el estado del juego para expulsar/redirigir a los jugadores a /play
    // en cuanto el administrador finalice el Live, sin necesidad de recargar manualmente.
    if (window.isLiveGame && !window.isLiveFinalized) {
        (function() {
            var gid = window.gameId || '<?= $game["id"] ?>';
            var checkLiveFinalized = function() {
                if (window.isLiveFinalized || window.__liveAutoKickExecuted) {
                    if (window.__liveKickIntervalId) {
                        clearInterval(window.__liveKickIntervalId);
                        window.__liveKickIntervalId = null;
                    }
                    return;
                }
                var statusUrl = (typeof site_url !== 'undefined' ? site_url : '/') + 'playings/getGameStatus?game_id=' + gid;
                $.ajax({
                    url: statusUrl,
                    type: 'GET',
                    dataType: 'json',
                    cache: false,
                    success: function(res) {
                        if (res && res.status === 'success') {
                            if (parseInt(res.game_status, 10) === 0 || res.live_finalized === true) {
                                window.isLiveFinalized = true;
                                window.allowGameUnload = true;
                                window.__userLeavingGame = true;
                                window.__liveAutoKickExecuted = true;
                                window.onbeforeunload = null;
                                window.onpopstate = null;

                                if (window.__liveKickIntervalId) {
                                    clearInterval(window.__liveKickIntervalId);
                                    window.__liveKickIntervalId = null;
                                }

                                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                    ['modalAwards', 'modalGameFinalized', 'modalExit'].forEach(function(mId) {
                                        var mEl = document.getElementById(mId);
                                        if (mEl) {
                                            var bsM = bootstrap.Modal.getInstance(mEl);
                                            if (bsM) bsM.hide();
                                        }
                                    });
                                }

                                if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                                    Swal.close();
                                }

                                var playUrl = (typeof site_url !== 'undefined' ? site_url : '/') + 'play';

                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: '📢 Transmisión Finalizada',
                                        text: 'La transmisión en vivo ha finalizado. Redirigiendo a la sala...',
                                        icon: 'info',
                                        timer: 1200,
                                        timerProgressBar: true,
                                        showConfirmButton: false,
                                        allowOutsideClick: false,
                                        allowEscapeKey: false
                                    }).then(function() {
                                        window.location.replace(playUrl);
                                    });
                                    setTimeout(function() {
                                        window.location.replace(playUrl);
                                    }, 1300);
                                } else {
                                    window.location.replace(playUrl);
                                }
                            }
                        }
                    }
                });
            };

            window.__liveKickIntervalId = setInterval(checkLiveFinalized, 1500);
        })();
    }
    window.awardsGet = function() {
        const gid = window.gameId || '<?= $game["id"] ?>';
        const url = (typeof site_url !== 'undefined' ? site_url : '/') + 'playings/awardsGet?game_id=' + gid;
        let el = document.getElementById('modalAwards');
        if (!el) {
            el = document.createElement('div');
            el.className = 'modal fade';
            el.id = 'modalAwards';
            el.tabIndex = -1;
            el.setAttribute('role', 'dialog');
            el.setAttribute('data-bs-backdrop', 'static');
            el.setAttribute('data-bs-keyboard', 'false');
            document.body.appendChild(el);
        }
        const btnVer = document.getElementById('btn-ver-ganadores-player');
        if (btnVer) {
            btnVer.style.display = 'none';
        }
        $(el).load(url, function() {
            bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static', keyboard: false }).show();
            $('#game-finalized').hide();
        });
    };
    $(document).on('hidden.bs.modal', '#modalAwards', function () {
        if (window.isLiveGame && !window.isLiveFinalized) {
            const btnVer = document.getElementById('btn-ver-ganadores-player');
            if (btnVer) {
                btnVer.style.display = 'inline-flex';
            }
        }
    });
    window.printBingoCartons = function() {
        var printArea = document.getElementById('printable-cartons-area');
        if (!printArea) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'No hay cartones disponibles para imprimir en esta partida.'
                });
            } else {
                alert('No hay cartones disponibles para imprimir en esta partida.');
            }
            return;
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            ['modalExit', 'modalBoard', 'modalAwards', 'modalGameFinalized'].forEach(function(mId) {
                var mEl = document.getElementById(mId);
                if (mEl) {
                    var bsM = bootstrap.Modal.getInstance(mEl);
                    if (bsM) bsM.hide();
                }
            });
        }

        // 1. Nombre solicitado del documento: Rey Bingo + "nombre de la partida"
        var rawDesc = <?= json_encode(!empty($game['description']) ? trim($game['description']) : 'Partida'); ?>;
        var cleanDesc = (rawDesc || 'Partida').replace(/[\\/:*?"<>|]/g, '').trim();
        var docTitle = (/^rey\s*bingo/i.test(cleanDesc)) ? cleanDesc : ('Rey Bingo ' + cleanDesc);
        var pdfFileName = docTitle + '.pdf';

        // 2. Función para descargar el documento PDF directamente
        function downloadCartonsPDF() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Descargando cartones',
                    text: 'Preparando ' + pdfFileName + '...',
                    allowOutsideClick: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });
            }

            function generateAndSave() {
                if (typeof html2pdf === 'undefined') {
                    if (typeof Swal !== 'undefined') Swal.close();
                    var prevTitle = document.title;
                    document.title = docTitle;
                    window.print();
                    setTimeout(function() { document.title = prevTitle; }, 1500);
                    return;
                }

                // Contenedor temporal de exportación con estilos inline robustos
                var exportWrapper = document.createElement('div');
                exportWrapper.id = 'bingo-pdf-temp-export';
                exportWrapper.style.position = 'fixed';
                exportWrapper.style.left = '-99999px';
                exportWrapper.style.top = '0';
                exportWrapper.style.width = '750px';
                exportWrapper.style.background = '#ffffff';
                exportWrapper.style.color = '#000000';
                exportWrapper.style.padding = '14px';
                exportWrapper.style.boxSizing = 'border-box';
                exportWrapper.style.zIndex = '-9999';

                exportWrapper.innerHTML = `
                    <style>
                        #bingo-pdf-temp-export * {
                            box-sizing: border-box;
                            font-family: Arial, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                        }
                        #bingo-pdf-temp-export .print-doc-header {
                            text-align: center;
                            margin: 0 auto 12px auto;
                            padding-bottom: 6px;
                            border-bottom: 2px solid #e0d7ff;
                            width: 100%;
                        }
                        #bingo-pdf-temp-export .print-doc-title {
                            font-size: 22px;
                            font-weight: 900;
                            color: #1e1b4b;
                            margin: 0;
                            padding: 0;
                            letter-spacing: 0.5px;
                            line-height: 1.2;
                        }
                        #bingo-pdf-temp-export .print-doc-gamename {
                            font-size: 14px;
                            font-weight: 700;
                            color: #6236ff;
                            margin: 3px 0 0 0;
                            padding: 0;
                            line-height: 1.2;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }
                        #bingo-pdf-temp-export .print-cartons-grid {
                            display: flex;
                            flex-wrap: wrap;
                            justify-content: center;
                            gap: 16px;
                            width: 100%;
                        }
                        #bingo-pdf-temp-export .print-cartons-grid.one-card .print-carton-item {
                            width: 380px;
                            max-width: 100%;
                        }
                        #bingo-pdf-temp-export .print-carton-item {
                            width: 345px;
                            background: #ffffff;
                            border: 2px solid #d0c3ff;
                            border-radius: 12px;
                            padding: 8px 10px 10px;
                            margin-bottom: 12px;
                            page-break-inside: avoid;
                            break-inside: avoid;
                        }
                        #bingo-pdf-temp-export .print-carton-serial {
                            text-align: center;
                            font-size: 13px;
                            font-weight: 800;
                            color: #4a4a5a;
                            margin: 0 0 6px 0;
                        }
                        #bingo-pdf-temp-export .print-bingo-carton {
                            display: flex;
                            flex-wrap: wrap;
                            width: 100%;
                            gap: 4px;
                            justify-content: space-between;
                        }
                        #bingo-pdf-temp-export .print-carton-header {
                            width: calc(20% - 4px);
                            height: 38px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 18px;
                            font-weight: 900;
                            color: #ffffff !important;
                            border-radius: 6px;
                            text-align: center;
                        }
                        #bingo-pdf-temp-export .print-carton-header.B { background-color: #FF4D4D !important; }
                        #bingo-pdf-temp-export .print-carton-header.I { background-color: #F39C12 !important; }
                        #bingo-pdf-temp-export .print-carton-header.N { background-color: #32CD32 !important; }
                        #bingo-pdf-temp-export .print-carton-header.G { background-color: #1E90FF !important; }
                        #bingo-pdf-temp-export .print-carton-header.O { background-color: #A020F0 !important; }

                        #bingo-pdf-temp-export .print-carton-number {
                            width: calc(20% - 4px);
                            height: 38px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background-color: #d0c3ff !important;
                            color: #2b1b54 !important;
                            font-weight: 800;
                            font-size: 16px;
                            border-radius: 6px;
                            text-align: center;
                        }
                        #bingo-pdf-temp-export .print-carton-number.data-position-13 {
                            font-size: 20px;
                        }
                    </style>
                ` + printArea.innerHTML;

                document.body.appendChild(exportWrapper);

                var opt = {
                    margin: [8, 8, 8, 8],
                    filename: pdfFileName,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true, logging: false },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(exportWrapper).save().then(function() {
                    if (exportWrapper.parentNode) exportWrapper.parentNode.removeChild(exportWrapper);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Descargado!',
                            text: 'Se ha descargado ' + pdfFileName,
                            timer: 2500,
                            showConfirmButton: false
                        });
                    }
                }).catch(function(err) {
                    console.error('Error al generar PDF:', err);
                    if (exportWrapper.parentNode) exportWrapper.parentNode.removeChild(exportWrapper);
                    if (typeof Swal !== 'undefined') Swal.close();
                    var prevTitle = document.title;
                    document.title = docTitle;
                    window.print();
                    setTimeout(function() { document.title = prevTitle; }, 1500);
                });
            }

            if (typeof html2pdf === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
                script.onload = generateAndSave;
                script.onerror = generateAndSave;
                document.head.appendChild(script);
            } else {
                generateAndSave();
            }
        }

        // 3. Función para imprimir en computadoras de escritorio
        function printCartonsDesktop() {
            var prevTitle = document.title;
            document.title = docTitle;

            var iframeId = 'bingo-isolated-print-iframe';
            var iframe = document.getElementById(iframeId);
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = iframeId;
                iframe.setAttribute('style', 'position:fixed;top:-99999px;left:-99999px;width:0;height:0;border:0;visibility:hidden;');
                document.body.appendChild(iframe);
            }

            var iframeDoc = iframe.contentWindow || iframe.contentDocument;
            if (iframeDoc.document) iframeDoc = iframeDoc.document;

            var printContent = printArea.innerHTML;
            var printStyles = `
                @page {
                    size: portrait;
                    margin: 8mm 10mm;
                }
                * {
                    box-sizing: border-box;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                html, body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: #ffffff !important;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                    color: #000000;
                }
                .print-doc-header {
                    text-align: center;
                    margin: 0 auto 10px auto;
                    padding-bottom: 6px;
                    border-bottom: 2px solid #e0d7ff;
                    width: 100%;
                    max-width: 680px;
                }
                .print-doc-title {
                    font-size: 18pt;
                    font-weight: 900;
                    color: #1e1b4b;
                    margin: 0;
                    padding: 0;
                    letter-spacing: 0.5px;
                    line-height: 1.2;
                }
                .print-doc-gamename {
                    font-size: 11pt;
                    font-weight: 700;
                    color: #6236ff;
                    margin: 2px 0 0 0;
                    padding: 0;
                    line-height: 1.2;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .print-cartons-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 12mm 10mm;
                    width: 100%;
                    max-width: 100%;
                    margin: 0 auto;
                    padding: 0;
                }
                .print-cartons-grid.one-card {
                    grid-template-columns: 1fr;
                    max-width: 110mm;
                    margin: 0 auto;
                }
                .print-carton-item {
                    page-break-inside: avoid;
                    break-inside: avoid;
                    background: #ffffff;
                    border: 1.5px solid #d0c3ff;
                    border-radius: 14px;
                    padding: 10px 12px 12px;
                }
                .print-carton-serial {
                    text-align: center;
                    font-size: 11pt;
                    font-weight: 800;
                    color: #4a4a5a;
                    margin: 0 0 8px 0;
                    letter-spacing: 0.5px;
                    line-height: 1.2;
                }
                .print-bingo-carton {
                    display: grid;
                    grid-template-columns: repeat(5, 1fr);
                    gap: 5px;
                    width: 100%;
                    background: #ffffff;
                    border-radius: 8px;
                    padding: 0;
                }
                .print-carton-header {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    aspect-ratio: 1 / 1;
                    min-height: 38px;
                    font-size: 16pt;
                    font-weight: 900;
                    color: #ffffff !important;
                    border-radius: 6px;
                    line-height: 1;
                    text-align: center;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .print-carton-header.B { background-color: #FF4D4D !important; }
                .print-carton-header.I { background-color: #F39C12 !important; }
                .print-carton-header.N { background-color: #32CD32 !important; }
                .print-carton-header.G { background-color: #1E90FF !important; }
                .print-carton-header.O { background-color: #A020F0 !important; }

                .print-carton-number {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    aspect-ratio: 1 / 1;
                    min-height: 38px;
                    background-color: #d0c3ff !important;
                    color: #2b1b54 !important;
                    font-weight: 800;
                    font-size: 15pt;
                    border-radius: 6px;
                    line-height: 1;
                    text-align: center;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .print-carton-number.data-position-13 {
                    font-size: 18pt !important;
                    line-height: 1 !important;
                }
                .print-cartons-grid.one-card .print-carton-header,
                .print-cartons-grid.one-card .print-carton-number {
                    min-height: 54px;
                    font-size: 22pt;
                    border-radius: 8px;
                }
                .print-cartons-grid.one-card .print-carton-number.data-position-13 {
                    font-size: 26pt;
                }
                .print-cartons-grid.one-card .print-carton-serial {
                    font-size: 13pt;
                    margin-bottom: 10px;
                }
            `;

            iframeDoc.open();
            iframeDoc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + docTitle + '</title><style>' + printStyles + '</style></head><body>' + printContent + '</body></html>');
            iframeDoc.close();

            setTimeout(function() {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    window.print();
                }
                setTimeout(function() { document.title = prevTitle; }, 2000);
            }, 250);
        }

        // 4. Detección de dispositivo móvil / teléfono
        var isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) 
            || (window.matchMedia && window.matchMedia('(max-width: 820px)').matches && ('ontouchstart' in window || navigator.maxTouchPoints > 0));

        if (isMobileDevice) {
            // En teléfonos: descarga directa del documento PDF solicitado
            downloadCartonsPDF();
        } else {
            // En computadoras de escritorio: permitir descargar PDF o imprimir directamente
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: docTitle,
                    text: '¿Cómo deseas obtener tus cartones?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-download me-1"></i> Descargar PDF',
                    cancelButtonText: '<i class="fa-solid fa-print me-1"></i> Imprimir',
                    confirmButtonColor: '#6236ff',
                    cancelButtonColor: '#0ea5e9'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        downloadCartonsPDF();
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        printCartonsDesktop();
                    }
                });
            } else {
                downloadCartonsPDF();
            }
        }
    };

    window.audioPath = "<?= site_url('assets/sounds/'); ?>";
    window.narrationPlaying = <?= (!isset($user['narration']) || (int)$user['narration'] !== 0) ? 'true' : 'false' ?>;
    window.soundPlaying = <?= (!isset($user['sounds']) || (int)$user['sounds'] !== 0) ? 'true' : 'false' ?>;
    var audioPath = window.audioPath;
    var narrationPlaying = window.narrationPlaying;
    var soundPlaying = window.soundPlaying;

    function canLeaveGameWithoutWarning() {
        return window.allowGameUnload === true
            || window.gameIsFinished === true
            || window.isGameCompleted === true
            || (typeof window.BingoApp !== 'undefined' && window.BingoApp.isGameFinished);
    }

    document.addEventListener('DOMContentLoaded', function () {
        // La clase .modality-won ya aplica el estilo negro mediante CSS, no se necesitan estilos inline
        // document.querySelectorAll('.border-carton.modality-won').forEach(...)


        let exitUrl = null;
        let allowUnload = window.allowGameUnload === true;
        let reloadAttempted = false;

        // Detectar actividad del usuario
        let lastActivity = Date.now();
        document.addEventListener('mousemove', () => { lastActivity = Date.now(); });
        document.addEventListener('keydown', () => { lastActivity = Date.now(); });
        document.addEventListener('click', () => { lastActivity = Date.now(); });

        // Botones de salida - Mostrar modal al hacer clic o salir directamente si ya terminó
        $('.btn-home, .btn-exit, .btn-back').on('click', function (e) {
            var $btn = $(this);
            var playUrl = (typeof site_url !== 'undefined' ? site_url : '/') + 'play';
            var targetUrl = $btn.attr('href') || $btn.data('href') || playUrl;

            window.__userLeavingGame = true;
            if (window.awardsModalTimeoutId) {
                clearTimeout(window.awardsModalTimeoutId);
                window.awardsModalTimeoutId = null;
            }
            if (canLeaveGameWithoutWarning()) {
                allowUnload = true;
                window.allowGameUnload = true;
                const modalAwardsEl = document.getElementById('modalAwards');
                if (modalAwardsEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const bsModal = bootstrap.Modal.getInstance(modalAwardsEl);
                    if (bsModal) bsModal.hide();
                }
                window.location.replace(targetUrl);
                return false;
            }
            e.preventDefault();
            exitUrl = targetUrl;
            if (typeof showBsModal === 'function') showBsModal('#modalExit');
        });

        // Confirmar salida desde el modal
        $('#confirmExit').on('click', function (e) {
            e.preventDefault();
            if (typeof hideBsModal === 'function') hideBsModal('#modalExit');

            // Permitir la salida/recarga
            allowUnload = true;
            window.allowGameUnload = true;
            window.__userLeavingGame = true;
            window.onpopstate = null;

            var playUrl = (typeof site_url !== 'undefined' ? site_url : '/') + 'play';
            var targetUrl = exitUrl || playUrl;

            if (reloadAttempted) {
                // Si fue un intento de recarga, recargar la página
                reloadAttempted = false;
                setTimeout(() => {
                    location.reload();
                }, 100);
            } else {
                // replace: no dejar /playing en el historial (evita bucle billetera → partida)
                window.location.replace(targetUrl);
            }
        });

        // Cancelar salida
        $('#cancelExit').on('click', function () {
            if (typeof hideBsModal === 'function') hideBsModal('#modalExit');
            exitUrl = null;
            reloadAttempted = false;
            allowUnload = false;
        });

        // Interceptar botón atrás del navegador
        history.pushState({ reybingoPlaying: 1 }, null, location.href);
        window.onpopstate = function () {
            // Si la billetera está abierta, app.js cierra el modal (no mostrar salida)
            var walletEl = document.getElementById('modalPayments');
            if (walletEl && walletEl.classList.contains('show')) {
                return;
            }
            if (canLeaveGameWithoutWarning()) {
                window.onpopstate = null;
                var playUrl = (typeof site_url !== 'undefined' ? site_url : '/') + 'play';
                window.location.replace(playUrl);
                return;
            }
            history.pushState({ reybingoPlaying: 1 }, null, location.href);
            if (typeof showBsModal === 'function') showBsModal('#modalExit');
            exitUrl = (typeof site_url !== 'undefined' ? site_url : '/') + 'play';
        };

        // Interceptar F5/Ctrl+R
        window.addEventListener('keydown', function (e) {
            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            const refreshCombo =
                (e.key === 'F5') ||
                (e.ctrlKey && e.key.toLowerCase() === 'r') ||
                (isMac && e.metaKey && e.key.toLowerCase() === 'r');

            if (refreshCombo && !allowUnload && !canLeaveGameWithoutWarning()) {
                e.preventDefault();
                reloadAttempted = true;
                if (typeof showBsModal === 'function') showBsModal('#modalExit');
            }
        });

        if (window.gameIsFinished && typeof showGameFinalized === 'function') {
            showGameFinalized();
        }
    });

    <?php if ($game['type'] == 4): ?>
        !function () {
            new Plyr("#plyr-video-player");
            document.getElementsByClassName("plyr")[0].style.borderRadius = "0px 0px 10px 10px";
            document.getElementsByClassName("plyr__poster")[0].style.display = "none";
            let e = document.getElementsByTagName("html")[0],
                t = document.querySelector(".stick-top");
            window.addEventListener("scroll", function () {
                e.classList.contains("layout-navbar-fixed") ? t.classList.add("course-content-fixed") : t.classList.remove("course-content-fixed")
            })
        }();
    <?php endif; ?>

    // ========================================================
    // MODAL Y LÓGICA DE COMPARTIR PARTIDA (MODO ESPECTADOR)
    // ========================================================
    window.openShareGameModal = function() {
        const modalEl = document.getElementById('modal-share-game');
        if (!modalEl) return;

        // Si el enlace aún no se ha cargado en el input, obtenerlo vía AJAX
        const input = document.getElementById('spectator-share-input');
        if (input && (!input.value || input.value.trim() === '')) {
            fetch('<?= site_url("playings/getShareLink") ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: '<?= csrf_token() ?>=<?= csrf_hash() ?>&game_id=<?= $game["id"] ?>'
            })
            .then(r => r.json())
            .then(res => {
                if (res && res.success && res.share_url) {
                    input.value = res.share_url;
                    const waBtn = document.getElementById('btn-share-whatsapp');
                    if (waBtn) {
                        const msg = encodeURIComponent("¡Hola! Te invito a ver mi partida de Rey Bingo en vivo aquí: " + res.share_url);
                        waBtn.href = "https://api.whatsapp.com/send?text=" + msg;
                    }
                }
            })
            .catch(() => {});
        }

        const nativeBtn = document.getElementById('btn-native-share');
        if (nativeBtn && navigator.share) {
            nativeBtn.style.display = 'flex';
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            bsModal.show();
        } else if (typeof $ !== 'undefined') {
            $('#modal-share-game').modal('show');
        }
    };

    window.copySpectatorLink = function() {
        const input = document.getElementById('spectator-share-input');
        if (!input || !input.value) return;

        input.select();
        input.setSelectionRange(0, 99999);

        const onCopied = function() {
            const copyText = document.getElementById('copy-text');
            const copyIcon = document.getElementById('copy-icon');
            if (copyText) copyText.textContent = '¡Copiado!';
            if (copyIcon) copyIcon.className = 'fa-solid fa-check me-1';

            setTimeout(() => {
                if (copyText) copyText.textContent = 'Copiar';
                if (copyIcon) copyIcon.className = 'fa-solid fa-copy me-1';
            }, 2500);

            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: "¡Enlace de espectador copiado al portapapeles!",
                    duration: 3000,
                    gravity: "top",
                    position: "center",
                    style: {
                        background: "linear-gradient(135deg, #10b981, #059669)",
                        borderRadius: "10px",
                        fontWeight: "600"
                    }
                }).showToast();
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Enlace copiado!',
                    text: 'Compártelo con tus amigos para que vean tu partida en vivo.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(onCopied).catch(() => {
                try { document.execCommand('copy'); onCopied(); } catch (e) { alert('Enlace: ' + input.value); }
            });
        } else {
            try { document.execCommand('copy'); onCopied(); } catch (e) { alert('Enlace: ' + input.value); }
        }
    };

    window.nativeShareSpectator = function() {
        const input = document.getElementById('spectator-share-input');
        const url = input ? input.value : '<?= esc($spectatorShareUrl ?? "") ?>';
        if (navigator.share) {
            navigator.share({
                title: 'Rey Bingo - Mira mi partida en vivo',
                text: '¡Mira mi partida en vivo en Rey Bingo!',
                url: url
            }).catch(() => {});
        }
    };
</script>

<!-- MODAL COMPARTIR PARTIDA (MODO ESPECTADOR) -->
<div class="modal fade" id="modal-share-game" tabindex="-1" aria-labelledby="modalShareGameLabel" aria-hidden="true" style="z-index: 1090;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg text-dark" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
            <div class="modal-header text-white p-3 p-md-4" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-share-nodes fs-5 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalShareGameLabel" style="font-size: 1.15rem;">Compartir mi Partida</h5>
                        <small class="text-white-50">Invita a tus amigos a ver tu juego en tiempo real</small>
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
                        Quien abra este enlace verá <strong>tus cartones exclusivos</strong>, las balotas que van saliendo y tus aciertos en vivo sin poder modificarlos.
                    </p>
                </div>

                <!-- Input con enlace y botón de copiar -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Tu enlace exclusivo de espectador:</label>
                    <div class="input-group shadow-sm rounded-3 overflow-hidden border">
                        <input type="text" id="spectator-share-input" class="form-control border-0 bg-light py-2" style="font-size: 0.88rem; font-family: monospace;" readonly value="<?= esc($spectatorShareUrl ?? '') ?>">
                        <button class="btn btn-primary px-3 fw-bold" type="button" id="btn-copy-share-link" onclick="copySpectatorLink();">
                            <i class="fa-solid fa-copy me-1" id="copy-icon"></i>
                            <span id="copy-text">Copiar</span>
                        </button>
                    </div>
                </div>

                <!-- Botones de Compartir -->
                <div class="d-grid gap-2">
                    <a href="https://api.whatsapp.com/send?text=<?= rawurlencode("¡Hola! Te invito a ver mi partida de Rey Bingo en vivo aquí: " . ($spectatorShareUrl ?? '')) ?>" target="_blank" id="btn-share-whatsapp" class="btn btn-success py-2 fw-bold d-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm" style="background: #25D366; border-color: #25D366;">
                        <i class="fa-brands fa-whatsapp fs-5"></i>
                        <span>Compartir por WhatsApp</span>
                    </a>

                    <button type="button" class="btn btn-outline-secondary py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 rounded-3" id="btn-native-share" onclick="nativeShareSpectator();" style="display: none;">
                        <i class="fa-solid fa-arrow-up-from-bracket"></i>
                        <span>Más opciones de compartir</span>
                    </button>
                </div>

                <div class="text-center mt-3 pt-2 border-top">
                    <small class="text-muted" style="font-size: 0.78rem;">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> Seguro: el espectador solo podrá observar; no puede marcar números, comprar cartones ni alterar tu juego.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- WebSocket: Soketi self-hosted (VPS) con fallback a Pusher Cloud -->
<script src="<?= function_exists('asset_url') ? asset_url('js/pusher.min.js') : (site_url('assets/js/pusher.min.js') . '?v=' . time()) ?>"></script>
<script>
    // Configuracion de WebSocket (Soketi self-hosted o Pusher Cloud)
    const GAME_ID        = '<?= $game["id"] ?>';
    const AUTH_URL       = '<?= site_url("pusher/auth") ?>';
    // Soketi (VPS): definir SOKETI_KEY en .env de CI4
    const SOKETI_KEY     = '<?= env("SOKETI_KEY") ?>';
    const SOKETI_HOST    = '<?= env("SOKETI_HOST") ?>';
    const SOKETI_PORT    = '<?= env("SOKETI_PORT", 443) ?>';
    // Pusher Cloud: usados si SOKETI_KEY esta vacio
    const PUSHER_KEY     = SOKETI_KEY || '<?= env("PUSHER_KEY") ?>';
    const PUSHER_CLUSTER = '<?= env("PUSHER_CLUSTER") ?>';
    const USER_ID        = '<?= session()->get('id') ?>';
</script>
<script src="<?= function_exists('asset_url') ? asset_url('js/pusher-client.js') : (site_url('assets/js/pusher-client.js') . '?v=' . time()) ?>"></script>

<script src="<?= function_exists('asset_url') ? asset_url('js/modality-winners.js') : (site_url('assets/js/modality-winners.js') . '?v=' . time()) ?>"></script>
<script src="<?= function_exists('asset_url') ? asset_url('js/playing.js') : (site_url('assets/js/playing.js') . '?v=' . time()) ?>"></script>

