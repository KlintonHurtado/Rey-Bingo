<link rel="stylesheet" href="<?= site_url('assets/plyr/plyr.css'); ?>?<?= md5(date("Hms")); ?>">
<style>
    .ball-slide-in { animation: ballSlideIn 0.45s ease-out; }
    @keyframes ballSlideIn {
        from { opacity: 0; transform: translateX(-12px) scale(0.85); }
        to { opacity: 1; transform: translateX(0) scale(1); }
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
    .container-section--playing > .top-section {
        flex: 0 0 auto !important;
    }
    .container-section--playing .top-section.live {
        padding-top: 3.25rem !important;
        justify-content: flex-start !important;
        height: auto !important;
        max-height: none !important;
    }
    .container-section--playing .top-section.live .video-responsive,
    .container-section--playing .top-section.live #plyr-video-player {
        width: 100%;
        max-width: 100%;
    }
    .container-section--playing .center-section.center-section--playing {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        justify-content: flex-start !important;
        overflow: hidden !important;
        width: 100% !important;
        font-size: inherit;
        font-weight: inherit;
    }
    .container-section--playing .cartons-section.cartons-section--playing {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        height: auto !important;
        max-height: 100% !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 10px 10px calc(58px + env(safe-area-inset-bottom, 0px)) !important;
    }
    /* El contenedor global usa 5 columnas; en playing debe ser 1 cartón por fila */
    .container-section--playing .content-cartons {
        display: grid !important;
        grid-template-columns: 1fr !important;
        justify-items: center !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 auto !important;
        gap: 14px !important;
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
        width: 60px;
        height: 60px;
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
        width: 60px !important;
        height: 60px !important;
        min-width: 60px !important;
        font-size: 1.8rem !important;
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
        padding: 2px 10px 10px;
        scrollbar-width: none; /* Ocultar scrollbar en firefox para mejor vista móvil */
        scroll-snap-type: x mandatory;
        scroll-padding: 10px;
    }
    .modalities-display::-webkit-scrollbar {
        display: none; /* Ocultar scrollbar en webkit */
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
        min-width: 200px !important; /* Más ancho para que abarque casi toda la pantalla */
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
        border-color: #28a745;
        box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.35);
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
        .container-section--playing > .top-section {
            flex-shrink: 0 !important;
        }
        .container-section--playing .top-section.live {
            max-height: none !important;
            padding-top: 3.25rem !important;
        }
        .container-section--playing .center-section.center-section--playing {
            flex: 1 1 auto !important;
            min-height: 0 !important;
        }
        .container-section--playing .cartons-section.cartons-section--playing {
            justify-content: flex-end !important;
            align-items: center !important;
            padding: 4px 10px calc(88px + env(safe-area-inset-bottom, 0px)) !important;
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
            justify-content: flex-start !important; /* Para que funcione el scroll-snap */
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
    }
