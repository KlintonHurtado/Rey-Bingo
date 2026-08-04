<link rel="stylesheet" href="<?= site_url('assets/plyr/plyr.css'); ?>?<?= md5(date("Hms")); ?>">
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
        padding: 10px 10px calc(100px + env(safe-area-inset-bottom, 0px)) !important;
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
            padding: 10px 10px calc(100px + env(safe-area-inset-bottom, 0px)) !important;
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
</style>
<div class="container-section container-section--playing<?= ($game['type'] == 3 || $game['type'] == 4) ? ' container-section--live-game' : '' ?>">
    <div class="top-section <?php if ($game['type'] == 3 || $game['type'] == 4): ?>live<?php endif; ?>">
        <a class="btn btn-small btn-home" href="<?= site_url('play'); ?>"><i
                class="fa-duotone fa-solid fa-house"></i></a>

        <button type="button" class="btn btn-small btn-wallet" onclick="paymentsGet();">
            <i class="fa-duotone fa-solid fa-wallet"></i>
        </button>

        <button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();" title="Sonido" aria-label="Sonido">
            <?php if ($user['sounds'] == 1): ?>
                <i class="fa-duotone fa-solid fa-volume"></i>
            <?php else: ?>
                <i class="fa-duotone fa-solid fa-volume-slash"></i>
            <?php endif; ?>
        </button>

        <button class="btn btn-small btn-microphone hidden" onclick="RemoveMicrophone();" title="Narración de balotas" aria-label="Narración">
            <?php if ($user['narration'] == 1): ?>
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



        <h6 class="total-accumulated m-0"><small><?= translate('accumulated'); ?></small> <br /><span
                id="accumulated-counter" data-counter="0.00"><?= systemGet('currency'); ?> 0.00</span></h6>

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
                <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-triangle-exclamation"></i>
                    <?= translate('Warning!'); ?></h6>
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
                    <?= translate('Volver al Inicio'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= site_url('assets/plyr/plyr.js'); ?>?<?= md5(date("Hms")); ?>"></script>

<script type="text/javascript">
    window.singBall = "<?= systemGet('singBall'); ?>";
    window.timeBallGet = singBall.split('-')[0];
    //window.timeBallLast = singBall.split('-')[1];
    window.timeBallLast = 1000;
    window.totalNumbersGenerated = <?= (int) ($totalNumbersGenerated ?? 0); ?>;
    window.fiveNumbers = <?= $lastNumbersJson ?? '[]' ?>;
    window.winners = <?= json_encode($winners) ?>;
    window.gameDate = '<?= esc(function_exists('bingo_game_start_iso') ? bingo_game_start_iso($game) : ($game['date'] . 'T' . $game['time']), 'js') ?>';
    window.gameStatus = <?= (int) ($game['status'] ?? 0) ?>;
    window.gameIsFinished = <?= !empty($gameIsFinished) ? 'true' : 'false' ?>;
    window.activeModalities = <?= json_encode($modalities ?? []) ?>;
    window.autoMarkEnabled = <?= (isset($user['autodial']) && $user['autodial'] == 1) ? 'true' : 'false' ?>;
    window.singBingoOnlyLastBall = <?= systemGet('singBingoOnlyLastBall') == 1 ? 'true' : 'false' ?>;
    window.drawnNumbers = <?= json_encode(array_values(array_map('intval', $selectedNumbers ?? []))) ?>;
    window.currentUserId = <?= (int) session()->get('id') ?>;
    window.allowGameUnload = window.gameIsFinished;
    window.playerGroup = 0;

    function canLeaveGameWithoutWarning() {
        return window.allowGameUnload === true
            || window.gameIsFinished === true
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

        // Botones de salida - Mostrar modal al hacer clic
        $('.btn-home, .btn-exit, .btn-back').on('click', function (e) {
            if (canLeaveGameWithoutWarning()) {
                return true;
            }
            e.preventDefault();
            exitUrl = $(this).attr('href') || $(this).data('href');
            if (typeof showBsModal === 'function') showBsModal('#modalExit');
        });

        // Confirmar salida desde el modal
        $('#confirmExit').on('click', function (e) {
            e.preventDefault();
            if (typeof hideBsModal === 'function') hideBsModal('#modalExit');

            // Permitir la salida/recarga
            allowUnload = true;
            window.allowGameUnload = true;
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
</script>

<!-- Añadir al final del archivo, antes de los scripts existentes -->
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
    // Configuración de Pusher
    const GAME_ID = '<?= $game["id"] ?>';
    const AUTH_URL = '<?= site_url("pusher/auth") ?>';
    const PUSHER_KEY = '<?= env("PUSHER_KEY") ?>';
    const PUSHER_CLUSTER = '<?= env("PUSHER_CLUSTER") ?>';
    const USER_ID = '<?= session()->get('id') ?>';
</script>
<script src="<?= site_url('assets/js/pusher-client.js'); ?>?<?= md5(date("Hms")); ?>"></script>

<script src="<?= site_url('assets/js/playing.js'); ?>?<?= md5(date("Hms")); ?>"></script>