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
    
        let soundtrack = null;  // Variable para el audio de fondo (deshabilitado)
        let audioStarted = false;
        window.__bingoSoundtrack = null;
    
        // Función para detener y limpiar cualquier música de fondo
        function stopBingoSoundtrack() {
            try {
                if (window.__bingoSoundtrack) {
                    window.__bingoSoundtrack.pause();
                    window.__bingoSoundtrack.currentTime = 0;
                    window.__bingoSoundtrack.src = '';
                    window.__bingoSoundtrack = null;
                }
                if (soundtrack) {
                    soundtrack.pause();
                    soundtrack.currentTime = 0;
                    soundtrack.src = '';
                    soundtrack = null;
                }
            } catch (e) { /* ignore */ }
        }
        window.stopBingoSoundtrack = stopBingoSoundtrack;
        stopBingoSoundtrack();

        // Función para iniciar el soundtrack (deshabilitada para que no suene canción de fondo)
        function startSoundtrack() {
            stopBingoSoundtrack();
            return;
        }
        window.startBingoSoundtrack = function() {};
    
        // Función para activar/desactivar el soundtrack
        $('.btn-volume').click(function() {
            stopBingoSoundtrack();
            $(this).html('<i class="fa-duotone fa-solid fa-volume-slash"></i>');
        });
    
        // Música de fondo deshabilitada: no auto-reproducir al hacer clic en la página
        function playSound() {
            stopBingoSoundtrack();
            document.removeEventListener('click', playSound);
        }
    
        // Música de fondo desactivada permanentemente: no escuchar clic para reproducir
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

function modalitiesGet() {
    $('#modalModalities').modal('show');
}

function boardGet() {
    $('#modalBoard').modal('show');
}

function generateCartonsGet(game) {
    if(game != '') {
        $("#modalAvailableCartons").load(site_url + 'playings/generateCartonsGet/' + game, function() {
            $('#modalAvailableCartons').modal('show');
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
            $('#modalAvailableCartons').modal('show');
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
        $('#modalGames').modal('show');
    });
}

function referralsGet() {
    $("#modalReferrals").load(site_url + 'users/referralsGet', function() {
        $('#modalReferrals').modal('show');
    });
}

function awardsGet() {
    $("#modalAwards").load(site_url + 'boards/awardsGet', function() {
        $('#modalAwards').modal('show');
        $('#game-finalized').hide();
    });
}

function awardsGameGet() {
    $("#modalAwards").load(site_url + 'boards/awardsGameGet', function() {
        $('#modalAwards').modal('show');
        $('#game-finalized').hide();
    });
}

function gameAdd() {
    $("#modalAddgame").load(site_url + 'games/add', function() {
        $('#modalAddgame').modal('show');
    });
}

function modalityAdd() {
    $("#modalAddmodality").load(site_url + 'games/addmodality', function() {
        $('#modalAddmodality').modal('show');
    });
}

function statisticsView() {
    $("#modalStatistics").load(site_url + 'games/statisticsView', function() {
        $('#modalStatistics').modal('show');
    });
}

function playersGet() {
    $("#modalPlayers").load(site_url + 'boards/playersGet', function() {
        $('#modalPlayers').modal('show');
    });
}

function paymentsGet() {
    $("#modalPayments").load(site_url + 'payments/paymentsGet', function() {
        $('#modalPayments').modal('show');
    });
}

function requestGet(type, id) {
    $("#modalRequest").load(site_url + 'payments/requestGet/' + type + '/' + id, function() {
        $('#modalRequest').modal('show');
    });
}

function modalVoucher(id) {
    $("#modalVoucher").load(site_url + 'payments/modalVoucher/' + id, function() {
        $('#modalVoucher').modal('show');
    });
}

function depositGet() {
    $("#modalDeposit").load(site_url + 'payments/depositGet', function() {
        $('#modalDeposit').modal('show');
    });
}

function retireGet() {
    $("#modalRetire").load(site_url + 'payments/retireGet', function() {
        $('#modalRetire').modal('show');
    });
}

function transferGet() {
    $("#modalTransfer").load(site_url + 'payments/transferGet', function() {
        $('#modalTransfer').modal('show');
    });
}

function settingswalletGet() {
    $("#modalSettings").load(site_url + 'payments/settingswalletGet', function() {
        $('#modalSettings').modal('show');
    });
}

function settingsGet() {
    $("#modalSettings").load(site_url + 'home/settingsGet', function() {
        $('#modalSettings').modal('show');
    });
}

function bankGet() {
    $("#modalBank").load(site_url + 'home/bankGet', function() {
        $('#bank-action').val('add');
        $('#bank-id').val('');
        $('#bank-modal-title').html('<i class="fa-duotone fa-solid fa-building-columns"></i> ' + __['add']);
        $('#bank-button').text(__['add']);
        $('#modalBank').modal('show');
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