</style>
<div class="container-section container-section--playing">
    <div class="top-section <?php if ($game['type'] == 3 || $game['type'] == 4): ?>live<?php endif; ?>">
        <a class="btn btn-small btn-home" href="<?= site_url('play'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>

        <button type="button" class="btn btn-small btn-wallet" onclick="paymentsGet();">
            <i class="fa-duotone fa-solid fa-wallet"></i>
        </button>

        <button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();">
            <?php if ($user['sounds'] == 1): ?>
                <i class="fa-duotone fa-solid fa-volume"></i>
            <?php else : ?>
                <i class="fa-duotone fa-solid fa-volume-slash"></i>
            <?php endif; ?>
        </button>

        <button class="btn btn-small btn-microphone hidden" onclick="RemoveMicrophone();">
            <?php if ($user['narration'] == 1): ?>
                <i class="fa-duotone fa-solid fa-microphone"></i>
            <?php else : ?>
                <i class="fa-duotone fa-solid fa-microphone-slash"></i>
            <?php endif; ?>
        </button>
        
        <button class="btn btn-small btn-binary hidden" id="btn-auto-mark" onclick="RemoveCheck();">
            <?php if ($user['autodial'] == 1): ?>
                <i class="fa-duotone fa-solid fa-binary-circle-check"></i>
            <?php else : ?>
                <i class="fa-duotone fa-solid fa-binary-slash"></i>
            <?php endif; ?>
        </button>

        <button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i class="fa-duotone fa-solid fa-sliders-simple"></i></button>

        <?php if ($game['type'] == 3): ?>
            <div class="ratio ratio-16x9 video-responsive"> 
                <iframe src="<?= $game['url']; ?>" title="YouTube video player" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen>
              </iframe>
            </div>
        <?php endif; ?>

        <?php if ($game['type'] == 4): ?>
            <video class="w-100" poster="<?= !empty($game['cover']) ? site_url('uploads/covers/' . $game['cover']) : site_url('uploads/covers/image.jpg'); ?>" id="plyr-video-player" playsinline="" controls=""><source src="<?= !empty($game['video']) ? site_url('uploads/videos/' . $game['video']) : site_url('uploads/videos/image.jpg'); ?>" type="video/mp4"></video>
        <?php endif; ?>

        <h6 class="total-balls m-0"><small><?= translate('total balls'); ?></small> <br /><span id="balls-counter"><?= $totalNumbersGenerated ?> - <?= 75 - $totalNumbersGenerated ?></span></h6>

        <h6 class="total-accumulated m-0"><small><?= translate('accumulated'); ?></small> <br /><span id="accumulated-counter" data-counter="0.00"><?= systemGet('currency'); ?> 0.00</span></h6>

        <?php if ($game['type'] != 3 && $game['type'] != 4): ?>
            <?php $class = $lastNumber ? $getClass($lastNumber) : 'STOP'; ?> <?php $letter = $lastNumber ? $getClass($lastNumber) : ''; ?>
            <div class="bingo-ball <?= $class ?> size-100" id="last-number"><small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;"><?= $letter ?></small><span><?= $lastNumber ? $lastNumber : 'STOP'; ?></span></div>
        <?php endif; ?>
        
        <div class="last-numbers">
            <span id="last-five-numbers">
                <?php foreach ($fourNumbers as $number): ?>
                    <?php $class = in_array($number, $fourNumbers) ? $getClass($number) : ''; ?>
                    <div class="bingo-ball <?= $class ?> size-40">
                        <span><?= $number ?></span>
                    </div>
                <?php endforeach; ?>
            </span>
        </div>

        <?php if ($game['type'] != 3 && $game['type'] != 4): ?>
            <h6 class="text-white text-center mb-0"><?= $game['description']; ?></h6>
            <h6 class="text-white text-center next-game mb-1 text-uppercase" style="font-size: 0.8rem;"></h6><span class="cursor"></span>
        <?php endif; ?>
    </div>
    <div class="center-section center-section--playing">
        <div class="cartons-section cartons-section--playing">
            <?php
                $playingCartonCount = isset($cartons) ? count($cartons) : 0;
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
                            <h6 class="ms-2 mb-1 text-center text-muted carton-serial">SERIAL: C<?= $cartonData['serial']; ?></h6>
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

                                        if (in_array((int)$number['number'], $singNumbers)) {
                                            $classes[] = 'carton-sing';
                                        }
                                    ?>
                                    <?php if ($index === 12): ?>
                                        <div class="bingo-carton-number modality data-position-13" data-position="<?= $number['position']; ?>">⭐️</div>
                                    <?php else: ?>
                                        <div class="bingo-carton-number number-<?= $number['number']; ?> <?= implode(' ', $classes); ?>"
                                            data-position="<?= $number['position']; ?>"
                                            id="number-<?= $number['number']; ?>"
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
    <button type="button" class="btn btn-small btn-modalities" id="toggle-modalities-btn" aria-label="<?= translate('modalities'); ?>" aria-expanded="false">
        <img src="<?= site_url('assets/img/modalidades.png'); ?>" alt="<?= translate('modalities'); ?>" style="width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0px 3px 6px rgba(0,0,0,0.35));">
    </button>
    <div class="modalities-display-container" id="playing-modalities-panel" role="region" aria-label="<?= translate('modalities'); ?>" aria-hidden="true">
        <div class="modalities-display-container__toolbar">
            <div class="modalities-display-meta">
                <h6><img src="<?= site_url('assets/img/modalidades.png'); ?>" alt="<?= translate('modalities'); ?>" style="width: 20px; height: 20px; object-fit: contain; margin-right: 6px; vertical-align: middle; filter: drop-shadow(0px 1px 3px rgba(0,0,0,0.25));"> <?= translate('modalities'); ?></h6>
                <span class="modalities-display__hint"><?= count($modalities); ?> <?= count($modalities) === 1 ? 'modalidad' : 'modalidades'; ?> · desliza →</span>
            </div>
            <button type="button" class="modalities-display-close" id="modalities-panel-close" aria-label="<?= translate('close'); ?>">
                <i class="fa-duotone fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modalities-display">
            <div class="container-cartons-modalities<?= count($modalities) === 1 ? ' container-cartons-modalities--solo' : '' ?>">
                <?php foreach ($modalities as $modality): ?>
                    <?php $isSing = in_array($modality['id'], $singsModalities); $positions = explode(',', $modality['positions']); ?>
                    <div class="border-carton <?= $isSing ? 'modality-won' : '' ?>">
                        <span class="modality-name"><?= translate($modality['name']); ?></span>
                        <div class="carton <?= $isSing ? 'cartn-sing' : '' ?>" id="modality-<?= $modality['id']; ?>">
                            <div class="card-letter B"><span>B</span></div>
                            <div class="card-letter I"><span>I</span></div>
                            <div class="card-letter N"><span>N</span></div>
                            <div class="card-letter G"><span>G</span></div>
                            <div class="card-letter O"><span>O</span></div>
                            <?php for ($i = 1; $i <= 25; $i++): ?>
                                <?php $isMarked = in_array($i, $positions); $showStar = ($isSing && $isMarked) || $i == 13; ?>
                                <?php if ($i == 13): ?>
                                    <div class="card-number" data-position="13">⭐️</div>
                                <?php else: ?>
                                    <div class="card-number <?= $isMarked ? 'modality-sing' : '' ?> <?= $isSing && $isMarked ? 'sing' : '' ?>" data-position="<?= $i; ?>"><?= $showStar ? '⭐️' : '' ?></div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <?php if ($game['award'] == 2) : ?>
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

