<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="description" content="<?= translate(strtolower(APP_NAME) . ' is an online bingo game designed for the whole family. Enjoy interactive games with virtual cards, automatic hype and the ability to play in real time from any device. Easy to use, no downloads'); ?>">
    <meta name="keywords" content="<?= translate(strtolower(APP_NAME) . ', online bingo game, bingo for the family, online bingo, play free online bingo, family bingo game, HTML and PHP bingo, interactive online bingo, bingo with virtual cards, bingo card generator, personalized bingo for families, bingo game with sound, online multiplayer bingo, real-time bingo, bingo for children and adults, create bingo cards online, bingo hype automatic, bingo number marker, responsive bingo game, PHP MySQL bingo, HTML5 bingo game, bingo with animations, online bingo on mobile devices, bingo for family reunions, free online bingo without download'); ?>">
    <meta name="author" content="IsAppWeb">
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" href="<?= asset_url('img/favicon.ico'); ?>" type="image/x-icon" sizes="512x512">
    
    <link rel="apple-touch-icon" href="<?= asset_url('img/192x192.png'); ?>" sizes="192x192">
    <link rel="apple-touch-icon" href="<?= asset_url('img/256x256.png'); ?>" sizes="256x256">
    <link rel="apple-touch-icon" href="<?= asset_url('img/384x384.png'); ?>" sizes="384x384">
    <link rel="apple-touch-icon" href="<?= asset_url('img/512x512.png'); ?>" sizes="512x512">

    <link rel="manifest" href="<?= asset_url('img/site.webmanifest.json'); ?>">
    <meta name="theme-color" content="#ffffff">

    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?= asset_url('img/192x192.png'); ?>">

    <link rel="mask-icon" href="<?= asset_url('img/safari-pinned-tab.svg'); ?>" color="#6236ff">

    <meta property="og:title" content="<?= APP_NAME; ?>">
    <meta property="og:site_name" content="<?= APP_NAME; ?>">
    <meta property="og:description" content="<?= translate(strtolower(APP_NAME) . ' is an online bingo game designed for the whole family. Enjoy interactive games with virtual cards, automatic hype and the ability to play in real time from any device. Easy to use, no downloads'); ?>">
    <meta property="og:image" content="<?= asset_url('img/logo-512x512.png'); ?>">
    <meta property="og:url" content="https://www.bingo.hubbills.com/">
    <meta property="og:type" content="website">

    <title><?= APP_NAME; ?> · <?= $page['title'] ?></title>

    <!-- CSS -->
    <link href="<?= asset_url('icons/css/all.css') ?>" rel="stylesheet"> 
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&display=swap" rel="stylesheet">
    <link href="<?= asset_url('bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="<?= asset_url('css/toastify.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/bingo.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/sweetalert.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('plugin/components/font-awesome/css/fontawesome.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('plugin/czm-chat-support.css') ?>" rel="stylesheet">
</head>
<body class="bg-gradient-bingo">
<?= view('layout/_layout_user_bootstrap') ?>
    <div class="preloader">
        <div class="canvas">
            <img src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid" alt="img" style="width: 250px;">
            <div class="loading-progress-bar">
                <div class="loading-progress"></div>
            </div>
            <div class="loading-percentage">1%</div>
        </div>
    </div>

    <div class="container-fluid" id="content-page">
        <?php
            if (isset($contentPage) && $contentPage != "") {
                echo $contentPage;
            }
        ?>
    </div>

    <div class="notifications" id="notificationsContainer"></div>
    <span class="notification-indicator hidden" id="notificationIndicator"></span>

    <?php if (session()->get('logged_in')) : ?>
        <input type="hidden" name="sounds" id="sounds" value="<?= $user['sounds']; ?>">
        <input type="hidden" name="narration" id="narration" value="<?= $user['narration']; ?>">
        <input type="hidden" name="autodial" id="autodial" value="<?= $user['autodial']; ?>">
    <?php else : ?>
        <input type="hidden" name="sounds" id="sounds" value="0">
    <?php endif; ?>

    <!-- Agregar esto a tu vista principal -->
    <div id="notification-banner" class="notification-banner hidden" style="display: none;">
        <div class="banner-content">
            <div class="banner-icon">🔔</div>
            <div class="banner-text">
                <h4>¡No te pierdas ningún juego!</h4>
                <p>Activa las notificaciones para recibir alertas de nuevas partidas de bingo</p>
            </div>
            <div class="banner-buttons">
                <button id="enable-notifications" class="btn btn-primary">
                    Activar Notificaciones
                </button>
                <button id="dismiss-banner" class="btn btn-secondary">
                    Ahora no
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de explicación -->
    <div id="notification-modal" class="modal fade hidden" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🔔 Notificaciones de Bingo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="<?= site_url('assets/img/logo.png'); ?>" alt="img" class="img-fluid w-50">
                    </div>
                    <h6>¿Qué notificaciones recibirás?</h6>
                    <ul>
                        <li>✅ Nuevas partidas de bingo disponibles</li>
                        <li>🎯 Recordatorios antes de que inicie un juego</li>
                        <li>🏆 Resultados de partidas</li>
                        <li>💰 Premios ganados</li>
                    </ul>
                    <div class="alert alert-info">
                        <small>
                            <strong>Tu privacidad es importante:</strong> Solo recibirás notificaciones relacionadas con el bingo. 
                            Puedes desactivarlas en cualquier momento.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="confirm-notifications" class="btn btn-primary">
                        🔔 Sí, activar notificaciones
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .notification-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }

        .banner-content {
            display: flex;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            gap: 15px;
        }

        .banner-icon {
            font-size: 2rem;
        }

        .banner-text h4 {
            margin: 0;
            font-size: 1.1rem;
        }

        .banner-text p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .banner-buttons {
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .banner-content {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
    </style>

    <div class="modal fade" id="wallet" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalStatistics" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalUser" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalUserDetails" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= translate('user details'); ?></h5>
                    <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body" id="userDetailsContent"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalGames" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalAwards" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalPlayers" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalPayments" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalRequest" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalVoucher" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalReferrals" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalAvailableCartons" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalDeposit" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalRetire" tabindex="-1" role="dialog"></div>
    
    <div class="modal fade" id="modalTransfer" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalSettings" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalBank" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalAddgame" tabindex="-1" role="dialog"></div>

    <div class="modal fade" id="modalAddmodality" tabindex="-1" role="dialog"></div>

    <?php if (session()->get('logged_in') && systemGet('activateCompleteProfile') == 1) : ?>
        <div class="modal fade" id="modalactivateCompleteProfile" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered max-w-50">
                <div class="modal-content">
                    <div class="modal-body">
                        <?php echo form_open(site_url() . 'users/profileSubmit', array('enctype' => 'multipart/form-data', 'id' => 'profile-form'));?>
                        
                            <?= csrf_field() ?>
                            
                            <div class="row" id="profile-step-1">
                                <div class="col-md-3 my-auto text-center">
                                    <div class="col-md-12">
                                        <div class="profile-picture">
                                            <img id="profileImage" src="<?= $imagePath ?>" alt="img">
                                            <label for="fileInput" class="edit-button"><i class="fa-duotone fa-solid fa-camera"></i></label>
                                            <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="previewImage(event)">
                                            <input type="hidden" id="profile_image_input" name="image">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-9">
                                    <div class="col-md-12 mb-1">
                                        <label for="firstname" class="form-label"><?= translate('first name'); ?></label>
                                        <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="firstname" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('first name')); ?>" autofocus autocomplete="off" value="<?= $user['firstname']; ?>">
                                        <small id="firstname-error" class="text-danger d-none"></small>
                                    </div>

                                    <div class="col-md-12 mb-1">
                                        <label for="lastname" class="form-label"><?= translate('last name'); ?></label>
                                        <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="lastname" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('last name')); ?>" autocomplete="off" value="<?= $user['lastname']; ?>">
                                        <small id="lastname-error" class="text-danger d-none"></small>
                                    </div>

                                    <div class="col-md-12 mb-1">
                                        <label for="document" class="form-label"><?= translate('document'); ?></label>
                                        <input type="number" class="form-control form-control-lg form-bingo" name="document" id="document" placeholder="<?= translate('document'); ?>" autocomplete="off" value="<?= $user['document']; ?>">
                                        <small id="document-error" class="text-danger d-none"></small>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <button type="button" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="profile-step-button"><?= translate('continue'); ?></button>
                                </div>
                            </div>

                            <div class="row" id="profile-step-2" style="display: none;">
                                <div class="col-md-12 mb-1">
                                    <label for="username" class="form-label"><?= translate('username'); ?></label>
                                    <input type="text" class="form-control form-control-lg form-bingo" name="username" id="username" placeholder="<?= translate('username'); ?>" autocomplete="off" value="<?= $user['username']; ?>">
                                    <small id="username-error" class="text-danger d-none"></small>
                                </div>

                                <div class="col-md-12 mb-1">
                                    <label for="phone" class="form-label"><?= translate('phone'); ?></label>
                                    <input type="number" class="form-control form-control-lg form-bingo" name="phone" id="phone" placeholder="<?= translate('phone'); ?>" autocomplete="off" value="<?= $user['phone']; ?>">
                                    <small id="phone-error" class="text-danger d-none"></small>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="email" class="form-label"><?= translate('email'); ?></label>
                                    <input type="text" class="form-control form-control-lg form-bingo" name="email" id="email" placeholder="<?= translate('email'); ?>" autocomplete="off" value="<?= $user['email']; ?>">
                                    <small id="email-error" class="text-danger d-none"></small>
                                </div>

                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary d-block w-50 btn-bingo" id="profile-button"><?= translate('update'); ?></button>
                                </div>
                            </div>
                        <?= form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="modalSharegame" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div class="action-sheet-content">
                        <div class="text-center" >
                            <img id="flyerImg" src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid w-50" alt="Flyer" style="border-top-left-radius: 30px; border-top-right-radius: 30px;">
                        </div>
                
                        <div class="text-center p-2 hidden">
                            <h5>
                                ✅ Comparte este flyer en tu estado de WhatsApp (mín. 50 vistas)<br />
                                ✅ Síguenos y etiqueta a @<?= systemGet('accountInstagram'); ?> en tus historias de Instagram
                            </h5>
                        </div>
                
                        <div class="text-center">
                            <a href="https://api.whatsapp.com/send?text=🎉 ¡Registrate en <?= APP_NAME; ?> 🎱 y participa en la ruleta de premios! Este 🕢 <?= translate_day(lastGame('date') . ' ' . lastGame('time')) ?> 🗓️ <?= translate_date(lastGame('date')) ?>, tenemos partidas con 💵 *¡<?= systemGet('currency'); ?> <?= lastGame('total'); ?> en premios!* por solo *<?= systemGet('currency'); ?> <?= lastGame('price'); ?>*. 🌐 <?= site_url('signup'); ?>" target="_blank" class="btn btn-success btn-bingo mt-2" style="width: 300px;">Enviar por WhatsApp</a>
            
                            <a href="<?= site_url('assets/img/flyer.jpg'); ?>"  download="logo.jpg" class="btn btn-secondary btn-bingo mt-2 hidden" style="width: 300px;">📥 Descargar Flyer</a>
            
                            <button onclick="shareNativeFlyer()" class="btn btn-primary btn-bingo mt-2 mb-3" style="width: 300px;">📤 Compartir</button>

                            <button type="button" class="btn btn-primary btn-bingo mt-2 mb-3" style="width: 200px; display: inline-block;" aria-label="close" data-bs-dismiss="modal"><?= translate('close'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        * {
            box-sizing: border-box;
        }
        .wrapper {
            text-align: center;
            width: 100%;
            max-width: 600px;
        }
        .wrapper canvas {
            width: 100%;
            height: auto;
            display: block;
            background: white;
            border-radius: 50%;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
            border: 10px solid #fff;
        }
        #result {
            margin-top: 20px;
            font-size: 24px;
            color: #fff;
            text-shadow: 2px 2px 4px #000;
        }
        .btn-spin {
            background: #ff3c38;
            color: #fff;
        }
        .confetti {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 9999;
            opacity: 0.8;
            animation: explode 3s forwards;
        }
        @keyframes explode {
            0% {
                transform: translateY(-100vh) rotate(0deg);
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
            }
        }
        .player-bonus-message {
            background: linear-gradient(45deg, #ffcc70, #ff8a00);
            color: #fff;
            text-align: center;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            font-family: 'Arial Black', sans-serif;
        }
        .player-bonus-message h4 {
            margin: 0;
            font-size: 24px;
        }
        .player-bonus-message p {
            margin: 5px 0 0;
            font-size: 16px;
        }

        #confetti-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }

        .confetti-piece {
            position: absolute;
            font-size: 20px;
            user-select: none;
            pointer-events: none;
            will-change: transform, opacity;
            transform-origin: center;
        }

        /* Animaciones optimizadas para móviles */
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg) scale(1);
                opacity: 1;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(100vh) rotate(720deg) scale(0.5);
                opacity: 0;
            }
        }

        @keyframes confetti-swing {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-20px);
            }
            75% {
                transform: translateX(20px);
            }
        }

        @keyframes confetti-bounce {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
        }

        /* Clases de animación */
        .confetti-fall {
            animation: confetti-fall linear forwards;
        }

        .confetti-swing {
            animation: confetti-swing ease-in-out infinite;
        }

        .confetti-bounce {
            animation: confetti-bounce ease-in-out infinite;
        }

        /* Botón de ejemplo */
        .confetti-btn {
            position: fixed;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 30px;
            font-size: 18px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            border: none;
            border-radius: 25px;
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }

        .confetti-btn:active {
            transform: translateX(-50%) scale(0.95);
        }

        /* Optimizaciones para dispositivos móviles */
        @media (max-width: 768px) {
            .confetti-piece {
                font-size: 16px;
            }
            
            .confetti-btn {
                padding: 12px 25px;
                font-size: 16px;
            }
        }

        /* Reducir animaciones si el usuario prefiere menos movimiento */
        @media (prefers-reduced-motion: reduce) {
            .confetti-piece {
                animation-duration: 1s !important;
            }
        }
    </style>
  
    <div class="modal fade" id="modalactivateRoulette" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center">
                        <div class="player-bonus-message">
                            <h4>¡Felicitaciones! 🎁</h4>
                            <h6>Has desbloqueado una oportunidad especial por completar tu perfil. ¡Gira la ruleta y reclama tu premio!</h6>
                        </div>
                        <div class="wrapper">
                            <canvas id="wheel" width="500" height="500"></canvas>
                            <div>
                                <button class="btn btn-primary btn-bingo btn-spin mt-3" style="width: 200px; display: inline-block;" id="spin">GIRAR RULETA</button>
                                <h1 id="result"></h1>
                                <button class="btn btn-primary btn-bingo btn-spin mt-2" id="claimBtn" onclick="claimPrize();"width: 200px; style="display: none;">RECLAMAR</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalJoingroup" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="action-sheet-content">
                        <div class="text-center">
                            <img src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid w-50" alt="img">
                        </div>
                        <div class="text-center p-2">
                            <h4>📲 ¡ÚNETE AL GRUPO!</h4>

                            <!-- Botón para unirse al grupo de WhatsApp -->
                            <a href="<?= systemGet('linkGroup'); ?>" target="_blank" class="btn btn-secondary btn-bingo mt-2" style="width: 300px;">
                            📥 Unirme
                            </a>

                            <!-- Botón para compartir por WhatsApp -->
                            <a href="https://api.whatsapp.com/send?text=🎉 ¡Registrate en <?= APP_NAME; ?> 🎱 y participa en la ruleta de premios! Este 🕢 <?= translate_day(lastGame('date') . ' ' . lastGame('time')) ?> 🗓️ <?= translate_date(lastGame('date')) ?>, tenemos partidas con 💵 *¡<?= systemGet('currency'); ?> <?= lastGame('total'); ?> en premios!* por solo *<?= systemGet('currency'); ?> <?= lastGame('price'); ?>*. 🌐 <?= site_url('signup'); ?>" target="_blank" class="btn btn-success btn-bingo mt-2" style="width: 300px;">
                            Compartir por WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="confetti-container"></div>
    
    <script src="<?= asset_url('js/in-app-notifications.js') ?>"></script>
    <script type="text/javascript">
        var site_url = "<?= site_url(); ?>";
        var currency = "<?= systemGet('currency'); ?>";
        var audioPath = "<?= site_url('assets/sounds/'); ?>";
        
        <?php if (session()->get('logged_in')) : ?>
            var imagePath = "<?= $imagePath; ?>";
            var narrationPlaying = <?= $user['narration'] == 1 ? 'true' : 'false' ?>;
            var autoMarkEnabled = <?= $user['autodial'] == 1 ? 'true' : 'false' ?>;
        <?php endif; ?>

        var __ = [];
        __['bingo!'] = "<?= translate('bingo!'); ?>";
        __['add bank'] = "<?= translate('add bank'); ?>";
        __['add'] = "<?= translate('add'); ?>";
        __['game finished!'] = "<?= translate('game finished!'); ?>";
        __['game started!'] = "<?= translate('game started!'); ?>";
        __['the game is initiated'] = "<?= translate('the game is initiated'); ?>";
        __['the game is finished'] = "<?= translate('the game is finished'); ?>";

        let soundnotify;
        let hasPlayedSound = false;

        <?php if (session()->get('logged_in')) : ?>
            const notificationConfig = {
                checkInterval: 10000,
                displayTime: 15000,
                maxNotifications: 3,
                apiUrl: '/users/userNotifications',
                markReadUrl: '/users/markNotificationRead',
                testUrl: '/users/testNotification'
            };

            // Función para inicializar el audio con compatibilidad iOS/Android
            function initializeAudio(src) {
                if (!soundnotify) {
                    soundnotify = new Audio();
                    
                    // Configuración específica para iOS
                    soundnotify.preload = 'auto';
                    soundnotify.crossOrigin = 'anonymous';
                    
                    // Event listeners para mejor compatibilidad
                    soundnotify.addEventListener('canplaythrough', () => {
                        console.log('Audio ready to play');
                    });
                    
                    soundnotify.addEventListener('error', (e) => {
                        console.error('Audio error:', e);
                    });
                }
                
                soundnotify.src = src;
                soundnotify.volume = 0.7; // Volumen más alto para iOS
                soundnotify.loop = false; // Nunca en loop para evitar repetición
                
                return soundnotify;
            }

            // Función mejorada para reproducir audio con compatibilidad iOS/Android
            async function playNotificationSound(type) {
                if (hasPlayedSound) return; // Si ya se reprodujo, no reproducir de nuevo
                
                try {
                    let audioSrc;
                    if (type === 'sing') {
                        audioSrc = audioPath + 'winner.mp3';
                    } else {
                        audioSrc = audioPath + 'success.mp3';
                    }
                    
                    const audio = initializeAudio(audioSrc);
                    
                    // Para iOS: crear una promesa que maneje el play
                    const playPromise = audio.play();
                    
                    if (playPromise !== undefined) {
                        await playPromise;
                        hasPlayedSound = true; // Marcar que ya se reprodujo
                        
                        // Resetear el flag después de un tiempo
                        setTimeout(() => {
                            hasPlayedSound = false;
                        }, 2000);
                        
                        console.log('Audio played successfully');
                    }
                } catch (error) {
                    console.log("Audio play failed:", error);
                    
                    // Fallback para iOS: intentar con Web Audio API
                    if (window.AudioContext || window.webkitAudioContext) {
                        try {
                            await playWithWebAudio(type);
                        } catch (webAudioError) {
                            console.log("Web Audio API also failed:", webAudioError);
                        }
                    }
                }
            }

            // Fallback con Web Audio API para iOS
            async function playWithWebAudio(type) {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                const audioContext = new AudioContext();
                
                let audioSrc = type === 'sing' ? audioPath + 'winner.mp3' : audioPath + 'success.mp3';
                
                try {
                    const response = await fetch(audioSrc);
                    const arrayBuffer = await response.arrayBuffer();
                    const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
                    
                    const source = audioContext.createBufferSource();
                    const gainNode = audioContext.createGain();
                    
                    source.buffer = audioBuffer;
                    gainNode.gain.value = 0.7;
                    
                    source.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    source.start(0);
                    hasPlayedSound = true;
                    
                    setTimeout(() => {
                        hasPlayedSound = false;
                    }, 2000);
                    
                } catch (error) {
                    console.error('Web Audio API error:', error);
                }
            }

            window.loadNotifications = async function loadNotifications() {
                try {
                    const response = await fetch(notificationConfig.apiUrl);
                    const data = await response.json();

                    const notifications = data.notifications || [];
                    const wallet = data.wallet ?? null;
                    const games = data.games ?? null;
                    const progress = data.progress ?? null;

                    if (notifications.length > 0) {
                        showNotificationIndicator();
                        
                        // Resetear el flag de sonido para esta nueva carga
                        hasPlayedSound = false;
                        
                        // Limitar a máximo 5 notificaciones
                        const limitedNotifications = notifications.slice(0, notificationConfig.maxNotifications);
                        
                        // Determinar el tipo de sonido a reproducir (priorizar 'sing')
                        let soundType = 'default';
                        const hasSingNotification = limitedNotifications.some(n => n.type === 'sing');
                        const hasGameNotification = limitedNotifications.some(n => n.type === 'game');
                        
                        if (hasSingNotification) {
                            soundType = 'sing';
                        } else if (hasGameNotification) {
                            soundType = 'game';
                        }

                        // Reproducir sonido UNA SOLA VEZ para todas las notificaciones
                        if (limitedNotifications.length > 0) {
                            await playNotificationSound(soundType);
                        }

                        // Procesar cada notificación
                        limitedNotifications.forEach(notification => {
                            showNotification(notification);
                            markAsRead(notification.id);

                            if (notification.transaction && isPaymentsModalOpen()) {
                                addPaymentRowToModal(notification.transaction);
                            }

                            // Efectos especiales solo para tipo 'sing'
                            if (notification.type === 'sing') {
                                AppcreateConfetti();
                            } else if (notification.type === 'game') {
                                <?php if ($page['title'] == translate('list of') . ' ' . translate('games')) : ?>
                                    gameslistGet();
                                <?php endif; ?>
                            }
                        });
                    } else {
                        hideNotificationIndicator();
                    }

                    if (wallet !== null) {
                        availableWallet(wallet);
                    }

                    if (games !== null) {
                        updateGames(games);
                    }

                    if (progress !== null) {
                        <?php if ($page['title'] == translate('list of') . ' ' . translate('games')) : ?>
                            updateProgressGames(progress);
                        <?php endif; ?>
                    }
                } catch (error) {
                    console.error('Error al cargar notificaciones:', error);
                }
            }

            window.showNotification = function showNotification(notification) {
                const container = document.getElementById('notificationsContainer');
                if (!container) {
                    return;
                }

                if (notification.id && container.querySelector(`[data-notification-id="${notification.id}"]`)) {
                    return;
                }
                
                while (container.children.length >= notificationConfig.maxNotifications) {
                    const oldestNotification = container.firstChild;
                    if (oldestNotification) {
                        hideNotification(oldestNotification);
                    } else {
                        break;
                    }
                }
                
                const notificationEl = document.createElement('div');
                notificationEl.className = `notification notification-${notification.type || 'default'}`;
                if (notification.id) {
                    notificationEl.dataset.notificationId = notification.id;
                }
                notificationEl.innerHTML = `
                    <div class="notification-header">
                        <h6 class="notification-title">${notification.title}</h6>
                    </div>
                    <div class="notification-message">${notification.message}</div>
                    <span class="notification-hint">Desliza a la derecha para cerrar</span>
                    <span class="notification-time mt-1">${formatTime(notification.created_at)}</span>
                `;
                
                container.appendChild(notificationEl);
                
                setTimeout(() => {
                    notificationEl.classList.add('show');
                }, 100);

                attachNotificationSwipeDismiss(notificationEl, hideNotification);
                
                notificationEl._autoHideTimer = setTimeout(() => {
                    hideNotification(notificationEl);
                }, notificationConfig.displayTime);
            }

            // Función para habilitar audio en iOS (llamar en interacción del usuario)
            function enableAudioForIOS() {
                if (!soundnotify) {
                    soundnotify = new Audio();
                    soundnotify.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTuJ0fPTgjMGHm7A7+OZURE';
                    soundnotify.volume = 0;
                    soundnotify.play().catch(() => {});
                }
            }

            function isPaymentsModalOpen() {
                const modal = document.getElementById('modalPayments');
                return modal && modal.classList.contains('show');
            }

            function play(path) {
                const audio = new Audio(path);
                audio.play().catch(error => {
                    console.error("Error al reproducir audio:", error);
                });
            }

            function addPaymentRowToModal(transaction) {
                const tbody = document.querySelector('#modalPayments #payments-tbody');
                if (!tbody) return;

                const statusElement = document.getElementById(`${transaction.type}-${transaction.id}`);
                if (statusElement) {
                    statusElement.innerHTML = `<span class="status-badge" data-status="${transaction.status_raw}">${transaction.status_formatted}</span>`;
                }
                
                // Verificar si la transacción ya existe en la tabla
                const existingRow = tbody.querySelector(`tr[data-id="${transaction.id}"][data-type="${transaction.type}"]`);
                if (existingRow) return; // Ya existe, no agregar duplicado
                
                // Remover el mensaje "no transactions found" si existe
                const notList = tbody.querySelector('#not-list');
                if (notList) notList.remove();
                
                const typeIcons = {
                    'deposit': '<i class="fa-duotone fa-solid fa-arrow-down-to-line text-success"></i>',
                    'retire': '<i class="fa-duotone fa-solid fa-arrow-up-from-bracket icon-danger"></i>',
                    'transfer': '<i class="fa-duotone fa-solid fa-arrow-right-arrow-left text-info"></i>',
                    'payment': '<i class="fa-duotone fa-solid fa-credit-card text-primary"></i>'
                };

                const typeIcon = typeIcons[transaction.type] || '<i class="fa-duotone fa-solid fa-circle-question text-warning"></i>';
                const amountClass = transaction.type === 'retire' ? 'icon-danger' : 'text-success';
                const amountSign = transaction.type === 'retire' ? '-' : '+';

                let row = `
                    <tr data-id="${transaction.id}" data-type="${transaction.type}">
                        <td class="text-center">
                            ${typeIcon}
                            <br>
                            <small class="text-muted">${transaction.type_Tra}</small>
                        </td>
                        <td>
                            <strong>${escapeHtml(transaction.reference)}</strong>
                            <br>
                            <small class="text-muted">${transaction.date_formatted}</small>
                        </td>
                `;

                <?php if (session()->get('group') == 1) : ?>
                row += `
                        <td>
                            <strong>${escapeHtml(transaction.user_code)}</strong>
                            <br>
                            <small class="text-muted">${escapeHtml(transaction.user_name)}</small>
                        </td>
                `;
                <?php endif; ?>

                row += `<td>${transaction.bank}</td>`;

                var user_id = '<?= session()->get('id'); ?>';

                // Manejar el monto según el tipo de transacción
                let amountHtml = '';
                if (transaction.type === 'retire') {
                    amountHtml = `
                        <strong class="icon-danger">
                            -<?= systemGet('currency'); ?> ${formatNumber(transaction.amount)}
                        </strong>
                    `;
                } else if (transaction.type === 'transfer') {
                    <?php if (session()->get('group') == 1) : ?>
                        amountHtml = `
                            <div>
                                <strong class="icon-danger d-block">
                                    -<?= systemGet('currency'); ?> ${formatNumber(transaction.amount)}
                                </strong>
                                <strong class="text-success d-block">
                                    +<?= systemGet('currency'); ?> ${formatNumber(transaction.amount)}
                                </strong>
                            </div>
                        `;
                    <?php else: ?>
                        if (user_id != transaction.user_id) {
                            amountHtml = `
                                <div>
                                    <strong class="icon-danger d-block">
                                        -<?= systemGet('currency'); ?> ${formatNumber(transaction.amount)}
                                    </strong>
                                </div>
                            `;
                        } else {
                            amountHtml = `
                                <div>
                                    <strong class="text-success d-block">
                                        +<?= systemGet('currency'); ?> ${formatNumber(transaction.amount)}
                                    </strong>
                                </div>
                            `;
                        }
                    <?php endif; ?>
                } else {
                    amountHtml = `
                        <strong class="text-success">
                            +<?= systemGet('currency'); ?> ${formatNumber(transaction.amount)}
                        </strong>
                    `;
                }

                row += `
                    <td class="text-center">
                        ${amountHtml}
                    </td>
                    <td class="text-center">
                        <small>${transaction.created_at}</small>
                    </td>
                    <td class="text-center" id="${transaction.type}-${transaction.id}">
                        <span class="status-badge" data-status="${transaction.status_raw}">
                            ${transaction.status_formatted}
                        </span>
                    </td>
                `;

                <?php if (session()->get('group') == 1) : ?>
                row += `
                    <td class="text-center">
                        <a class="btn btn-primary btn-modal text-white" onclick="requestGet('${transaction.type}', '${transaction.id}')">
                            <i class="fa-duotone fa-solid fa-eye"></i>
                        </a>
                    </td>
                `;
                <?php endif; ?>

                row += `</tr>`;

                tbody.insertAdjacentHTML('afterbegin', row);
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function formatNumber(num) {
                return parseFloat(num).toFixed(2);
            }

            /*function showNotification(notification) {
                const container = document.getElementById('notificationsContainer');
                
                // Limitar el número de notificaciones visibles
                if (container.children.length >= notificationConfig.maxNotifications) {
                    const oldestNotification = container.firstChild;
                    oldestNotification.classList.add('hide');
                    setTimeout(() => oldestNotification.remove(), 500);
                }
                
                // Determinar el tipo de notificación
                let notificationType = 'info';
                if (notification.title.toLowerCase().includes('bingo')) {
                    notificationType = 'bingo';
                } else if (notification.title.toLowerCase().includes('ganaste')) {
                    notificationType = 'success';
                } else if (notification.title.toLowerCase().includes('advertencia')) {
                    notificationType = 'warning';
                }
                
                // Crear elemento de notificación
                const notificationEl = document.createElement('div');
                //notificationEl.className = `notification ${notificationType}`;
                notificationEl.className = `notification`;
                notificationEl.innerHTML = `
                    <div class="notification-header">
                        <h6 class="notification-title">${notification.title}</h6>
                    </div>
                    <div class="notification-message">${notification.message}</div>
                    <button class="notification-close" onclick="closeNotification(this)">X</button>
                    <span class="notification-time mt-1">${formatTime(notification.created_at)}</span>
                `;
                
                container.appendChild(notificationEl);
                
                // Mostrar notificación con animación
                setTimeout(() => {
                    notificationEl.classList.add('show');
                }, 100);
                
                // Ocultar automáticamente después del tiempo configurado
                setTimeout(() => {
                    hideNotification(notificationEl);
                }, notificationConfig.displayTime);
            }*/

            function availableWallet(wallet) {
                const elements = document.querySelectorAll('.available-wallet');
                elements.forEach(el => {
                    // Solo actualizar si el valor es diferente al actual
                    if (el.textContent !== wallet.toString()) {
                        el.textContent = wallet;
                    }
                });
            }

            const gameTimers = new Map();

            function parseLocalDateTime(date, time) {
                try {
                    const [y, m, d] = date.split('-').map(Number);
                    const [hh = 0, mm = 0, ss = 0] = (time || '00:00:00').split(':').map(Number);
                    return new Date(y, (m - 1), d, hh || 0, mm || 0, ss || 0, 0);
                } catch (e) {
                    return null;
                }
            }

            function calculateTimeRemaining(date, time) {
                let targetDate = parseLocalDateTime(date, time);
                if (!targetDate || isNaN(targetDate)) return '';

                const now = new Date();
                const timeDiff = targetDate.getTime() - now.getTime();

                if (timeDiff <= 0) return '¡EL JUEGO YA INICIÓ!';

                const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);

                if (days > 0) {
                    return `INICIA EN: ${days} DÍA${days > 1 ? 'S' : ''} ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
                } else if (hours > 0) {
                    return `INICIA EN: ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
                } else {
                    if (minutes === 0) {
                        const sec = Math.max(0, seconds);
                        return `INICIA EN: ${sec} SEGUNDO${sec === 1 ? '' : 'S'}`;
                    } else {
                        return `INICIA EN: ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MINUTO${minutes === 1 ? '' : 'S'}`;
                    }
                }
            }

            function ensureCountdown(game) {
                const timeElId = `card-time-${game.id}`;
                const btnElPlayId  = `card-button-play-${game.id}`;
                const btnElBuyId  = `card-button-buy-${game.id}`;

                const timeEl = document.getElementById(timeElId);
                const btnElBuy  = document.getElementById(btnElBuyId);
                const btnElPlay  = document.getElementById(btnElPlayId);

                if (!timeEl) return;

                if (gameTimers.has(game.id)) return;

                const tick = () => {
                    const text = calculateTimeRemaining(game.date, game.time);
                    timeEl.textContent = text;

                    const started = text === '¡EL JUEGO YA INICIÓ!';

                    if (btnElBuy) {
                        if (started) {
                            btnElBuy.disabled = true;
                            btnElBuy.classList.add('disabled');
                            btnElBuy.textContent = 'Jugando...';
                        } else {
                            btnElBuy.disabled = false;
                            btnElBuy.classList.remove('disabled');
                            btnElBuy.textContent = '<?= translate('buy cartons'); ?>';
                        }
                    }

                    if (btnElPlay) {
                        if (started) {
                            if (game.cartons >= 1) {
                                btnElPlay.disabled = false;
                                btnElPlay.classList.remove('disabled');
                                btnElPlay.textContent = '<?= translate('come in to play'); ?>';
                            } else {
                                btnElPlay.disabled = true;
                                btnElPlay.classList.add('disabled');
                                btnElPlay.textContent = 'No disponible';
                            }
                        } else {
                            if (game.cartons >= 1) {
                                btnElPlay.disabled = false;
                                btnElPlay.classList.remove('disabled');
                                btnElPlay.textContent = '<?= translate('come in to play'); ?>';
                            } else {
                                btnElPlay.disabled = true;
                                btnElPlay.classList.add('disabled');
                                btnElPlay.textContent = '<?= translate('come in to play'); ?>';
                            }
                        }
                    }

                    if (started) {
                        const t = gameTimers.get(game.id);
                        if (t) {
                            clearInterval(t);
                            gameTimers.delete(game.id);
                        }
                    }
                };

                // Primera ejecución
                tick();

                // Intervalo por card
                const timerId = setInterval(tick, 1000);
                gameTimers.set(game.id, timerId);
            }

            function removeGameCard(gameId) {
                const card = document.querySelector(`.card-game-${gameId}`);
                if (card && card.parentNode) card.parentNode.removeChild(card);

                const t = gameTimers.get(gameId);
                if (t) {
                    clearInterval(t);
                    gameTimers.delete(gameId);
                }
            }

            function renderOrUpdateCard(game, cardsContainer) {
                let buttonsHtml = '';
                let card = document.querySelector(`.card-game-${game.id}`);
                if (!card) {
                    card = document.createElement('div');
                    card.className = `card ${game.color} text-center card-game-${game.id}`;
                    
                    if (game.cartons >= 1) {
                        buttonsHtml = `<button type="submit" class="btn btn-small btn-primary d-block w-100 btn-bingo mb-1" id="card-button-play-${game.id}" onclick="gameGet(${game.id});"><?= translate('come in to play'); ?></button><button type="submit" class="btn btn-small btn-primary d-block w-100 btn-bingo bingo-bg-success card-button-buy" id="card-button-buy-${game.id}" onclick="generateCartonsGet(${game.id});"><?= translate('buy cartons'); ?></button>`;
                    } else {
                        buttonsHtml = `<button type="submit" class="btn btn-small btn-primary d-block w-100 btn-bingo mb-1"  id="card-button-play-${game.id}" onclick="gameGet(${game.id});" disabled><?= translate('come in to play'); ?></button><button type="submit" class="btn btn-small btn-primary d-block w-100 btn-bingo bingo-bg-success card-button-buy" id="card-button-buy-${game.id}" onclick="generateCartonsGet(${game.id});"><?= translate('buy cartons'); ?></button>`;
                    }

                    card.innerHTML = `
                        <span class="card-hour">${game.time_translate}</span>
                        <span class="card-price"><?= translate('carton'); ?>: <?= systemGet('currency'); ?> ${game.price}</span>
                        <img src="<?= site_url('assets/img/logo.png'); ?>" class="card-img-top p-1" alt="img">
                        <div class="card-body p-1">
                            <h5 class="card-title text-center mb-0">
                                ${game.room}
                                <div class="scrolling-text">
                                    <span>${game.description}</span>
                                    <span>${game.description}</span>
                                </div>
                            </h5>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="p-0" style="font-size: 0.8rem;">${game.date_translate}</li>
                            <li class="p-0" id="card-accumulated-${game.id}">Premio: <?= systemGet('currency'); ?> ${game.accumulated}</li>
                            <li class="p-0" style="font-size: 0.7rem;" id="card-time-${game.id}"></li>
                        </ul>
                        <div class="card-body p-1">
                            ${buttonsHtml}
                        </div>`;
                    cardsContainer.appendChild(card);
                } else {
                    const accEl = document.getElementById(`card-accumulated-${game.id}`);
                    if (accEl) accEl.textContent = `Premio: <?= systemGet('currency'); ?> ${game.accumulated}`;
                }

                ensureCountdown(game);
            }

            function updateProgressGames(gamesProgress) {
                // Validar que gamesProgress existe y es un array
                if (!gamesProgress || !Array.isArray(gamesProgress)) {
                    console.warn('gamesProgress no es válido:', gamesProgress);
                    return;
                }

                gamesProgress.forEach(game => {
                    // Validar que el objeto game tiene las propiedades necesarias
                    if (!game || !game.game_id || game.percentage === undefined) {
                        return;
                    }

                    // Buscar el contenedor del progreso
                    const progressElement = document.getElementById(`game-progress-${game.game_id}`);

                    if (!progressElement) {
                        return;
                    }

                    const statusElement = document.getElementById(`game-status-${game.game_id}`);

                    if (statusElement) {
                        statusElement.innerHTML = game.status;
                    }

                    const playersElement = document.getElementById(`game-players-${game.game_id}`);

                    if (playersElement) {
                        playersElement.innerHTML = game.players;
                    }

                    const totalElement = document.getElementById(`game-total-${game.game_id}`);

                    if (totalElement) {
                        totalElement.innerHTML = game.total;
                    }

                    // Buscar la barra dentro del contenedor
                    const progressBar = progressElement.querySelector('.progress-bar');
                    if (!progressBar) {
                        return;
                    }

                    try {
                        // Obtener el porcentaje actual
                        const currentWidth = progressBar.style.width;
                        const currentPercentage = currentWidth ? parseFloat(currentWidth.replace('%', '')) : 0;
                        const newPercentage = parseFloat(game.percentage) || 0;

                        // Solo actualizar si hay cambios significativos
                        if (Math.abs(currentPercentage - newPercentage) > 0.1) {
                            // Actualizar barra
                            progressBar.style.width = `${newPercentage}%`;

                            // Actualizar texto del porcentaje
                            const progressText = progressBar.querySelector('.progress-text');
                            if (progressText) {
                                progressText.textContent = `${newPercentage}%`;
                            }

                            // Actualizar contador de números
                            const progressNumbers = progressBar.querySelector('.progress-numbers');
                            if (progressNumbers) {
                                progressNumbers.textContent = `${game.numbers_called || 0}/75`;
                            }

                            // Actualizar atributos de accesibilidad
                            progressElement.setAttribute('aria-valuenow', newPercentage);

                            // Efecto visual
                            progressElement.classList.add('progress-updated');
                            setTimeout(() => {
                                if (progressElement) {
                                    progressElement.classList.remove('progress-updated');
                                }
                            }, 1000);

                            // Actualizar color según porcentaje
                            updateProgressColor(progressBar, newPercentage);
                        }
                    } catch (error) {
                        console.error(`Error actualizando progreso del juego ${game.game_id}:`, error);
                    }
                });
            }

            function updateProgressColor(progressBar, percentage) {
                if (!progressBar || !progressBar.classList) {
                    console.warn('progressBar inválido:', progressBar);
                    return;
                }

                try {
                    // Remover clases previas
                    progressBar.classList.remove(
                        'bingo-bg-success',
                        'bingo-bg-orange',
                        'bingo-bg-primary',
                        'bingo-bg-info'
                    );

                    // Aplicar nueva clase según porcentaje
                    if (percentage >= 90) {
                        progressBar.classList.add('bingo-bg-success');
                    } else if (percentage >= 70) {
                        progressBar.classList.add('bingo-bg-orange');
                    } else if (percentage >= 40) {
                        progressBar.classList.add('bingo-bg-info');
                    } else {
                        progressBar.classList.add('bingo-bg-primary');
                    }
                } catch (error) {
                    console.error('Error actualizando color de progreso:', error);
                }
            }

            function updateGames(games) {
                if (games.length <= 0) {
                    $('.play-cards').html(`<h3 class="no_active_rooms"><?= translate('there are no active rooms'); ?></h3>`);
                } else {
                    $('.play-cards .no_active_rooms').remove();
                }
                <?php if ($page['title'] == translate('start game')) : ?>
                    <?php if (systemGet('activateRoomCards') == 0) : ?>
                        const select = document.getElementById('game');
                        games.forEach(game => {
                            if (!select.querySelector(`option[value="${game.id}"]`)) {
                                const option = document.createElement('option');
                                option.value = game.id;
                                option.textContent = game.label;
                                select.appendChild(option);
                            }
                        });
                    <?php else : ?>
                        const cardsContainer = document.querySelector('.play-cards');

                        games.forEach(game => renderOrUpdateCard(game, cardsContainer));

                        const currentIds = new Set(games.map(g => String(g.id)));

                        const existingCards = cardsContainer.querySelectorAll('[class*="card-game-"]');
                        existingCards.forEach(card => {
                            const classMatch = Array.from(card.classList).find(c => c.startsWith('card-game-'));
                            if (!classMatch) return;

                            const domId = classMatch.replace('card-game-', '');
                            if (!currentIds.has(domId)) {
                              removeGameCard(domId);
                            }
                        });
                    <?php endif; ?>
                <?php endif; ?>
            }

            function removeGameCard(gameId) {
                const card = document.querySelector(`.card-game-${gameId}`);
                if (card && card.parentNode) card.parentNode.removeChild(card);
                    const t = gameTimers.get(gameId);
                if (t) {
                    clearInterval(t);
                    gameTimers.delete(gameId);
                }
            }

            function hideNotification(notificationEl) {
                if (!notificationEl || !notificationEl.parentNode) {
                    return;
                }
                if (notificationEl._autoHideTimer) {
                    clearTimeout(notificationEl._autoHideTimer);
                    notificationEl._autoHideTimer = null;
                }
                notificationEl.classList.add('hide');
                setTimeout(() => {
                    if (notificationEl.parentNode) {
                        notificationEl.remove();
                    }
                }, 300);
            }

            // Función para marcar notificación como leída
            async function markAsRead(notificationId) {
                try {
                    const response = await fetch(notificationConfig.markReadUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ id: notificationId })
                    });
                    
                    if (!response.ok) {
                        console.error('Error al marcar notificación como leída');
                    }
                } catch (error) {
                    console.error('Error en la petición:', error);
                }
            }

            // Función para mostrar indicador de notificaciones
            function showNotificationIndicator() {
                //document.getElementById('notificationIndicator').style.display = 'block';
            }

            // Función para ocultar indicador de notificaciones
            function hideNotificationIndicator() {
                //document.getElementById('notificationIndicator').style.display = 'none';
            }

            // Función para formatear la hora
            function formatTime(dateString) {
                // Asegura compatibilidad reemplazando espacio por 'T'
                const isoString = dateString.replace(' ', 'T');
                const date = new Date(isoString);
                const now = new Date();

                const diffMs = now - date;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMins / 60);
                const diffDays = Math.floor(diffHours / 24);

                if (diffMins < 1) return 'Ahora';
                if (diffMins < 60) return `Hace ${diffMins} min`;
                if (diffHours < 24) return `Hace ${diffHours} h`;
                if (diffDays === 1) return 'Hace 1 día';
                if (diffDays < 7) return `Hace ${diffDays} días`;

                // Si ya pasó más de una semana, muestra la fecha
                return date.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }

            // Iniciar el sistema de notificaciones
            document.addEventListener('DOMContentLoaded', function() {
                // Habilitar audio para iOS en la primera interacción
                document.addEventListener('touchstart', enableAudioForIOS, { once: true });
                document.addEventListener('click', enableAudioForIOS, { once: true });
                
                loadNotifications();
                setInterval(loadNotifications, notificationConfig.checkInterval);
            });
        <?php endif; ?>

        $(document).ready(function() {
            $('#profile-step-button').on('click', function() {
    
                var button = $('#profile-step-button');
                button.prop("disabled", true); 
        
                $('.text-danger').addClass('d-none').text('');
                $('.form-control').removeClass('is-invalid');
    
                var formData = {
                    firstname: $('#firstname').val(),
                    lastname: $('#lastname').val(),
                    document: $('#document').val()
                };
    
                $.ajax({
                    url: "<?= site_url('users/profileStepSubmit') ?>",
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#profile-step-1').hide();
                            $('#profile-step-2').show();
                        } else {
                            if (response.errors) {
                                $.each(response.errors, function(field, message) {
                                    $('#' + field + '-error').text(message).removeClass('d-none');
                                    $('#' + field).addClass('is-invalid');
                                });
                            }
                        }
                    },
                    error: function() {
                        Toastify({
                            text: "<?= translate('there was an error in the request to the server'); ?>",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#dc3545" },
                            stopOnFocus: true
                        }).showToast();
                    },
                    complete: function() {
                        button.prop("disabled", false);
                    }
                });
            });
    
            $('#profile-form').on('submit', function(e) {
                e.preventDefault();
        
                var button = $('#profile-button');
                button.prop("disabled", true);
        
                $('.text-danger').addClass('d-none').text('');
                $('.form-control').removeClass('is-invalid');
    
                var formData = new FormData(this);
                formData.append('profile_image', $('#fileInput')[0].files[0]);
    
                $.ajax({
                    url: "<?= site_url('users/profileSubmit') ?>",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#modalactivateCompleteProfile').modal('hide');
                            <?php if (session()->get('logged_in') && $user['roulette'] == 0 && systemGet('activateRoulette') == 1) : ?>
                                modalRoulette = 'modalactivateRoulette';
                                const modal = new bootstrap.Modal(document.getElementById(modalRoulette));
                                modal.show();
                            <?php endif; ?>
                            Toastify({
                                text: "<?= translate('profile'); ?> <?= translate('updated successfully'); ?>",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#198754" },
                                stopOnFocus: true
                            }).showToast();
                        } else {
                            if (response.error) {
                                Toastify({
                                    text: response.error,
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    style: { background: "#dc3545" },
                                    stopOnFocus: true
                                }).showToast();
                            }
                            
                            if (response.errors) {
                                $.each(response.errors, function(field, message) {
                                    $('#' + field + '-error').text(message).removeClass('d-none');
                                    $('#' + field).addClass('is-invalid');
                                });
                            }
                        }
                    },
                    error: function() {
                        Toastify({
                            text: "<?= translate('there was an error in the request to the server'); ?>",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#dc3545" },
                            stopOnFocus: true
                        }).showToast();
                    },
                    complete: function() {
                        button.prop("disabled", false);
                    }
                });
            });
        });
    
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('profileImage');
                output.src = reader.result;
    
                document.getElementById('profile_image_input').value = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function shareNativeFlyer() {
            const flyerURL = "<?= site_url('assets/img/logo.png'); ?>";
            const text = "🎉 ¡Registrate en <?= APP_NAME; ?> 🎱 y participa en la ruleta de premios! Este 🕢 <?= translate_day(lastGame('date') . ' ' . lastGame('time')) ?> 🗓️ <?= translate_date(lastGame('date')) ?>, tenemos partidas con 💵 *¡<?= systemGet('currency'); ?> <?= lastGame('total'); ?> en premios!* por solo *<?= systemGet('currency'); ?> <?= lastGame('price'); ?>*. 🌐 <?= site_url('signup'); ?>";
        
            if (navigator.share) {
              navigator.share({
                title: '<?= APP_NAME; ?>',
                text: `${text} ${flyerURL}`,
                url: flyerURL
              })
              .then(() => console.log('Compartido correctamente'))
              .catch((error) => console.log('Error al compartir', error));
            } else {
              alert('Tu navegador no soporta el uso nativo de compartir.');
            }
        }
        
        <?php if (session()->get('logged_in') && systemGet('activateShareGame') == 1) : ?>
            document.addEventListener("DOMContentLoaded", function () {
                modalShare = 'modalSharegame';

                if (!localStorage.getItem('activateShareGame')) {
                    const modal = new bootstrap.Modal(document.getElementById(modalShare));
                    modal.show();
                    localStorage.setItem('activateShareGame', 'yes');
                }
            });
        <?php endif; ?>

        <?php if (systemGet('activateJoinGroup') == 1) : ?>
            document.addEventListener("DOMContentLoaded", function () {
                modalGroup = 'modalJoingroup';

                if (!localStorage.getItem('activateJoinGroup')) {
                    const modal = new bootstrap.Modal(document.getElementById(modalGroup));
                    modal.show();
                    localStorage.setItem('activateJoinGroup', 'yes');
                }
            });
        <?php endif; ?>

        <?php if (session()->get('logged_in') && systemGet('activateCompleteProfile') == 1) : ?>
            <?php if (empty($user['document']) || empty($user['firstname']) || empty($user['lastname']) || empty($user['username']) || empty($user['email']) || empty($user['phone'])) : ?>
                document.addEventListener("DOMContentLoaded", function () {
                    modalProfile = 'modalactivateCompleteProfile';

                    //if (!localStorage.getItem('activateCompleteProfile')) {
                        const modal = new bootstrap.Modal(document.getElementById(modalProfile));
                        modal.show();
                        //localStorage.setItem('activateCompleteProfile', 'yes');
                    //}
                });
            <?php endif; ?>
        <?php endif; ?>

        <?php if (session()->get('logged_in') && $user['roulette'] == 0 && systemGet('activateRoulette') == 1) : ?>
            <?php if (!empty($user['document']) && !empty($user['firstname']) && !empty($user['lastname']) && !empty($user['username']) && !empty($user['email']) && !empty($user['phone'])) : ?>
                document.addEventListener("DOMContentLoaded", function () {
                    modalRoulette = 'modalactivateRoulette';

                    if (!localStorage.getItem('SheetSpin')) {
                        const modal = new bootstrap.Modal(document.getElementById(modalRoulette));
                        modal.show();
                        localStorage.setItem('SheetSpin', 'yes');
                    }
                });
            <?php endif; ?>
        <?php endif; ?>

        const MAX_CONFETTIS = 100;

        function createConfetti() {
            const emojis = ['🎉', '🎊', '✨', '🌟', '🥳', '🍾', '💥', '🔥', '💫', '🍬', '🎈'];
            for (let i = 0; i < MAX_CONFETTIS; i++) {
                const confetti = document.createElement("div");
                confetti.classList.add("confetti");
                confetti.textContent = emojis[Math.floor(Math.random() * emojis.length)];
                confetti.style.left = Math.random() * 100 + "vw";
                confetti.style.top = Math.random() * -20 + "vh";
                confetti.style.fontSize = (Math.random() * 30 + 10) + "px";
                confetti.style.animationDuration = (Math.random() * 5 + 1) + "s";
                confetti.style.animationDelay = Math.random() + "s";
                document.body.appendChild(confetti);
                confetti.addEventListener("animationend", () => confetti.remove());
            }
        }

        const prizes = [
            "1 CARTÓN", "2 CARTONES", "3 CARTONES", "4 CARTONES", "5 CARTONES", "10 CARTONES",
            "INTENTA DE NUEVO", "SUERTE LA PRÓXIMA VEZ"
        ];

        const wheel = document.getElementById("wheel");
        const ctx = wheel.getContext("2d");
        const spinBtn = document.getElementById("spin");
        const claimBtn = document.getElementById("claimBtn"); // ✅ Definir aquí
        const result = document.getElementById("result");

        const totalSegments = 30;
        const segmentAngle = (2 * Math.PI) / totalSegments;

        let angles = [];
        let colors = [];
        let segments = [];

        function generateSegments() {
            segments = [];
            let iphoneIncluded = false;
            for (let i = 0; i < totalSegments; i++) {
                if (!iphoneIncluded && Math.random() < 0.05) {
                    segments.push("10 CARTONES");
                    iphoneIncluded = true;
                } else {
                    segments.push(prizes[Math.floor(Math.random() * prizes.length)]);
                }
            }
            if (!iphoneIncluded) {
                segments[Math.floor(Math.random() * totalSegments)] = "10 CARTONES";
            }
        }

        generateSegments();

        function getRandomColor() {
            const hue = Math.floor(Math.random() * 360);
            return `hsl(${hue}, 85%, 65%)`;
        }

        for (let i = 0; i < totalSegments; i++) {
            angles.push(i * segmentAngle);
            colors.push(getRandomColor());
        }

        function drawWheel(rotation = 0) {
            ctx.clearRect(0, 0, wheel.width, wheel.height);
            const centerX = wheel.width / 2;
            const centerY = wheel.height / 2;
            const radius = wheel.width / 2;

            for (let i = 0; i < totalSegments; i++) {
                const startAngle = angles[i] + rotation;
                const endAngle = startAngle + segmentAngle;

                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                ctx.fillStyle = colors[i];
                ctx.fill();
                ctx.stroke();

                ctx.save();
                ctx.translate(centerX, centerY);
                ctx.rotate(startAngle + segmentAngle / 2);
                ctx.textAlign = "right";
                ctx.fillStyle = "black";
                ctx.font = "bold 12px sans-serif";
                ctx.fillText(segments[i], radius - 10, 5);
                ctx.restore();
            }

            ctx.beginPath();
            ctx.moveTo(centerX - 10, 10);
            ctx.lineTo(centerX + 10, 10);
            ctx.lineTo(centerX, 40);
            ctx.fillStyle = "#d63031";
            ctx.fill();
        }

        drawWheel();

        function spinWheel() {
            spinBtn.disabled = true;
            let rotation = 0;
            let speed = Math.random() * 0.3 + 0.4;
            let deceleration = 0.985;

            const spinning = setInterval(() => {
                rotation += speed;
                speed *= deceleration;
                drawWheel(rotation);

                if (speed < 0.002) {
                    clearInterval(spinning);

                    const finalAngle = rotation % (2 * Math.PI);
                    const angleAtPointer = (3 * Math.PI / 2 - finalAngle + 2 * Math.PI) % (2 * Math.PI);
                    let index = Math.floor(angleAtPointer / segmentAngle) % totalSegments;

                    // Si el premio es 10 CARTONES, forzar selección de otro premio
                    while (segments[index] === "10 CARTONES") {
                        index = (index + 1) % totalSegments;
                    }

                    const selectedPrize = segments[index];

                    if (selectedPrize !== "INTENTA DE NUEVO" && selectedPrize !== "SUERTE LA PRÓXIMA VEZ") {
                        result.textContent = `🎉 TU PREMIO: ${selectedPrize} 🎉`;
                    } else {
                        result.textContent = `${selectedPrize}`;
                    }

                    if (selectedPrize !== "INTENTA DE NUEVO" && selectedPrize !== "SUERTE LA PRÓXIMA VEZ") {
                        AppcreateConfetti(); // ✅ Corregido
                        spinBtn.style.display = "none";
                        claimBtn.style.display = "inline-block";
                        claimBtn.style.width = "200px";
                        claimBtn.disabled = false;

                        // ✅ Extraer número de cartones (singular y plural)
                        let cartons = 0;
                        
                        // Buscar patrón "X CARTONES" (plural)
                        let match = selectedPrize.match(/^(\d+)\s+CARTONES$/);
                        if (match) {
                            cartons = parseInt(match[1]);
                        } else {
                            // Buscar patrón "1 CARTÓN" (singular)
                            match = selectedPrize.match(/^(\d+)\s+CARTÓN$/);
                            if (match) {
                                cartons = parseInt(match[1]);
                            }
                        }
                        
                        claimBtn.dataset.cartons = cartons;
                        console.log('Cartones asignados:', cartons, 'Premio:', selectedPrize); // ✅ Debug
                        
                    } else {
                        claimBtn.style.display = "none";
                        spinBtn.disabled = false;
                    }
                }
            }, 20);
        }

        spinBtn.addEventListener("click", spinWheel);

        function claimPrize() {
            const cartons = parseInt(claimBtn.dataset.cartons) || 0; // ✅ Usar la referencia global y parsear

            console.log('Reclamando cartones:', cartons); // ✅ Debug

            if (cartons === 0) {
                Toastify({
                    text: "Error: No se detectó el número de cartones",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
                return;
            }

            claimBtn.disabled = true;
            claimBtn.textContent = 'Procesando...';

            $.ajax({
                url: "<?= site_url('playings/claimPrize') ?>",
                method: 'POST',
                data: { cartons: cartons },
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                        $('#modalactivateRoulette').modal('hide');
                        Toastify({
                            text: data.message,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#198754" },
                            stopOnFocus: true
                        }).showToast();
                        claimBtn.style.display = 'none';
                    } else {
                        Toastify({
                            text: "<?= translate('there was an error in the request to the server'); ?>",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#dc3545" },
                            stopOnFocus: true
                        }).showToast();
                        claimBtn.disabled = false;
                        claimBtn.textContent = 'RECLAMAR PREMIO';
                    }
                },
                error: function() {
                    Toastify({
                        text: "<?= translate('there was an error in the request to the server'); ?>",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                    claimBtn.disabled = false;
                    claimBtn.textContent = 'RECLAMAR PREMIO';
                }
            });
        }
    </script>     
    
    <div class="modal fade" id="modalactivateInstallPWA" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="action-sheet-content">
                        <div class="text-center">
                            <img src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid w-50" alt="img">
                        </div>
                        <div class="text-center p-2">
                            <h4>¡Vive la emoción de <?= APP_NAME; ?> en tu celular!</h4>
                            <h6>Instala ahora y disfruta de partidas emocionantes, premios increíbles y diversión sin límites, ¡en cualquier momento y lugar!</h6>
                        </div>
                        <div class="row">
                            <button type="button" class="btn btn-primary btn-bingo mt-2" style="width: 200px; display: inline-block;" id="install-pwa"><?= translate('install'); ?></button>
                            <button type="button" class="btn btn-primary btn-bingo mt-2" style="width: 200px; display: inline-block;" id="close-pwa"><?= translate('close'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        
    <script type="text/javascript">
        <?php if (systemGet('activateInstallPWA') == 1) : ?>
            document.addEventListener('DOMContentLoaded', () => {
                const modalId = 'modalactivateInstallPWA';
                const modal = new bootstrap.Modal(document.getElementById(modalId));
                let deferredPrompt;

                function setCookie(name, value, days) {
                    let expires = '';
                    if (days) {
                        const date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        expires = '; expires=' + date.toUTCString();
                    }
                    document.cookie = name + '=' + value + expires + '; path=/';
                }

                function getCookie(name) {
                    const nameEQ = name + '=';
                    const cookies = document.cookie.split(';');
                    for (let i = 0; i < cookies.length; i++) {
                        let cookie = cookies[i];
                        while (cookie.charAt(0) === ' ') {
                            cookie = cookie.substring(1, cookie.length);
                        }
                        if (cookie.indexOf(nameEQ) === 0) {
                            return cookie.substring(nameEQ.length, cookie.length);
                        }
                    }
                    return null;
                }

                function showInstallPrompt() {
                    if (!getCookie('bingoInstallIgnored') && !window.matchMedia('(display-mode: standalone)').matches) {
                        modal.show();
                    }
                }

                window.addEventListener('beforeinstallprompt', (e) => {
                    if (!getCookie('bingoInstallIgnored')) {
                        e.preventDefault(); 
                        deferredPrompt = e; 
                        showInstallPrompt();
                    }
                });

                document.getElementById('install-pwa').addEventListener('click', () => {
                    if (deferredPrompt) {
                        deferredPrompt.prompt(); 
                        deferredPrompt.userChoice.then((choiceResult) => {
                            if (choiceResult.outcome === 'accepted') {
                                console.log('Usuario aceptó instalar la PWA');
                            } else {
                                console.log('Usuario rechazó instalar la PWA');
                            }
                            deferredPrompt = null;
                            modal.hide();
                        });
                    }
                });

                document.getElementById('close-pwa').addEventListener('click', () => {
                    setCookie('bingoInstallIgnored', 'true', 365); 
                    modal.hide();
                });

                if (window.matchMedia('(display-mode: standalone)').matches) {
                    console.log('La PWA ya está instalada');
                } else if (!getCookie('bingoInstallIgnored')) {
                    setTimeout(showInstallPrompt, 2000);
                }
            });
        <?php endif; ?>
    </script>
    
    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= asset_url('bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <script src="<?= asset_url('js/toastify.js') ?>"></script>
    <script src="<?= asset_url('js/sweetalert.js') ?>"></script>

    <!-- <script src="<?= site_url('assets/js/notifications.js'); ?>"></script>-->

    <script src="<?= asset_url('plugin/components/moment/moment.min.js') ?>"></script>
    <script src="<?= asset_url('plugin/components/moment/moment-timezone-with-data.min.js') ?>"></script>
    <script src="<?= asset_url('plugin/czm-chat-support.min.js') ?>"></script>
    <script src="https://www.paypalobjects.com/api/checkout.js"></script>

    <script type="text/javascript">
        $('#whatsapp-plugin').czmChatSupport({
            /* Button Settings */
            button: {
                position: "right", /* left, right or false. "position:false" does not pin to the left or right */
                style: 1, /* Button style. Number between 1 and 7 */
                src: '<i class="fab fa-whatsapp"></i>', /* Image, Icon or SVG */
                backgroundColor: "#10c379", /* Html color code */
                effect: 1, /* Button effect. Number between 1 and 7 */
                notificationNumber: "1", /* Custom text or false. To remove, (notificationNumber:false) */
                /*speechBubble: "<?= translate('how can we help you?'); ?>", To remove,*/
                speechBubble:false, 
                pulseEffect: true, /* To remove, (pulseEffect:false) */
                text: { /* For Button style larger than 1 */
                    title: "<?= translate('do you need help? talk to us'); ?>", /* Writing is required */
                    description: "<?= translate('customer service'); ?>", /* To remove, (description:false) */
                    online: "<?= translate('online'); ?>", /* To remove, (online:false) */
                    offline: "<?= translate('offline'); ?>" /* To remove, (offline:false) */
                }
            },
        
            /* Popup Settings */
            popup: {
                automaticOpen: false, /* true or false (Open popup automatically when the page is loaded) */
                outsideClickClosePopup: true, /* true or false (Clicking anywhere on the page will close the popup) */
                effect: 1, /* Popup opening effect. Number between 1 and 15 */
                header: {
                    backgroundColor: "#10c379", /* Html color code */
                    title: "<?= translate('do you need help? talk to us'); ?>", /* Writing is required */
                    description: "<?= translate('one of our representatives will assist you'); ?>" /* To remove, (description:false) */
                },
        
                /* Representative Settings */
                persons: [

                    <?php foreach ($contacts as $contact): ?>
                    {
                        avatar: {
                            src: '<img src="<?= site_url('assets/img/person/' . $contact['id'] . '.svg'); ?>" alt="img">', /* Image, Icon or SVG */
                            backgroundColor: "#ffffff", /* Html color code */
                            onlineCircle: true /* Avatar online circle. To remove, (onlineCircle:false) */
                        },
                        text: {
                            title: "<?= $contact['name'] ?>", /* Writing is required */
                            description: "<?= $contact['charge'] ?>", /* To remove, (description:false) */
                            online: "<?= translate('online'); ?>", /* To remove, (online:false) */
                            offline: "<?= translate('offline'); ?>", /* To remove, (offline:false) */
                            button: "<?= translate('start chat'); ?>"
                        },
                        link: {
                            desktop: "https://web.whatsapp.com/send?phone=<?= $contact['phone'] ?>&text=<?= translate('hello'); ?> <?= $contact['name'] ?>, <?= translate('i need more information'); ?> <?= APP_NAME; ?> 🎱.", /* Writing is required */
                            mobile: "https://wa.me/<?= $contact['phone'] ?>/?text=<?= translate('hello'); ?> <?= $contact['name'] ?>, <?= translate('i need more information'); ?>." /* If it is hidden desktop link will be valid. To remove, (mobile:false) */
                        },
                        onlineDay: {
                            /* Change the day you are offline like this. (sunday:false) */
                            sunday: "00:00-23:59",
                            monday: "00:00-23:59",
                            tuesday: "00:00-23:59",
                            wednesday: "00:00-23:59",
                            thursday: "00:00-23:59",
                            friday: "00:00-23:59",
                            saturday: "00:00-23:59"
                        }
                    },
                    <?php endforeach; ?>

                ],
            },
        
            /* Other Settings */
            sound: false, /* true (default sound), false or custom sound. Custom sound example, (sound:'assets/sound/notification.mp3') */
            changeBrowserTitle: false, /* Custom text or false. To remove, (changeBrowserTitle:false) */
            cookie: false, /* It does not show the speech bubble, notification number, and pulse effect again for the specified time. For example, do not show for 1 hour, (cookie:1) or to remove, (cookie:false) */
        });

        let wakeLock = null;

        async function requestWakeLock() {
            try {
                wakeLock = await navigator.wakeLock.request('screen');
                console.log('wake Lock activated');

                wakeLock.addEventListener('release', () => {
                    console.log('wake Lock released');
                });
            } catch (err) {
                console.error(`${err.name}, ${err.message}`);
            }
        }

        // Solicita el wake lock cuando la página se carga
        document.addEventListener('DOMContentLoaded', () => {
            if ('wakeLock' in navigator) {
                requestWakeLock();

                // Reintenta si el dispositivo se vuelve visible tras estar oculto (por ejemplo, al volver a la pestaña)
                document.addEventListener('visibilitychange', () => {
                    if (wakeLock !== null && document.visibilityState === 'visible') {
                        requestWakeLock();
                    }
                });
            } else {
                console.warn('the Wake Lock API is not supported in this browser.');
            }
        });
    </script>

    <script>
        const emojis = ['🎉', '🎊', '✨', '🌟', '🥳', '🍾', '💥', '🔥', '💫', '🍬', '🎈'];
        let confettiContainer;
        let activeConfetti = [];
        let confettiTimeout; // Variable para controlar el timeout

        function isMobile() {
            return window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        function AppcreateConfetti(count = null) {
            if (!confettiContainer) {
                confettiContainer = document.getElementById('confetti-container');
            }

            // Limpiar confeti anterior si hay demasiado
            if (activeConfetti.length > 50) {
                clearOldConfetti();
            }

            // Limpiar timeout anterior si existe
            if (confettiTimeout) {
                clearTimeout(confettiTimeout);
            }

            const confettiCount = count || (isMobile() ? 25 : 40);
            const screenWidth = window.innerWidth;

            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    createSingleConfetti(screenWidth);
                }, i * 100);
            }

            // Programar la limpieza automática después de 10 segundos
            confettiTimeout = setTimeout(() => {
                clearAllConfetti();
            }, 10000); // 10 segundos = 10000 milisegundos
        }

        function createSingleConfetti(screenWidth) {
            // Crear elemento
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            
            // Emoji aleatorio
            const emoji = emojis[Math.floor(Math.random() * emojis.length)];
            piece.textContent = emoji;
            
            // Posición inicial
            const startX = Math.random() * screenWidth;
            piece.style.left = startX + 'px';
            piece.style.top = '-50px';
            
            // Agregar al contenedor
            confettiContainer.appendChild(piece);
            activeConfetti.push(piece);

            // Animación con JavaScript para mejor control
            animateConfetti(piece);
        }

        function animateConfetti(piece) {
            let posY = -50;
            let posX = parseFloat(piece.style.left);
            let rotation = 0;
            let opacity = 1;
            let scale = 0.8 + Math.random() * 0.4;
            
            // Parámetros de movimiento
            const fallSpeed = 2 + Math.random() * 3;
            const swingSpeed = (Math.random() - 0.5) * 2;
            const rotationSpeed = (Math.random() - 0.5) * 10;
            const screenHeight = window.innerHeight;

            function animate() {
                posY += fallSpeed;
                posX += Math.sin(posY * 0.01) * swingSpeed;
                rotation += rotationSpeed;
                
                // Fade out cerca del final
                if (posY > screenHeight * 0.8) {
                    opacity -= 0.02;
                }

                // Aplicar transformaciones
                piece.style.transform = `translate(${posX - parseFloat(piece.style.left)}px, ${posY}px) rotate(${rotation}deg) scale(${scale})`;
                piece.style.opacity = opacity;

                // Continuar animación o limpiar
                if (posY < screenHeight + 100 && opacity > 0) {
                    requestAnimationFrame(animate);
                } else {
                    removeConfettiPiece(piece);
                }
            }

            requestAnimationFrame(animate);
        }

        function removeConfettiPiece(piece) {
            if (piece && piece.parentNode) {
                piece.parentNode.removeChild(piece);
                const index = activeConfetti.indexOf(piece);
                if (index > -1) {
                    activeConfetti.splice(index, 1);
                }
            }
        }

        function clearOldConfetti() {
            // Limpiar la mitad del confeti más antiguo
            const toRemove = activeConfetti.splice(0, Math.floor(activeConfetti.length / 2));
            toRemove.forEach(piece => {
                if (piece && piece.parentNode) {
                    piece.parentNode.removeChild(piece);
                }
            });
        }

        function clearAllConfetti() {
            activeConfetti.forEach(piece => {
                if (piece && piece.parentNode) {
                    piece.parentNode.removeChild(piece);
                }
            });
            activeConfetti = [];
            
            // Limpiar el timeout si existe
            if (confettiTimeout) {
                clearTimeout(confettiTimeout);
                confettiTimeout = null;
            }
        }
    </script>
    <?php if (ENVIRONMENT === 'development' && session()->get('logged_in')) : ?>
    <script src="<?= asset_url('js/notification-dev.js') ?>"></script>
    <?php endif; ?>
</body>
</html>