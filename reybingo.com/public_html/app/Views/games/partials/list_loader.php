<script type="text/javascript">
(function () {
    if (!window.__reyGamesListLoaderInstalled) {
        window.__reyGamesListLoaderInstalled = true;

        function findGamesListView(source) {
            if (source) {
                var sourceView = $(source).closest('[data-games-list-view]')[0];
                if (sourceView) return sourceView;
            }
            return document.querySelector('#modalGames.show [data-games-list-view]')
                || document.querySelector('[data-games-list-view="page"]')
                || document.querySelector('[data-games-list-view]');
        }

        window.gameslistGet = function (page, source) {
            var view = findGamesListView(source);
            if (!view) return;

            var $view = $(view);
            var $list = $view.find('[id="games-list"]');
            if (!$list.length) return;

            var state = view.__gamesListState || { sequence: 0, request: null };
            view.__gamesListState = state;
            var sequence = ++state.sequence;
            if (state.request) state.request.abort();

            page = Math.max(1, parseInt(page, 10) || 1);
            var date = $view.find('[name="datefilter"]').val() || 'all';
            var room = $view.find('[name="roomfilter"]').val() || 'all';
            var status = $view.find('[name="statusfilter"]').val() || 'all';

            $list.attr('aria-busy', 'true');
            if (!$list.children().length) {
                $list.html('<div data-games-list-loading class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"></div><span class="ms-2">Cargando partidas...</span></div>');
            }

            state.request = $.ajax({
                url: <?= json_encode(site_url('games/gameslistGet')) ?> + '/' + [date, room, status, page].map(encodeURIComponent).join('/'),
                type: 'GET',
                dataType: 'html',
                cache: false,
                success: function (data) {
                    if (sequence !== state.sequence || !document.documentElement.contains(view)) return;
                    $list.html(data);
                },
                error: function (xhr, textStatus) {
                    if (textStatus === 'abort' || sequence !== state.sequence || !document.documentElement.contains(view)) return;
                    if (typeof Toastify === 'function') {
                        Toastify({
                            text: 'No se pudo actualizar la lista de partidas. Vuelve a intentarlo.',
                            duration: 4000, gravity: 'top', position: 'right',
                            style: { background: '#dc3545' }, stopOnFocus: true
                        }).showToast();
                    }
                    if ($list.find('[data-games-list-loading]').length) {
                        $list.html('<div class="text-center py-4">No se pudo cargar la lista de partidas. <button type="button" class="btn btn-link" onclick="gameslistGet(' + page + ', this)">Reintentar</button></div>');
                    }
                },
                complete: function () {
                    if (sequence !== state.sequence) return;
                    state.request = null;
                    $list.attr('aria-busy', 'false');
                }
            });
            return state.request;
        };

        window.gameslistGetPage = function (page, source) {
            return window.gameslistGet(page, source);
        };

        window.refreshGamesListAfterSave = function (response, form) {
            response = response || {};
            var views = document.querySelectorAll('[data-games-list-view]');
            var $form = $(form || '#game-form');
            var date = String(response.date || $form.find('[name="date"]').val() || 'all');
            var room = String(response.room || $form.find('[name="room"]').val() || 'all');

            if (!views.length) {
                if (typeof window.gameslistGet === 'function') window.gameslistGet(1);
                return;
            }

            views.forEach(function (view) {
                var $view = $(view);
                var $dateFilter = $view.find('[name="datefilter"]');
                var $roomFilter = $view.find('[name="roomfilter"]');
                var $statusFilter = $view.find('[name="statusfilter"]');

                $dateFilter.find('option[value="0"]').remove();
                if (date !== 'all' && !$dateFilter.find('option').filter(function () { return this.value === date; }).length) {
                    $dateFilter.append($('<option>', { value: date, text: response.dateText || date }));
                }

                var curDate = $dateFilter.val();
                if (curDate !== 'all' && curDate !== date) {
                    $dateFilter.val(date);
                }

                var curRoom = $roomFilter.val();
                if (curRoom !== 'all' && curRoom !== room) {
                    $roomFilter.val(room);
                }

                $statusFilter.val('all');
                window.gameslistGet(1, view);
            });
        };
    }

    var view = document.querySelector('[data-games-list-view="<?= esc($listView, 'attr') ?>"]');
    if (view) {
        $(view).find('[name="datefilter"], [name="roomfilter"], [name="statusfilter"]')
            .off('change.reyGamesList')
            .on('change.reyGamesList', function () { window.gameslistGet(1, this); });
        window.gameslistGet(1, view);
    }
})();
</script>
