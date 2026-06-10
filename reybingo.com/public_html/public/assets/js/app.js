var App = function() {
    
    var uiInit = function () {
        linkPage();
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        var preloader = document.querySelector('.preloader');
        var loadingProgressBar = document.querySelector('.loading-progress');
        var loadingPercentage = document.querySelector('.loading-percentage');
    
        function updateProgress(event) {
            if (event.lengthComputable) {
                var percentComplete = Math.round((event.loaded / event.total) * 100);
                loadingProgressBar.style.width = percentComplete + '%';
                loadingPercentage.textContent = percentComplete + '%';
            }
        }
    
        function removePreloader() {
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 500);
        }
    
        window.addEventListener('load', removePreloader);
    
        // Simular carga (puedes eliminar esto en producción)
        var fakeLoadingProgress = 0;
        var interval = setInterval(function() {
            fakeLoadingProgress += 10;
            loadingProgressBar.style.width = fakeLoadingProgress + '%';
            loadingPercentage.textContent = fakeLoadingProgress + '%';
            if (fakeLoadingProgress >= 100) {
                clearInterval(interval);
                removePreloader();
            }
        }, 100);
    });
    
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
    return target;
}

function showBsModal(target) {
    const el = resolveModalEl(target);
    if (!el || typeof bootstrap === 'undefined') return null;
    return bootstrap.Modal.getOrCreateInstance(el).show();
}

function hideBsModal(target) {
    const el = resolveModalEl(target);
    if (!el || typeof bootstrap === 'undefined') return;
    const instance = bootstrap.Modal.getInstance(el);
    if (instance) instance.hide();
}

window.showBsModal = showBsModal;
window.hideBsModal = hideBsModal;

function modalitiesGet() {
    showBsModal('#modalModalities');
}

function boardGet() {
    showBsModal('#modalBoard');
}

function generateCartonsGet(game) {
    if(game != '') {
        $("#modalAvailableCartons").load(site_url + 'playings/generateCartonsGet/' + game, function() {
            showBsModal('#modalAvailableCartons');
        });
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
        $("#modalAvailableCartons").load(site_url + 'playings/availableCartonsGet/' + game, function() {
            showBsModal('#modalAvailableCartons');
        });
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

function statisticsView() {
    $("#modalStatistics").load(site_url + 'games/statisticsView', function() {
        showBsModal('#modalStatistics');
    });
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
    $("#modalRequest").load(site_url + 'payments/requestGet/' + type + '/' + id, function() {
        showBsModal('#modalRequest');
    });
}

function modalVoucher(id) {
    $("#modalVoucher").load(site_url + 'payments/modalVoucher/' + id, function() {
        showBsModal('#modalVoucher');
    });
}

function depositGet() {
    $("#modalDeposit").load(site_url + 'payments/depositGet', function() {
        showBsModal('#modalDeposit');
    });
}

function retireGet() {
    $("#modalRetire").load(site_url + 'payments/retireGet', function() {
        showBsModal('#modalRetire');
    });
}

function transferGet() {
    $("#modalTransfer").load(site_url + 'payments/transferGet', function() {
        showBsModal('#modalTransfer');
    });
}

function settingswalletGet() {
    $("#modalSettings").load(site_url + 'payments/settingswalletGet', function() {
        showBsModal('#modalSettings');
    });
}

function settingsGet() {
    $("#modalSettings").load(site_url + 'home/settingsGet', function() {
        showBsModal('#modalSettings');
    });
}

function bankGet() {
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