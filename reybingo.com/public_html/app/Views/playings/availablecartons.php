<div class="modal-dialog modal-dialog-centered max-w-70">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2 text-uppercase"><i class="fa-duotone fa-solid fa-gamepad"></i> <?= $room['name']; ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body p-1 pt-0 text-center">
            <div class="text-center p-2"><?= $game['description']; ?> <span id="selection-time"></span></div>
            <div class="carton-filters-bar">
                <div class="carton-favorites-nav" id="carton-favorites-nav" aria-hidden="true">
                    <button type="button" class="carton-favorites-back-btn" id="carton-favorites-back-btn" aria-label="Volver a todos los cartones">
                        <i class="fa-duotone fa-solid fa-arrow-left"></i>
                    </button>
                    <span class="carton-favorites-nav-title">Favoritos</span>
                </div>
                <div class="carton-filters-default" id="carton-filters-default">
                    <div class="carton-search-wrap">
                        <i class="fa-duotone fa-solid fa-magnifying-glass carton-search-icon"></i>
                        <input type="search" id="carton-serial-search" class="carton-serial-search" placeholder="Buscar por serial, número o columna (ej: O 70)" autocomplete="off" inputmode="search">
                    </div>
                    <button type="button" class="carton-open-favorites-btn" id="toggle-favorite-cartons-filter" aria-pressed="false">
                        <i class="fa-duotone fa-solid fa-star"></i> Favoritos (<span id="carton-favorites-count-num">0</span>)
                    </button>
                    <div id="carton-search-results" class="carton-search-results" aria-live="polite"></div>
                </div>
            </div>
            <div class="action-sheet-content mb-3" id="cartons-container">
                <div class="cartons-section-select">
                    <?php if (isset($cartons) && count($cartons) == 0): ?>
                        <style>
                            .content-cartons-select {
                                grid-template-columns: repeat(1, 1fr) !important;
                            }
                        </style>
                    <?php endif; ?>
                    <div id="cartons-favorites-empty" class="cartons-favorites-empty">No hay cartones favoritos disponibles aquí.</div>
                    <div id="cartons-search-empty" class="cartons-favorites-empty">No se encontraron cartones con esa búsqueda.</div>
                    <div class="content-cartons-select" id="cartons-list">
                    <?php if (isset($cartons) && count($cartons) > 0): ?>
                        <?php foreach ($cartons as $cartonData): ?>
                            <div class="bingo-border-carton-select" data-carton-wrapper-id="<?= $cartonData['cartonId']; ?>" data-carton-serial="<?= esc($cartonData['serial']); ?>">
                                <div class="carton-card-head">
                                    <span class="carton-serial-label">SERIAL: C<?= $cartonData['serial']; ?></span>
                                    <button type="button" class="carton-favorite-btn favorite-carton-btn" data-favorite-carton="<?= $cartonData['cartonId']; ?>" data-carton-serial="<?= esc($cartonData['serial']); ?>" data-game-id="<?= $game['id']; ?>" aria-label="Marcar cartón favorito" title="Marcar favorito">&#9734;</button>
                                </div>
                                <div class="bingo-carton" id="carton-<?= $cartonData['cartonId']; ?>" data-carton-id="<?= $cartonData['cartonId']; ?>">
                                        <div class="bingo-carton-header bingo-col-b"><span>B</span></div>
                                        <div class="bingo-carton-header bingo-col-i"><span>I</span></div>
                                        <div class="bingo-carton-header bingo-col-n"><span>N</span></div>
                                        <div class="bingo-carton-header bingo-col-g"><span>G</span></div>
                                        <div class="bingo-carton-header bingo-col-o"><span>O</span></div>
                                        
                                        <?php foreach ($cartonData['numbers'] as $index => $number): ?>
                                            <?php if ($index === 12): ?>
                                                <div class="bingo-carton-number modality" data-position="<?= $number['position']; ?>">⭐️</div>
                                            <?php else: ?>
                                                <div class="bingo-carton-number"><?= $number['number']; ?></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="btn btn-small btn-primary d-block w-75 btn-bingo mt-1 carton-action-btn" data-carton-id="<?= $cartonData['cartonId']; ?>" data-action="select"><?= translate('select'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <h6 class="text-center mt-2"><?= translate('there are no cards available for this game'); ?></h6>
                    <?php endif; ?>
                    </div>
                </div>
                
                <div id="loading-indicator" style="display: none; padding: 20px; text-center;">
                    <i class="fa fa-spinner fa-spin"></i> Cargando más cartones...
                </div>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-small btn-primary d-block w-50 btn-bingo" id="play-button"><?= translate('play'); ?></button>
            </div>
            <div class="text-center p-2"><?= translate('my wallet'); ?> <?= systemGet('currency'); ?> <span class="available-wallet"><?= $user['wallet']; ?></span></div>
            <div class="text-center p-2 hidden"><?= translate('selected cartons'); ?> <span id="select-cartons">0</span></div>
        </div>
    </div>
</div>

<style>
.carton-filters-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin: 0 auto 14px;
    padding: 0 10px 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.18);
}

.carton-filters-default {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.carton-filters-default.is-hidden {
    display: none;
}

.carton-favorites-nav {
    display: none;
    width: 100%;
    max-width: 300px;
    align-items: center;
    gap: 10px;
}

.carton-favorites-nav.is-visible {
    display: flex;
}

.carton-favorites-back-btn {
    border: 0;
    border-radius: 999px;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.96);
    color: #6236ff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    cursor: pointer;
    flex: 0 0 auto;
}

