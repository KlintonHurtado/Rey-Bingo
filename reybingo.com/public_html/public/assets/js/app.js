(function () {
    var APP_LOADED_KEY = 'reybingo-app-loaded';
    var preloaderInterval = null;

    function markAppLoaded() {
        try {
            sessionStorage.setItem(APP_LOADED_KEY, '1');
        } catch (e) {}
    }

    function wasAppLoadedInSession() {
        try {
            return sessionStorage.getItem(APP_LOADED_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function hidePreloader(delayMs) {
        var preloader = document.querySelector('.preloader');
        if (!preloader) {
            return;
        }

        if (preloaderInterval) {
            clearInterval(preloaderInterval);
            preloaderInterval = null;
        }

        var hide = function () {
            preloader.style.display = 'none';
            document.documentElement.classList.add('reybingo-skip-preloader');
            markAppLoaded();
        };

        if (delayMs > 0) {
            setTimeout(hide, delayMs);
        } else {
            hide();
        }
    }

    window.hideReyBingoPreloader = function (immediate) {
        hidePreloader(immediate ? 0 : 500);
    };

    window.addEventListener('pageshow', function (event) {
        if (event.persisted || wasAppLoadedInSession()) {
            hidePreloader(0);
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && wasAppLoadedInSession()) {
            hidePreloader(0);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var preloader = document.querySelector('.preloader');
        var loadingProgressBar = document.querySelector('.loading-progress');
        var loadingPercentage = document.querySelector('.loading-percentage');

        if (!preloader || !loadingProgressBar || !loadingPercentage) {
            return;
        }

        if (wasAppLoadedInSession()) {
            hidePreloader(0);
            return;
        }

        function removePreloader() {
            hidePreloader(500);
        }

        window.addEventListener('load', removePreloader, { once: true });

        var fakeLoadingProgress = 0;
        preloaderInterval = setInterval(function () {
            fakeLoadingProgress += 10;
            loadingProgressBar.style.width = fakeLoadingProgress + '%';
            loadingPercentage.textContent = fakeLoadingProgress + '%';
            if (fakeLoadingProgress >= 100) {
                clearInterval(preloaderInterval);
                preloaderInterval = null;
                removePreloader();
            }
        }, 100);
    });
})();

var App = function() {
    
    var uiInit = function () {
        linkPage();
    };
    
    $(document).ready(function() {
        generateStars();
    
        function generateStars() {
            const modalBg = $('.efecto-bingo');
            let intervalId = setInterval(function () {
                let moneda = $('<div class="moneda"></div>');
                let randomX = Math.floor(Math.random() * window.innerWidth);
                let randomY = Math.floor(Math.random() * window.innerHeight);
                
                // Definir un tamaño aleatorio entre 10px y 60px
                let randomSize = Math.floor(Math.random() * 150) + 10;
                
                // Posicionar y dimensionar la estrella aleatoriamente
                moneda.css({
                    top: randomY + 'px',
                    left: randomX + 'px',
                    width: randomSize + 'px',
                    height: randomSize + 'px',
                    transform: `translate(${randomX}px, ${randomY}px)`  // Control de la expansión
                });
    
                modalBg.append(moneda);
    
                // Eliminar la estrella después de la animación
                setTimeout(function() {
                    moneda.remove();
                }, 20000);
            }, 20);  // Intervalo para generar estrellas
    
            // Limpiar las estrellas cuando el modal se cierra
            $('#login').on('hidden.bs.modal', function () {
                clearInterval(intervalId);
            });
    
            // Limpiar las estrellas cuando el modal se cierra
            $('#jugar').on('hidden.bs.modal', function () {
                clearInterval(intervalId);
            });
        }
    
        let soundtrack;  // Variable para el audio de fondo
        let audioStarted = false;  // Para evitar que el soundtrack se reproduzca más de una vez
    
        // Función para iniciar el soundtrack
        function startSoundtrack() {
            if (!audioStarted) {
                if (!soundtrack) soundtrack = new Audio();
                soundtrack.src = audioPath + 'gamemusic.mp3';
                soundtrack.volume = 0.5;
                soundtrack.loop = true;  // Hacer que el audio se repita
                soundtrack.play().catch(error => {
                    console.log("Autoplay prevented. User interaction needed.");
                });
                audioStarted = true;
            }
        }
    
        // Función para activar/desactivar el soundtrack
        $('.btn-volume').click(function() {
            if (soundtrack && !soundtrack.paused) {
                soundtrack.pause();
                $(this).html('<i class="fa-duotone fa-solid fa-volume-slash"></i>');
            } else {
                if (!soundtrack) {
                    startSoundtrack();
                } else {
                    soundtrack.play();
                }
                $(this).html('<i class="fa-duotone fa-solid fa-volume"></i>');
            }
        });
    
        // Reproduce el soundtrack automáticamente cuando se hace clic en la página
        function playSound() {
            startSoundtrack();
            document.removeEventListener('click', playSound);
            $('.volume').html('<i class="fa-duotone fa-solid fa-volume"></i>');
        }
    
        // Añadir el event listener para reproducir el soundtrack al hacer clic en la página
        const userSoundsAuto = document.querySelector(`#sounds`);

        if (userSoundsAuto.value == 1) {
            document.addEventListener('click', playSound);
        }
    });
    
    var linkPage = function () {
        $('.linkPage').click(function (e) {
            e.preventDefault();
            checkURL($(this).attr('href'));
        });
    };
    
    var checkURL = function (hash) {
        if (!hash) hash = window.location.hash;
        lasturl = hash;
        loadPage(hash);
    };
    
    var loadPage = function (url) {
        $.ajax({
            type: "GET", 
            url: url, 
            dataType: "html",
            success: function (data) {
                $('#content-page').html(data);
            },
            error: function () {
                $('#content-page').html('<p>Error al cargar la página.</p>');
            }
        });
    };

    document.addEventListener("DOMContentLoaded", function () { 
        function ViewSliders() {
            let hiddenButtons = document.querySelectorAll(".btn-volume, .btn-microphone, .btn-binary, .btn-user, .btn-lock");

            // Alternar la clase 'hidden' en cada botón
            hiddenButtons.forEach(button => {
                button.classList.toggle("hidden");
            });

            // Agregar o quitar el event listener para detectar clics fuera de los botones
            if (!document.body.classList.contains("sliders-active")) {
                document.body.classList.add("sliders-active");
                document.addEventListener("click", closeSlidersOnClickOutside);
            } else {
                document.body.classList.remove("sliders-active");
                document.removeEventListener("click", closeSlidersOnClickOutside);
            }
        }

        function closeSlidersOnClickOutside(event) {
            let slidersButton = document.querySelector(".btn-sliders");
            let hiddenButtons = document.querySelectorAll(".btn-volume, .btn-microphone, .btn-binary, .btn-user, .btn-lock");

            // Si el clic no es en el engranaje ni en los botones, se ocultan
            if (!slidersButton.contains(event.target) && ![...hiddenButtons].some(btn => btn.contains(event.target))) {
                hiddenButtons.forEach(button => button.classList.add("hidden"));
                document.body.classList.remove("sliders-active");
                document.removeEventListener("click", closeSlidersOnClickOutside);
            }
        }

        // Evitar que los clics en los botones ocultos cierren el menú
        document.querySelectorAll(".btn-volume, .btn-microphone, .btn-binary, .btn-user, .btn-lock").forEach(button => {
            button.addEventListener("click", function (event) {
                event.stopPropagation(); // Evita que el evento de clic se propague al document
            });
        });

        // Hacer que la función esté disponible globalmente
        window.ViewSliders = ViewSliders;
    });
    
    return {
        init: function () {
            uiInit();
        },
    };
}();

function resolveModalEl(target) {
    if (!target) return null;
    if (typeof target === 'string') return document.querySelector(target);
    if (target.jquery) return target[0];
    return target.nodeType ? target : null;
}

function disposeBsModal(target) {
    const el = resolveModalEl(target);
    if (!el || typeof bootstrap === 'undefined') return;
    const instance = bootstrap.Modal.getInstance(el);
    if (instance) {
        try {
            instance.dispose();
        } catch (e) {
            // ignore stale instances
        }
    }
}

function showBsModal(target) {
    const el = resolveModalEl(target);
    if (!el || typeof bootstrap === 'undefined') return null;

    // Tras .load(), el .modal-dialog cambia; la instancia vieja deja _dialog en null/detached
    // y Bootstrap hace querySelector.call(null) → Illegal invocation.
    disposeBsModal(el);

    if (!el.querySelector('.modal-dialog')) {
        console.warn('Modal sin .modal-dialog:', el.id || el);
        return null;
    }

    return bootstrap.Modal.getOrCreateInstance(el).show();
}

function hideBsModal(target) {
    const el = resolveModalEl(target);
    if (!el || typeof bootstrap === 'undefined') return;
    const instance = bootstrap.Modal.getInstance(el);
    if (instance) instance.hide();
}

window.resolveModalEl = resolveModalEl;
window.disposeBsModal = disposeBsModal;
window.showBsModal = showBsModal;
window.hideBsModal = hideBsModal;

// Compatibilidad jQuery: $('#modal').modal('show'|'hide') usa la ruta segura.
(function patchJqueryBootstrapModal() {
    if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
        return;
    }

    jQuery.fn.modal = function (action) {
        return this.each(function () {
            if (action === 'hide') {
                hideBsModal(this);
                return;
            }
            if (action === 'dispose') {
                disposeBsModal(this);
                return;
            }
            if (action === 'toggle') {
                const instance = bootstrap.Modal.getInstance(this);
                if (instance && instance._isShown) {
                    hideBsModal(this);
                } else {
                    showBsModal(this);
                }
                return;
            }
            // 'show' o llamada sin args / con objeto de opciones
            showBsModal(this);
        });
    };
})();

function loadAndShowModal(selector, url) {
    disposeBsModal(selector);
    jQuery(selector).load(url, function (response, status) {
        if (status === 'error') {
            console.error('No se pudo cargar el modal:', url);
            return;
        }
        showBsModal(selector);
    });
}

window.loadAndShowModal = loadAndShowModal;

function modalitiesGet() {
    showBsModal('#modalModalities');
}

function boardGet() {
    showBsModal('#modalBoard');
}

function generateCartonsGet(game, payWith) {
    if(game != '') {
        var mode = (payWith === 'bonus' || payWith === 'bono' || payWith === 'abono') ? 'bonus' : 'real';
        loadAndShowModal('#modalAvailableCartons', site_url + 'playings/generateCartonsGet/' + game + '?pay_with=' + encodeURIComponent(mode));
    } else {
        Toastify({
            text: 'Debe seleccionar una sala.',
            duration: 3000,
            gravity: "top",
            position: "right",
            style: { background: "#ff4d49" },
            stopOnFocus: true
        }).showToast();
    }
}

function availableCartonsRoomGet(game) {
    if(game != '') {
        loadAndShowModal('#modalAvailableCartons', site_url + 'playings/availableCartonsGet/' + game);
    } else {
        Toastify({
            text: 'Debe seleccionar una sala.',
            duration: 3000,
            gravity: "top",
            position: "right",
            style: { background: "#ff4d49" },
            stopOnFocus: true
        }).showToast();
    }
}

function refreshWonCartonsPendingBadge() {
    $.get(site_url + 'playings/pendingWonCartonsCountGet', function(response) {
        if (response && response.success) {
            updateWonCartonsPendingBadge(response.count);
        }
    }, 'json');
}

function updateWonCartonsPendingBadge(count) {
    var badge = document.getElementById('won-cartons-pending-badge');
    if (!badge) {
        return;
    }

    var total = parseInt(count, 10) || 0;
    badge.textContent = total;

    if (total > 0) {
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
}

function gamesGet() {
    $("#modalGames").load(site_url + 'games/gamesGet', function() {
        showBsModal('#modalGames');
    });
}

function referralsGet() {
    $("#modalReferrals").load(site_url + 'users/referralsGet', function() {
        showBsModal('#modalReferrals');
    });
}

function awardsGet() {
    const awardsUrl = (typeof window.playerGroup !== 'undefined' && parseInt(window.playerGroup, 10) === 0)
        ? site_url + 'playings/awardsGet'
        : site_url + 'boards/awardsGet';

    $("#modalAwards").load(awardsUrl, function() {
        showBsModal('#modalAwards');
        $('#game-finalized').hide();
    });
}

function awardsGameGet() {
    $("#modalAwards").load(site_url + 'boards/awardsGameGet', function() {
        showBsModal('#modalAwards');
        $('#game-finalized').hide();
    });
}

function gameAdd() {
    $("#modalAddgame").load(site_url + 'games/add', function() {
        showBsModal('#modalAddgame');
    });
}

function modalityAdd() {
    $("#modalAddmodality").load(site_url + 'games/addmodality', function() {
        showBsModal('#modalAddmodality');
    });
}

function statisticsView(initialTab) {
    $("#modalStatistics").load(site_url + 'games/statisticsView', function() {
        showBsModal('#modalStatistics');

        if (!initialTab) {
            return;
        }

        var targetTabElement = document.getElementById(initialTab + '-tab');
        if (!targetTabElement) {
            return;
        }

        var tab = bootstrap.Tab.getOrCreateInstance(targetTabElement);
        tab.show();

        if (typeof statisticsGet === 'function') {
            statisticsGet(initialTab);
        }
    });
}

function statisticsViewUsers() {
    statisticsView('players');
}

function openUserExportModal() {
    $("#modalUserExport").load(site_url + 'users/exportUsersModal', function() {
        showBsModal('#modalUserExport');
    });
}

function updateLowBalancePendingBadge(count) {
    var badge = document.getElementById('low-balance-pending-badge');
    if (!badge) {
        return;
    }

    var total = parseInt(count, 10) || 0;
    badge.textContent = total;

    if (total > 0) {
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
}

function refreshLowBalancePendingBadge() {
    $.get(site_url + 'users/lowBalancePendingCountGet', function(response) {
        if (response && response.success) {
            updateLowBalancePendingBadge(response.count);
        }
    }, 'json');
}

function playersGet() {
    $("#modalPlayers").load(site_url + 'boards/playersGet', function() {
        showBsModal('#modalPlayers');
    });
}

function formatWalletAmount(value) {
    const num = parseFloat(value);
    if (Number.isNaN(num)) {
        return '0.00';
    }
    return num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateWalletUI(summary) {
    if (!summary || typeof summary !== 'object') {
        return;
    }

    const total = formatWalletAmount(summary.total);
    const recharge = formatWalletAmount(summary.recharge);
    const withdraw = formatWalletAmount(summary.withdraw);
    const bonus = formatWalletAmount(summary.bonus);

    document.querySelectorAll('.available-wallet, .wallet-total-value').forEach(function(el) {
        el.textContent = total;
    });
    document.querySelectorAll('.wallet-recharge-value').forEach(function(el) {
        el.textContent = recharge;
    });
    document.querySelectorAll('.wallet-withdraw-value').forEach(function(el) {
        el.textContent = withdraw;
    });
    document.querySelectorAll('.wallet-bonus-value').forEach(function(el) {
        el.textContent = bonus;
    });
}

function availableWallet(wallet) {
    if (wallet && typeof wallet === 'object') {
        updateWalletUI(wallet);
        return;
    }

    const total = formatWalletAmount(wallet);
    document.querySelectorAll('.available-wallet, .wallet-total-value').forEach(function(el) {
        el.textContent = total;
    });
}

function refreshWalletFromServer() {
    if (typeof site_url === 'undefined') {
        return;
    }

    return fetch(site_url + 'payments/availablewalletGet', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data && data.wallet) {
                availableWallet(data.wallet);
            }
            return data;
        })
        .catch(function(error) {
            console.error('No se pudo actualizar la billetera:', error);
        });
}

window.formatWalletAmount = formatWalletAmount;
window.updateWalletUI = updateWalletUI;
window.availableWallet = availableWallet;
window.refreshWalletFromServer = refreshWalletFromServer;

function paymentsGet() {
    disposeBsModal('#modalPayments');
    $("#modalPayments").load(site_url + 'payments/paymentsGet', function(response, status) {
        if (status === 'error') {
            console.error('No se pudo cargar la billetera (payments/paymentsGet)');
            return;
        }
        showBsModal('#modalPayments');
        refreshWalletFromServer();
    });
}

function requestGet(type, id) {
    loadAndShowModal('#modalRequest', site_url + 'payments/requestGet/' + type + '/' + id);
}

function modalVoucher(id) {
    loadAndShowModal('#modalVoucher', site_url + 'payments/modalVoucher/' + id);
}

function depositGet() {
    loadAndShowModal('#modalDeposit', site_url + 'payments/depositGet');
}

function grantBonusGet(userId) {
    var url = site_url + 'users/grantBonusGet';
    if (userId) {
        url += '/' + userId;
    }
    loadAndShowModal('#modalGrantBonus', url);
}

function retireGet() {
    loadAndShowModal('#modalRetire', site_url + 'payments/retireGet');
}

function transferGet() {
    loadAndShowModal('#modalTransfer', site_url + 'payments/transferGet');
}

function settingswalletGet() {
    loadAndShowModal('#modalSettings', site_url + 'payments/settingswalletGet');
}

function settingsGet() {
    loadAndShowModal('#modalSettings', site_url + 'home/settingsGet');
}

function bankGet() {
    disposeBsModal('#modalBank');
    $("#modalBank").load(site_url + 'home/bankGet', function() {
        $('#bank-action').val('add');
        $('#bank-id').val('');
        $('#bank-modal-title').html('<i class="fa-duotone fa-solid fa-building-columns"></i> ' + __['add']);
        $('#bank-button').text(__['add']);
        showBsModal('#modalBank');
    });
}

$('.modal').on("hidden.bs.modal", function (e) {
    if($('.modal:visible').length) {
        $('.modal-backdrop').first().css('z-index', parseInt($('.modal:visible').last().css('z-index')) - 10);
        $('body').addClass('modal-open');
    }
}).on("show.bs.modal", function (e) {
    if($('.modal:visible').length) {
        $('.modal-backdrop.in').first().css('z-index', parseInt($('.modal:visible').last().css('z-index')) + 10);
        $(this).css('z-index', parseInt($('.modal-backdrop.in').first().css('z-index')) + 10);
    }
});

$(function(){ App.init(); });