<button class="btn btn-small btn-chat" id="toggle-messages-btn"><i class="fa-duotone fa-solid fa-comments-question"></i></button>

<div class="message-display-container<?= ($game['type'] == 3 || $game['type'] == 4) ? ' message-display-container--live' : '' ?>" id="message-display-container" aria-hidden="true">
    <div class="message-display-container__toolbar">
        <button type="button" class="message-display-close" id="message-display-close" aria-label="<?= translate('close'); ?>">
            <i class="fa-duotone fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="message-display" id="message-display" aria-live="polite"></div>
    <div class="emoji-message-panel">
        <div class="chat-quick-box" style="display: flex; flex-direction: row; gap: 12px; width: 100%; align-items: flex-start; padding: 4px 8px;">
            <div class="message-list" style="flex: 1; display: flex; flex-direction: column; gap: 10px; max-height: <?= ($game['type'] == 3 || $game['type'] == 4) ? '120px' : '190px' ?>; overflow-y: auto; padding-right: 6px; padding-bottom: 8px;">
                <button type="button" class="message-btn" style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;" onclick="sendEmoji('¡Oe, me falta solo una! 😱', 20)">¡Oe, me falta solo una! 😱</button>
                <button type="button" class="message-btn" style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;" onclick="sendEmoji('¡Bravo, salió mi número! 🥳', 21)">¡Bravo, salió mi número! 🥳</button>
                <button type="button" class="message-btn" style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;" onclick="sendEmoji('¡Este premio es mío! 🤑', 22)">¡Este premio es mío! 🤑</button>
                <button type="button" class="message-btn" style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;" onclick="sendEmoji('¡Suerte para todos! 🍀', 23)">¡Suerte para todos! 🍀</button>
                <button type="button" class="message-btn" style="white-space: normal; text-align: left; padding: 8px 12px; font-size: 0.8rem; line-height: 1.2;" onclick="sendEmoji('¡Mi Rey, Bingo! 👑', 24)">¡Mi Rey, Bingo! 👑</button>
            </div>
            <div class="emoji-grid" style="flex: 1; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; align-content: flex-start; max-height: <?= ($game['type'] == 3 || $game['type'] == 4) ? '120px' : '180px' ?>; overflow-y: auto;">
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('🥳', 1)">🥳</button>
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('🎉', 2)">🎉</button>
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('😎', 3)">😎</button>
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('🍀', 4)">🍀</button>
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('🤑', 5)">🤑</button>
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('🌟', 6)">🌟</button>
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('😡', 7)">😡</button>
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('🔥', 8)">🔥</button>
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('👑', 9)">👑</button>
                <button type="button" class="emoji-btn" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.2rem;" onclick="sendEmoji('💵', 10)">💵</button>
            </div>
        </div>
        <?php if ($game['type'] == 3 || $game['type'] == 4): ?>
            <div class="input-group live-chat-input" style="padding: 0 8px 4px;">
                <input type="text" class="form-control" id="message-send-new" placeholder="Escribe un mensaje..." aria-label="Escribe un mensaje" autocomplete="off" maxlength="500">
                <button class="btn btn-primary" type="button" id="btn-send-message-new"><i class="fa-duotone fa-solid fa-paper-plane-top"></i></button>
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="modal fade" id="modalBoard" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered max-w-45 max-w-50-xs mx-auto">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-table-cells"></i> <?= translate('board'); ?></h6>
                <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
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
                <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-triangle-exclamation"></i> <?= translate('Warning!'); ?></h6>
                <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body pt-0 text-center">
                <?= translate('if you exit the game you could lose your game data. We recommend you stay in the game.'); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary d-block w-50 btn-bingo mt-3 pe-2" id="cancelExit"><?= translate('cancel'); ?></button>
                <a href="javascript:void(0);" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="confirmExit">
                    <?= translate('accept'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="<?= site_url('assets/plyr/plyr.js'); ?>?<?= md5(date("Hms")); ?>"></script>

<script type="text/javascript">
    window.singBall = "<?= systemGet('singBall'); ?>";
    window.timeBallGet = singBall.split('-')[0];
    //window.timeBallLast = singBall.split('-')[1];
    window.timeBallLast = 2500;
    window.totalNumbersGenerated = <?= (int) ($totalNumbersGenerated ?? 0); ?>;
    window.fiveNumbers = <?= $lastNumbersJson ?? '[]' ?>;
    window.winners = <?= json_encode($winners) ?>;
    window.gameDate = '<?= $game["date"] ?> <?= $game["time"] ?>';
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
        let exitUrl = null;
        let allowUnload = window.allowGameUnload === true;
        let reloadAttempted = false;

        // Detectar actividad del usuario
        let lastActivity = Date.now();
        document.addEventListener('mousemove', () => { lastActivity = Date.now(); });
        document.addEventListener('keydown', () => { lastActivity = Date.now(); });
        document.addEventListener('click', () => { lastActivity = Date.now(); });
        
        // Botones de salida - Mostrar modal al hacer clic
        $('.btn-home, .btn-exit, .btn-back').on('click', function(e) {
            if (canLeaveGameWithoutWarning()) {
                return true;
            }
            e.preventDefault();
            exitUrl = $(this).attr('href') || $(this).data('href');
            if (typeof showBsModal === 'function') showBsModal('#modalExit');
        });

        // Confirmar salida desde el modal
        $('#confirmExit').on('click', function(e) {
            e.preventDefault();
            if (typeof hideBsModal === 'function') hideBsModal('#modalExit');
            
            // Permitir la salida/recarga
            allowUnload = true;
            window.allowGameUnload = true;
            
            if (reloadAttempted) {
                // Si fue un intento de recarga, recargar la página
                reloadAttempted = false;
                setTimeout(() => {
                    location.reload();
                }, 100);
            } else if (exitUrl) {
                // Si hay una URL específica, navegar a ella
                window.location.href = exitUrl;
            } else {
                // Si fue el botón atrás, permitir la navegación atrás
                history.back();
            }
        });

        // Cancelar salida
        $('#cancelExit').on('click', function() {
            if (typeof hideBsModal === 'function') hideBsModal('#modalExit');
            exitUrl = null;
            reloadAttempted = false;
            allowUnload = false;
        });

        // Interceptar botón atrás del navegador
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            if (canLeaveGameWithoutWarning()) {
                history.back();
                return;
            }
            history.pushState(null, null, location.href);
            if (typeof showBsModal === 'function') showBsModal('#modalExit');
        };
        
        // Interceptar F5/Ctrl+R
        window.addEventListener('keydown', function(e) {
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
        !function() {
            new Plyr("#plyr-video-player");
            document.getElementsByClassName("plyr")[0].style.borderRadius = "0px 0px 10px 10px";
            document.getElementsByClassName("plyr__poster")[0].style.display = "none";
            let e = document.getElementsByTagName("html")[0],
            t = document.querySelector(".stick-top");
            window.addEventListener("scroll", function() {
                e.classList.contains("layout-navbar-fixed") ? t.classList.add("course-content-fixed") : t.classList.remove("course-content-fixed")
            })
        } ();
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