.carton-favorites-nav-title {
    flex: 1;
    text-align: left;
    font-size: 0.82rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
}

.carton-search-wrap {
    position: relative;
    width: 100%;
    max-width: 300px;
}

.carton-search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.78rem;
    color: #6236ff;
    pointer-events: none;
}

.carton-serial-search {
    width: 100%;
    border: 0;
    border-radius: 999px;
    padding: 7px 12px 7px 32px;
    font-size: 0.78rem;
    line-height: 1.2;
    color: #333;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.carton-serial-search:focus {
    outline: 2px solid rgba(98, 54, 255, 0.35);
}

.carton-serial-search::placeholder {
    color: #8a8a8a;
}

.carton-open-favorites-btn {
    border: 0;
    border-radius: 999px;
    font-size: 0.74rem;
    padding: 6px 14px;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: linear-gradient(145deg, #ffc107, #ff9800);
    color: #522f00;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    width: auto;
    margin: 0;
}

.carton-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    padding: 0 2px 6px;
}

.carton-serial-label {
    font-size: 0.68rem;
    color: #6c757d;
    text-align: left;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.2;
}

.carton-favorite-btn {
    flex-shrink: 0;
    width: 26px;
    height: 26px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.95);
    color: #6236ff;
    font-size: 0.88rem;
    line-height: 1;
    cursor: pointer;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.14);
    transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease;
}

.carton-favorite-btn:hover {
    transform: scale(1.06);
}

.carton-favorite-btn.is-favorite {
    background: linear-gradient(145deg, #ffc107, #ff9800);
    color: #522f00;
}

.carton-open-favorites-btn.is-active {
    background: linear-gradient(145deg, #ff9800, #f57c00);
    box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.18);
}

.bingo-border-carton-select.carton-filter-hidden {
    display: none !important;
}

.cartons-search-active .bingo-border-carton-select.carton-search-match {
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.95);
}

.bingo-carton-number.carton-search-hit {
    background: #4caf50 !important;
    color: #fff !important;
    border-radius: 8px;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.85);
    transform: scale(1.04);
    font-weight: 700;
    z-index: 2;
}

.bingo-carton-number.carton-search-dim {
    opacity: 0.22;
}

.carton-search-results {
    display: none;
    width: 100%;
    text-align: center;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.92);
    margin-top: -4px;
}

.carton-search-results.is-visible {
    display: block;
}

.cartons-favorites-empty {
    display: none;
    padding: 18px 12px;
    text-align: center;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.82rem;
}

.cartons-favorites-empty.is-visible {
    display: block;
}

.bingo-border-carton-select.is-favorite-carton {
    box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.85);
    border-radius: 10px;
}

#cartons-container .content-cartons-select {
    grid-gap: 12px 10px;
    padding: 4px 6px 10px;
}

#cartons-container .bingo-border-carton-select {
    padding: 6px 5px 8px;
    box-sizing: border-box;
    width: 100%;
}

.carton-scroll-highlight {
    animation: cartonPulse 1.2s ease;
}

@keyframes cartonPulse {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.8); }
    70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}
</style>

<script src="<?= asset_url('js/carton-search.js'); ?>"></script>
<script type="text/javascript">
    var currentPage = <?= $currentPage ?? 1 ?>;
    var hasMorePages = <?= ($currentPage < $totalPages) ? 'true' : 'false' ?>;
    var isLoading = false;
    var selectedCartons = [];
    var otherUsersCartons = [];
    var selectionTimers = {};
    var gameId = <?= $game['id'] ?>;
    var realTimeInterval;
    var lastUpdateTimestamp = 0;
    var FAVORITE_CARTONS_KEY = 'favoriteCartonSerials';
    var FAVORITE_CARTONS_META_KEY = 'favoriteCartonsMetaBySerial';
    var LEGACY_FAVORITE_CARTONS_KEY = 'favoriteCartons';
    var LEGACY_FAVORITE_CARTONS_META_KEY = 'favoriteCartonsMeta';
    var showOnlyFavoriteCartons = false;
    var cartonSearchQuery = '';
    var legacyFavoritesMigrated = false;

    function normalizeFavoriteSerial(serial) {
        return String(serial || '').trim().replace(/^c/i, '');
    }

    function migrateLegacyFavoriteStorage() {
        if (legacyFavoritesMigrated) {
            return;
        }
        legacyFavoritesMigrated = true;

        if (localStorage.getItem(FAVORITE_CARTONS_KEY)) {
            return;
        }

        try {
            const legacyIds = JSON.parse(localStorage.getItem(LEGACY_FAVORITE_CARTONS_KEY) || 'null');
            const legacyMeta = JSON.parse(localStorage.getItem(LEGACY_FAVORITE_CARTONS_META_KEY) || '{}');
            if (!legacyIds || typeof legacyIds !== 'object' || Array.isArray(legacyIds)) {
                return;
            }

            const serials = [];
            const metaBySerial = {};

            Object.keys(legacyIds).forEach(function(gameKey) {
                (legacyIds[gameKey] || []).forEach(function(cartonId) {
                    const meta = (legacyMeta[gameKey] || {})[String(cartonId)];
                    const serial = normalizeFavoriteSerial(meta && meta.serial ? meta.serial : cartonId);
                    if (!serial || serials.includes(serial)) {
                        return;
                    }
                    serials.push(serial);
                    if (meta) {
                        metaBySerial[serial] = {
                            serial: String(meta.serial || serial),
                            numbers: meta.numbers || null
                        };
                    }
                });
            });

            localStorage.setItem(FAVORITE_CARTONS_KEY, JSON.stringify(serials));
            localStorage.setItem(FAVORITE_CARTONS_META_KEY, JSON.stringify(metaBySerial));
        } catch (error) {
            console.warn('No se pudo migrar favoritos legacy:', error);
        }
    }

    function getFavoriteCartonSerials() {
        migrateLegacyFavoriteStorage();
        const raw = JSON.parse(localStorage.getItem(FAVORITE_CARTONS_KEY) || '[]');
        return Array.isArray(raw) ? raw.map(normalizeFavoriteSerial).filter(Boolean) : [];
    }

    function saveFavoriteCartonSerials(serials) {
        const unique = [...new Set(serials.map(normalizeFavoriteSerial).filter(Boolean))];
        localStorage.setItem(FAVORITE_CARTONS_KEY, JSON.stringify(unique));
    }

    function saveFavoriteCartonMetaBySerial(serial, numbers) {
        const normalized = normalizeFavoriteSerial(serial);
        if (!normalized) {
            return;
        }

        const all = JSON.parse(localStorage.getItem(FAVORITE_CARTONS_META_KEY) || '{}');
        all[normalized] = {
            serial: String(serial),
            numbers: numbers || null
        };
        localStorage.setItem(FAVORITE_CARTONS_META_KEY, JSON.stringify(all));
    }

    function removeFavoriteCartonMetaBySerial(serial) {
        const normalized = normalizeFavoriteSerial(serial);
        const all = JSON.parse(localStorage.getItem(FAVORITE_CARTONS_META_KEY) || '{}');
        if (all[normalized]) {
            delete all[normalized];
            localStorage.setItem(FAVORITE_CARTONS_META_KEY, JSON.stringify(all));
        }
    }

    function isFavoriteCarton(cartonId) {
        const serial = getCartonSerialFromDom(cartonId);
        if (!serial) {
            return false;
        }
        return getFavoriteCartonSerials().includes(serial);
    }

    function favoriteCartonExistsInDom(serial) {
        const normalized = normalizeFavoriteSerial(serial);
        let exists = false;

        $('.bingo-border-carton-select[data-carton-serial]').each(function() {
            if (normalizeFavoriteSerial($(this).data('carton-serial') || '') === normalized) {
                exists = true;
                return false;
            }
        });

        return exists;
    }

    function getCartonSerialFromDom(cartonId) {
        const wrapper = $(`.bingo-border-carton-select[data-carton-wrapper-id="${cartonId}"]`);
        if (wrapper.length && wrapper.data('carton-serial')) {
            return normalizeFavoriteSerial(wrapper.data('carton-serial'));
        }
        return '';
    }

    function countFavoriteCartonsInCurrentView() {
        let count = 0;
        $('.bingo-border-carton-select[data-carton-wrapper-id]').each(function() {
            const serial = normalizeFavoriteSerial($(this).data('carton-serial') || '');
            if (serial && getFavoriteCartonSerials().includes(serial)) {
                count++;
            }
        });
        return count;
    }

    function captureCartonNumbersFromDom(cartonId) {
        const numbers = [];
        $(`#carton-${cartonId} .bingo-carton-number`).each(function() {
            numbers.push({
                position: $(this).data('position') || 0,
                number: $(this).text().trim()
            });
        });
        return numbers.length ? numbers : null;
    }

    function updateFavoritesFilterButton() {
        const $btn = $('#toggle-favorite-cartons-filter');
        $btn.toggleClass('is-active', showOnlyFavoriteCartons);
        $btn.attr('aria-pressed', showOnlyFavoriteCartons ? 'true' : 'false');

        $('#carton-favorites-nav')
            .toggleClass('is-visible', showOnlyFavoriteCartons)
            .attr('aria-hidden', showOnlyFavoriteCartons ? 'false' : 'true');
        $('#carton-filters-default').toggleClass('is-hidden', showOnlyFavoriteCartons);
    }

    function exitFavoritesFilter() {
        if (!showOnlyFavoriteCartons) {
            return;
        }

        showOnlyFavoriteCartons = false;
        updateFavoritesFilterButton();
        applyFavoriteCartonsFilter();
    }

    function normalizeCartonSearch(value) {
        return window.CartonSearch ? CartonSearch.normalizeCartonSearch(value) : String(value || '').toLowerCase().replace(/\s+/g, '').replace(/^c/, '');
    }

    function cartonMatchesSearch($wrapper, query) {
        if (window.CartonSearch) {
            return CartonSearch.cartonMatchesSearch($wrapper, query);
        }

        if (!query) {
            return true;
        }

        const normalizedQuery = normalizeCartonSearch(query);
        const serial = normalizeCartonSearch($wrapper.data('carton-serial') || '');
        if (serial.includes(normalizedQuery)) {
            return true;
        }

        let numberMatch = false;
        $wrapper.find('.bingo-carton-number:not(.modality)').each(function() {
            if ($(this).text().trim().includes(normalizedQuery)) {
                numberMatch = true;
                return false;
            }
        });

        return numberMatch;
    }

    function applyCartonsFilters() {
        let visibleFavorites = 0;
        const hasSearch = cartonSearchQuery.trim().length > 0;
        let visibleMatches = 0;

        if (hasSearch && showOnlyFavoriteCartons) {
            showOnlyFavoriteCartons = false;
            updateFavoritesFilterButton();
        }

        if (window.CartonSearch) {
            const stats = CartonSearch.applyCartonsSearchState('#cartons-container', cartonSearchQuery);
            visibleMatches = stats.visible;

            if (showOnlyFavoriteCartons) {
                $('.bingo-border-carton-select[data-carton-wrapper-id]:not(.carton-filter-hidden)').each(function() {
                    const cartonId = String($(this).data('carton-wrapper-id'));
                    if (isFavoriteCarton(cartonId)) {
                        visibleFavorites++;
                    } else {
                        $(this).addClass('carton-filter-hidden').hide();
                        visibleMatches--;
                    }
                });
            }
        } else {
            $('.bingo-border-carton-select[data-carton-wrapper-id]').each(function() {
                const $wrapper = $(this);
                const cartonId = String($wrapper.data('carton-wrapper-id'));
                const isFavorite = isFavoriteCarton(cartonId);
                const matchesSearch = cartonMatchesSearch($wrapper, cartonSearchQuery);
                const shouldHide = (showOnlyFavoriteCartons && !isFavorite) || (hasSearch && !matchesSearch);

                $wrapper.toggleClass('carton-filter-hidden', shouldHide);
                this.style.display = shouldHide ? 'none' : '';

                if (showOnlyFavoriteCartons && isFavorite && !shouldHide) {
                    visibleFavorites++;
                }
                if (!shouldHide) {
                    visibleMatches++;
                }
            });
        }

        const $results = $('#carton-search-results');
        if (hasSearch) {
            $results
                .addClass('is-visible')
                .text(visibleMatches > 0
                    ? visibleMatches + ' cartón' + (visibleMatches === 1 ? '' : 'es') + ' encontrado' + (visibleMatches === 1 ? '' : 's')
                    : 'Ningún cartón coincide con la búsqueda');
        } else {
            $results.removeClass('is-visible').text('');
        }

        $('#cartons-favorites-empty').toggleClass('is-visible', showOnlyFavoriteCartons && visibleFavorites === 0 && !hasSearch);
        $('#cartons-search-empty').toggleClass('is-visible', hasSearch && visibleMatches === 0);
    }

    function applyFavoriteCartonsFilter() {
        applyCartonsFilters();
    }

    function handleCartonSearchInput(value) {
        cartonSearchQuery = String(value || '').trim();
        applyCartonsFilters();
    }

    let loadingFavoriteCartons = false;

    function ensureFavoriteCartonsFromServer(done) {
        const serials = getFavoriteCartonSerials();
        const missing = serials.filter(function(serial) {
            return !favoriteCartonExistsInDom(serial);
        });

        if (!missing.length) {
            if (typeof done === 'function') {
                done();
            }
            return;
        }

        if (loadingFavoriteCartons) {
            if (typeof done === 'function') {
                done();
            }
            return;
        }

        loadingFavoriteCartons = true;

        $.ajax({
            url: '<?= site_url('playings/loadFavoriteCartons') ?>',
            method: 'POST',
            data: {
                game_id: gameId,
                serials: JSON.stringify(missing)
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function(response) {
                if (response.success && response.cartons && response.cartons.length > 0) {
                    appendCartons(response.cartons);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading favorite cartons:', error);
            },
            complete: function() {
                loadingFavoriteCartons = false;
                if (typeof done === 'function') {
                    done();
                }
            }
        });
    }

    function toggleFavoritesFilter() {
        if (!showOnlyFavoriteCartons && getFavoriteCartonSerials().length === 0) {
            showNotification('No tienes cartones favoritos guardados.', 'info');
            return;
        }

        if (!showOnlyFavoriteCartons) {
            ensureFavoriteCartonsFromServer(function() {
                showOnlyFavoriteCartons = true;
                updateFavoritesFilterButton();
                applyFavoriteCartonsFilter();
                $('#cartons-container').scrollTop(0);
            });
            return;
        }

        showOnlyFavoriteCartons = false;
        updateFavoritesFilterButton();
        applyFavoriteCartonsFilter();
    }

    function toggleFavoriteCarton(cartonId, serial) {
        const normalizedSerial = normalizeFavoriteSerial(serial || getCartonSerialFromDom(cartonId));
        if (!normalizedSerial) {
            return false;
        }

        let serials = getFavoriteCartonSerials();
        const wasFavorite = serials.includes(normalizedSerial);

        if (wasFavorite) {
            serials = serials.filter(function(item) { return item !== normalizedSerial; });
            removeFavoriteCartonMetaBySerial(normalizedSerial);
        } else {
            serials.push(normalizedSerial);
            saveFavoriteCartonMetaBySerial(normalizedSerial, captureCartonNumbersFromDom(cartonId));
        }

        saveFavoriteCartonSerials(serials);
        refreshAllFavoriteCartonsUI();

        if (showOnlyFavoriteCartons && wasFavorite && countFavoriteCartonsInCurrentView() === 0) {
            showOnlyFavoriteCartons = false;
            updateFavoritesFilterButton();
            applyFavoriteCartonsFilter();
        }

        return !wasFavorite;
    }

    function updateFavoriteCartonUI(cartonId) {
        const id = parseInt(cartonId, 10);
        const isFavorite = isFavoriteCarton(id);
        const wrapper = $(`.bingo-border-carton-select[data-carton-wrapper-id="${id}"]`);
        const button = wrapper.find('.favorite-carton-btn');

        button.toggleClass('is-favorite', isFavorite);
        button.html(isFavorite ? '&#9733;' : '&#9734;');
        button.attr('title', isFavorite ? 'Quitar de favoritos' : 'Marcar favorito');
        wrapper.toggleClass('is-favorite-carton', isFavorite);
    }

    function refreshAllFavoriteCartonsUI() {
        $('.bingo-border-carton-select[data-carton-wrapper-id]').each(function() {
            const cartonId = $(this).data('carton-wrapper-id');
            updateFavoriteCartonUI(cartonId);
        });
        updateFavoriteCartonsCount();
        sortFavoriteCartonsFirst();
        applyFavoriteCartonsFilter();
    }

    function updateFavoriteCartonsCount() {
        $('#carton-favorites-count-num').text(getFavoriteCartonSerials().length);
    }

    function sortFavoriteCartonsFirst() {
        const list = $('#cartons-list');
        const items = list.children('.bingo-border-carton-select').get();
        if (!items.length) return;

        items.sort(function(a, b) {
            const aId = parseInt($(a).data('carton-wrapper-id'), 10);
            const bId = parseInt($(b).data('carton-wrapper-id'), 10);
            const aFav = isFavoriteCarton(aId) ? 1 : 0;
            const bFav = isFavoriteCarton(bId) ? 1 : 0;
            if (aFav !== bFav) return bFav - aFav;
            return aId - bId;
        });

        $.each(items, function(_, item) {
            list.append(item);
        });
    }

    function buildCartonCardHtml(cartonData) {
        const cartonId = cartonData.cartonId;
        const isUserSelected = selectedCartons.includes(cartonId);
        const isOtherUserSelected = otherUsersCartons.includes(cartonId);

        let cartonClass = 'bingo-carton';
        let buttonText = '<?= translate('select'); ?>';
        let buttonClass = 'btn btn-small btn-primary d-block w-75 btn-bingo mt-1 carton-action-btn';
        let buttonAction = 'select';
        let buttonDisabled = '';

        if (isUserSelected) {
            cartonClass += ' select-carton';
            buttonText = 'Deseleccionar';
            buttonClass = 'btn btn-small btn-danger d-block w-75 btn-bingo mt-1 carton-action-btn';
            buttonAction = 'deselect';
        } else if (isOtherUserSelected) {
            cartonClass += ' already-select-carton';
            buttonText = 'No disponible';
            buttonClass = 'btn btn-small btn-secondary d-block w-75 btn-bingo mt-1 carton-action-btn';
            buttonAction = 'unavailable';
            buttonDisabled = 'disabled';
        }

        let html = `<div class="bingo-border-carton-select" data-carton-wrapper-id="${cartonId}" data-carton-serial="${cartonData.serial}">`;
        html += `<div class="carton-card-head">`;
        html += `<span class="carton-serial-label">SERIAL: C${cartonData.serial}</span>`;
        html += `<button type="button" class="carton-favorite-btn favorite-carton-btn" data-favorite-carton="${cartonId}" data-carton-serial="${cartonData.serial}" data-game-id="${gameId}" aria-label="Marcar cartón favorito" title="Marcar favorito">&#9734;</button>`;
        html += `</div>`;
        html += `<div class="${cartonClass}" id="carton-${cartonId}" data-carton-id="${cartonId}">`;
        html += `<div class="bingo-carton-header bingo-col-b"><span>B</span></div><div class="bingo-carton-header bingo-col-i"><span>I</span></div><div class="bingo-carton-header bingo-col-n"><span>N</span></div><div class="bingo-carton-header bingo-col-g"><span>G</span></div><div class="bingo-carton-header bingo-col-o"><span>O</span></div>`;

        cartonData.numbers.forEach(function(number, index) {
            if (index === 12) {
                html += `<div class="bingo-carton-number modality" data-position="${number.position}">⭐️</div>`;
            } else {
                html += `<div class="bingo-carton-number">${number.number}</div>`;
            }
        });

        html += `</div>`;
        html += `<button type="button" class="${buttonClass}" data-carton-id="${cartonId}" data-action="${buttonAction}" ${buttonDisabled}>${buttonText}</button>`;
        html += `</div>`;
        return html;
    }

    $(document).ready(function() {
        // Event handlers para scroll infinito
        $('#cartons-container').on('scroll', function() {
            if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 100) {
                if (hasMorePages && !isLoading) {
                    loadMoreCartons();
                }
            }
        });

        // Event handlers para botones de cartones
        $(document).on('click', '.carton-action-btn', function() {
            const cartonId = $(this).data('carton-id');
            const action = $(this).data('action');
            
            if (action === 'select') {
                selectCarton(cartonId);
            } else if (action === 'deselect') {
                deselectCarton(cartonId);
            }
        });

        $(document).on('click', '.favorite-carton-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const cartonId = $(this).data('favorite-carton');
            const serial = $(this).data('carton-serial');
            const added = toggleFavoriteCarton(cartonId, serial);
            showNotification(
                added ? 'Cartón agregado a favoritos.' : 'Cartón quitado de favoritos.',
                added ? 'success' : 'info'
            );
        });

        $('#toggle-favorite-cartons-filter').off('click.favFilter').on('click.favFilter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleFavoritesFilter();
        });

        $('#carton-favorites-back-btn').off('click.favBack').on('click.favBack', function(e) {
            e.preventDefault();
            e.stopPropagation();
            exitFavoritesFilter();
        });

        $('#carton-serial-search').off('input.cartonSearch search.cartonSearch').on('input.cartonSearch search.cartonSearch', function() {
            handleCartonSearchInput($(this).val());
        });

        // Event handler para click en cartón seleccionado
        $(document).on('click', '.bingo-carton.select-carton', function() {
            const cartonId = $(this).data('carton-id');
            deselectCarton(cartonId);
        });

        // Event handler para el botón de jugar
        $('#play-button').on('click', function(e) {
            e.preventDefault();
            const selectedCount = selectedCartons.length;
            Swal.fire({
                title: '<?= translate('do you want to continue?'); ?>',
                text: `<?= translate('you have selected to play'); ?> (${selectedCount} <?= strtolower(translate('cartons')); ?>)`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<?= translate('yes, play!'); ?>',
                cancelButtonText: '<?= translate('cancel'); ?>',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-danger'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    playGame();
                }
            });
        });

        // Inicializar actualizaciones en tiempo real y cargar cartones seleccionados
        startRealTimeUpdates();
        loadSelectedCartons();
        updatePlayButton();
        refreshAllFavoriteCartonsUI();
    });

    // Función para procesar el juego
    function playGame() {
        const button = $('#play-button');
        
        // Validar que haya cartones seleccionados
        if (selectedCartons.length === 0) {
            showNotification('Debes seleccionar al menos un cartón para jugar', 'error');
            return;
        }

        // Deshabilitar botón
        button.prop('disabled', true);
        const originalText = button.text();
        button.html('<i class="fa fa-spinner fa-spin"></i> Procesando...');

        $.ajax({
            url: '<?= site_url('playings/playGame') ?>',
            method: 'POST',
            data: {
                game_id: gameId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Éxito - mostrar mensaje y redirigir
                    showNotification(
                        `¡Cartones asignados correctamente!`, 
                        'success'
                    );

                    showNotification(
                        `Cartones: ${response.cartons_assigned}, Costo: <?= systemGet('currency'); ?> ${response.total_cost}`, 
                        'info'
                    );

                    /*showNotification(
                        `Saldo disponible: <?= systemGet('currency'); ?> ${response.new_balance}`, 
                        'success'
                    );*/
                    
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 2000);

                } else if (response.play) {
                    // Juego ya iniciado y usuario tiene cartones
                    showNotification('El juego ya ha iniciado', 'warning');
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1500);

                } else if (response.finished) {
                    // Juego terminado
                    showNotification('El juego ha terminado', 'error');
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    }

                } else if (response.initiated) {
                    // Juego iniciado pero usuario no tiene cartones
                    showNotification('El juego ya ha iniciado y no puedes unirte', 'error');

                } else if (response.payments) {
                    // Falta recarga mínima
                    showNotification(
                        `Para jugar debes recargar al menos <?= systemGet('currency'); ?> ${response.amount} a tu billetera`, 
                        'error'
                    );

                } else if (response.time) {
                    // Muy temprano para entrar
                    showNotification('Debes ingresar a la partida 10 minutos antes de iniciar', 'error');

                } else {
                    // Errores de validación
                    if (response.errors) {
                        let errorMessages = [];
                        $.each(response.errors, function(field, message) {
                            errorMessages.push(message);
                        });
                        showNotification(errorMessages.join(', '), 'error');
                    } else {
                        showNotification(response.message || 'Error desconocido al procesar el juego', 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en playGame:', error);
                showNotification('Error de conexión al procesar el juego', 'error');
            },
            complete: function() {
                // Rehabilitar botón
                button.prop('disabled', false);
                button.text(originalText);
            }
        });
    }

    // Función para actualizar el botón de jugar según la selección
    function updatePlayButton() {
        const button = $('#play-button');
        const selectedCount = selectedCartons.length;
        
        if (selectedCount === 0) {
            button.prop('disabled', true);
            button.text('<?= translate('select cartons'); ?>');
        } else {
            button.prop('disabled', false);
            button.text(`<?= translate('play'); ?> (${selectedCount} <?= strtolower(translate('cartons')); ?>)`);
        }
    }

    // Función para iniciar actualizaciones en tiempo real
    function startRealTimeUpdates() {
        realTimeInterval = setInterval(function() {
            updateCartonsRealTime();
        }, 3000);
        
        console.log('Actualizaciones en tiempo real iniciadas');
    }

    // Función para actualizar cartones en tiempo real
    function updateCartonsRealTime() {
        $.ajax({
            url: '<?= site_url('playings/getRealTimeCartonsStatus') ?>',
            method: 'POST',
            data: {
                game_id: gameId
            },
            success: function(response) {
                if (response.success) {
                    if (response.timestamp > lastUpdateTimestamp) {
                        lastUpdateTimestamp = response.timestamp;
                        
                        const newOtherUsersCartons = response.otherUsersCartons || [];
                        const currentOtherUsersCartons = otherUsersCartons.slice();
                        
                        // Cartones liberados por otros usuarios
                        const releasedCartons = currentOtherUsersCartons.filter(cartonId => 
                            !newOtherUsersCartons.includes(cartonId)
                        );
                        
                        // Cartones recién seleccionados por otros usuarios
                        const newlySelectedByOthers = newOtherUsersCartons.filter(cartonId => 
                            !currentOtherUsersCartons.includes(cartonId)
                        );
                        
                        // Procesar cartones liberados
                        releasedCartons.forEach(cartonId => {
                            if (!selectedCartons.includes(cartonId)) {
                                updateCartonToAvailable(cartonId);
                                //showNotification(`Cartón C${getCartonSerial(cartonId)} ahora está disponible`, 'success');
                            }
                        });
                        
                        // Procesar cartones recién seleccionados por otros
                        newlySelectedByOthers.forEach(cartonId => {
                            if (!selectedCartons.includes(cartonId)) {
                                updateCartonToUnavailable(cartonId);
                                //showNotification(`Cartón C${getCartonSerial(cartonId)} fue seleccionado por otro jugador`, 'warning');
                            }
                        });
                        
                        otherUsersCartons = newOtherUsersCartons;
                        
                        // Verificar cartones del usuario que expiraron
                        const currentUserCartons = response.userCartons || [];
                        const expiredUserCartons = selectedCartons.filter(cartonId => 
                            !currentUserCartons.includes(cartonId)
                        );
                        
                        expiredUserCartons.forEach(cartonId => {
                            removeCartonSelection(cartonId);
                            //showNotification(`Tu selección del cartón C${getCartonSerial(cartonId)} expiró`, 'error');
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en actualización en tiempo real:', error);
            }
        });
    }

    // Función para seleccionar cartón
    function selectCarton(cartonId) {
        if (selectedCartons.includes(cartonId) || otherUsersCartons.includes(cartonId)) {
            showNotification('Este cartón no está disponible', 'error');
            return;
        }

        $.ajax({
            url: '<?= site_url('playings/selectCarton') ?>',
            method: 'POST',
            data: {
                carton_id: cartonId,
                game_id: gameId
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    updateCartonToSelected(cartonId);
                    selectedCartons.push(parseInt(cartonId));
                    $('#select-cartons').text(selectedCartons.length);
                    updatePlayButton();
                    startCartonTimer(cartonId);
                    updateSelectionTime();
                    
                    setTimeout(updateCartonsRealTime, 500);
                    showNotification(`Cartón C${getCartonSerial(cartonId)} seleccionado.`, 'success');
                } else {
                    showNotification(response.message || 'Error al seleccionar cartón', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error selecting carton:', error);
                showNotification('Error al seleccionar cartón', 'error');
            }
        });
    }

    // Función para deseleccionar cartón
    function deselectCarton(cartonId) {
        if (!selectedCartons.includes(parseInt(cartonId))) {
            return;
        }

        $.ajax({
            url: '<?= site_url('playings/deselectCarton') ?>',
            method: 'POST',
            data: {
                carton_id: cartonId,
                game_id: gameId
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    removeCartonSelection(cartonId);
                    setTimeout(updateCartonsRealTime, 500);
                    //showNotification(`Cartón C${getCartonSerial(cartonId)} deseleccionado`, 'success');
                } else {
                    showNotification(response.message || 'Error al deseleccionar cartón', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error deselecting carton:', error);
                showNotification('Error al deseleccionar cartón', 'error');
            }
        });
    }

    // Función para cargar más cartones (scroll infinito)
    function loadMoreCartons() {
        if (isLoading) return;
        
        isLoading = true;
        currentPage++;
        
        $('#loading-indicator').show();
        
        $.ajax({
            url: '<?= site_url('playings/loadMoreCartons') ?>',
            method: 'POST',
            data: {
                game_id: gameId,
                page: currentPage
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function(response) {
                if (response.success && response.cartons.length > 0) {
                    appendCartons(response.cartons);
                    hasMorePages = response.hasMore;
                } else {
                    hasMorePages = false;
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading more cartons:', error);
                currentPage--; 
            },
            complete: function() {
                isLoading = false;
                $('#loading-indicator').hide();
            }
        });
    }

    // Función para cargar cartones ya seleccionados
    function loadSelectedCartons() {
        $.ajax({
            url: '<?= site_url('playings/getSelectedCartons') ?>/' + gameId,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    // Procesar cartones del usuario
                    if (response.userCartons && response.userCartons.length > 0) {
                        response.userCartons.forEach(carton => {
                            const cartonId = carton.carton;
                            
                            updateCartonToSelected(cartonId);
                            selectedCartons.push(parseInt(cartonId));
                            
                            const createdAt = new Date(carton.created_at).getTime();
                            const expirationTime = createdAt + (5 * 60 * 1000);
                            
                            if (expirationTime > Date.now()) {
                                selectionTimers[cartonId] = expirationTime;
                            }
                        });
                        
                        $('#select-cartons').text(selectedCartons.length);
                        updatePlayButton();
                        updateSelectionTime();
                        refreshAllFavoriteCartonsUI();
                    }
                    
                    // Procesar cartones de otros usuarios
                    if (response.otherUsersCartons && response.otherUsersCartons.length > 0) {
                        response.otherUsersCartons.forEach(carton => {
                            const cartonId = carton.carton;
                            otherUsersCartons.push(parseInt(cartonId));
                            updateCartonToUnavailable(cartonId);
                        });
                    }
                }
            }
        });
    }

    // Función para agregar cartones al DOM
    function appendCartons(cartons) {
        let html = '';
        cartons.forEach(function(cartonData) {
            html += buildCartonCardHtml(cartonData);
        });
        
        $('#cartons-list').append(html);
        refreshAllFavoriteCartonsUI();
        applyCartonsFilters();
    }

    // Funciones de actualización visual de cartones
    function updateCartonToSelected(cartonId) {
        $(`#carton-${cartonId}`).addClass('select-carton');
        
        const button = $(`.carton-action-btn[data-carton-id="${cartonId}"]`);
        button.removeClass('btn-primary btn-secondary')
              .addClass('btn-danger')
              .text('Deseleccionar')
              .prop('disabled', false)
              .data('action', 'deselect');
    }

    function updateCartonToDeselected(cartonId) {
        $(`#carton-${cartonId}`).removeClass('select-carton');
        
        const button = $(`.carton-action-btn[data-carton-id="${cartonId}"]`);
        button.removeClass('btn-danger btn-secondary')
              .addClass('btn-primary')
              .text('<?= translate('select'); ?>')
              .prop('disabled', false)
              .data('action', 'select');
    }

    function updateCartonToUnavailable(cartonId) {
        const cartonElement = $(`#carton-${cartonId}`);
        const buttonElement = $(`.carton-action-btn[data-carton-id="${cartonId}"]`);
        
        if (!cartonElement.hasClass('already-select-carton')) {
            cartonElement.addClass('already-select-carton');
            
            buttonElement.removeClass('btn-primary btn-danger')
                         .addClass('btn-secondary')
                         .text('No disponible')
                         .prop('disabled', true)
                         .data('action', 'unavailable');
            
            cartonElement.fadeOut(200).fadeIn(200);
        }
    }

    function updateCartonToAvailable(cartonId) {
        const cartonElement = $(`#carton-${cartonId}`);
        const buttonElement = $(`.carton-action-btn[data-carton-id="${cartonId}"]`);
        
        if (cartonElement.hasClass('already-select-carton')) {
            cartonElement.removeClass('already-select-carton select-carton');
            
            buttonElement.removeClass('btn-secondary btn-danger')
                         .addClass('btn-primary')
                         .text('<?= translate('select'); ?>')
                         .prop('disabled', false)
                         .data('action', 'select');
            
            cartonElement.fadeOut(200).fadeIn(200);
        }
    }

    // Funciones de temporizador
    function startCartonTimer(cartonId) {
        const expirationTime = Date.now() + (5 * 60 * 1000);
        selectionTimers[cartonId] = expirationTime;
    }

    function updateSelectionTime() {
        if (selectedCartons.length > 0) {
            const now = Date.now();
            const times = Object.values(selectionTimers).filter(time => time > now);
            
            if (times.length > 0) {
                const oldestSelection = Math.min(...times);
                const timeLeft = Math.max(0, oldestSelection - now);
                
                if (timeLeft > 0) {
                    const minutes = Math.floor(timeLeft / 60000);
                    const seconds = Math.floor((timeLeft % 60000) / 1000);
                    $('#selection-time').text(`Tiempo restante: ${minutes}:${seconds.toString().padStart(2, '0')}`);
                    
                    setTimeout(updateSelectionTime, 1000);
                } else {
                    $('#selection-time').text('');
                }
            } else {
                $('#selection-time').text('');
            }
        } else {
            $('#selection-time').text('');
        }
    }

    function removeCartonSelection(cartonId) {
        updateCartonToDeselected(cartonId);
        
        selectedCartons = selectedCartons.filter(id => id != cartonId);
        delete selectionTimers[cartonId];
        
        $('#select-cartons').text(selectedCartons.length);
        updatePlayButton();
        
        if (selectedCartons.length === 0) {
            $('#selection-time').text('');
        }
    }

    // Funciones auxiliares
    function getCartonSerial(cartonId) {
        const cartonElement = $(`#carton-${cartonId}`);
        const wrapper = cartonElement.closest('.bingo-border-carton-select');
        if (wrapper.length && wrapper.data('carton-serial')) {
            return String(wrapper.data('carton-serial'));
        }
        const serialElement = wrapper.find('.carton-serial-label');
        if (serialElement.length) {
            const serialText = serialElement.text();
            const match = serialText.match(/C(\d+)/);
            return match ? match[1] : cartonId;
        }
        return cartonId;
    }

    function showNotification(message, type = 'info') {
        var backgroundColor;
        
        switch(type) {
            case 'success':
                backgroundColor = "#28a745";
                break;
            case 'error':
            case 'danger':
                backgroundColor = "#ff4d49";
                break;
            case 'warning':
                backgroundColor = "#fdb528";
                break;
            case 'info':
            default:
                backgroundColor = "#26c6f9";
                break;
        }
        
        Toastify({
            text: message,
            duration: 3000,
            gravity: "top",
            position: "right",
            style: { background: backgroundColor },
            stopOnFocus: true
        }).showToast();
    }

    // Event handlers para limpieza
    $(document).on('hidden.bs.modal', function() {
        if (realTimeInterval) {
            clearInterval(realTimeInterval);
            console.log('Actualizaciones en tiempo real detenidas');
        }
    });

    $(window).on('beforeunload', function() {
        if (realTimeInterval) {
            clearInterval(realTimeInterval);
        }
    });
</script>
