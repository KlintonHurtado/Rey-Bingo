(function (global) {
    'use strict';

    const COLUMN_INDEX = { B: 0, I: 1, N: 2, G: 3, O: 4 };
    const HIGHLIGHT_CLASS = 'carton-search-hit';
    const DIM_CLASS = 'carton-search-dim';

    function normalizeCartonSearch(value) {
        return String(value || '').toLowerCase().replace(/\s+/g, '').replace(/^c/, '');
    }

    function parseCartonSearchQuery(query) {
        const trimmed = String(query || '').trim();
        if (!trimmed) {
            return { type: 'empty' };
        }

        const columnMatch = trimmed.match(/^([BINGO])\s*(\d+)$/i);
        if (columnMatch) {
            return {
                type: 'column_number',
                column: columnMatch[1].toUpperCase(),
                number: columnMatch[2],
            };
        }

        if (/^\d+$/.test(trimmed)) {
            return {
                type: 'number',
                number: trimmed,
            };
        }

        if (/^c?\d+$/i.test(trimmed.replace(/\s+/g, ''))) {
            return {
                type: 'serial',
                normalized: normalizeCartonSearch(trimmed),
            };
        }

        return {
            type: 'serial',
            normalized: normalizeCartonSearch(trimmed),
        };
    }

    function resolveCartonWrapper(element) {
        if (!element) {
            return null;
        }

        if (element.jquery && element.length) {
            element = element[0];
        }

        if (element.classList && element.classList.contains('bingo-border-carton-select')) {
            return element;
        }

        return element.closest ? element.closest('.bingo-border-carton-select') : null;
    }

    function getNumberCells(wrapper) {
        return wrapper.querySelectorAll('.bingo-carton-number');
    }

    function getSerialFromWrapper(wrapper) {
        if (!wrapper) {
            return '';
        }

        const fromData = wrapper.dataset.cartonSerial || wrapper.getAttribute('data-carton-serial') || '';
        if (fromData) {
            return normalizeCartonSearch(fromData);
        }

        const label = wrapper.querySelector('.carton-serial-label');
        return label ? normalizeCartonSearch(label.textContent) : '';
    }

    function clearCartonSearchHighlights(wrapper) {
        if (!wrapper) {
            return;
        }

        wrapper.classList.remove('carton-search-match');
        getNumberCells(wrapper).forEach(function (cell) {
            cell.classList.remove(HIGHLIGHT_CLASS, DIM_CLASS);
        });
    }

    function highlightColumnNumber(wrapper, column, number) {
        const colIdx = COLUMN_INDEX[String(column || '').toUpperCase()];
        const target = String(number).trim();
        let matched = false;

        getNumberCells(wrapper).forEach(function (cell, index) {
            if (cell.classList.contains('modality')) {
                return;
            }

            const isTargetColumn = index % 5 === colIdx;
            const isTargetNumber = cell.textContent.trim() === target;

            if (isTargetColumn && isTargetNumber) {
                cell.classList.add(HIGHLIGHT_CLASS);
                cell.classList.remove(DIM_CLASS);
                matched = true;
            } else {
                cell.classList.add(DIM_CLASS);
                cell.classList.remove(HIGHLIGHT_CLASS);
            }
        });

        if (matched) {
            wrapper.classList.add('carton-search-match');
        }

        return matched;
    }

    function highlightExactNumber(wrapper, number) {
        const target = String(number).trim();
        let matched = false;

        getNumberCells(wrapper).forEach(function (cell) {
            if (cell.classList.contains('modality')) {
                cell.classList.add(DIM_CLASS);
                return;
            }

            if (cell.textContent.trim() === target) {
                cell.classList.add(HIGHLIGHT_CLASS);
                cell.classList.remove(DIM_CLASS);
                matched = true;
            } else {
                cell.classList.add(DIM_CLASS);
                cell.classList.remove(HIGHLIGHT_CLASS);
            }
        });

        if (matched) {
            wrapper.classList.add('carton-search-match');
        }

        return matched;
    }

    function cartonMatchesColumnNumber(wrapper, column, number) {
        const colIdx = COLUMN_INDEX[String(column || '').toUpperCase()];
        if (colIdx === undefined) {
            return false;
        }

        const target = String(number).trim();
        const cells = getNumberCells(wrapper);

        for (let i = 0; i < cells.length; i++) {
            if (i % 5 !== colIdx || cells[i].classList.contains('modality')) {
                continue;
            }

            if (cells[i].textContent.trim() === target) {
                return true;
            }
        }

        return false;
    }

    function cartonMatchesExactNumber(wrapper, number) {
        const target = String(number).trim();
        const cells = wrapper.querySelectorAll('.bingo-carton-number:not(.modality)');

        for (let i = 0; i < cells.length; i++) {
            if (cells[i].textContent.trim() === target) {
                return true;
            }
        }

        return false;
    }

    function cartonMatchesSerial(wrapper, normalizedQuery) {
        const serial = getSerialFromWrapper(wrapper);
        return serial.includes(normalizedQuery);
    }

    function applyCartonSearchHighlights(wrapper, query) {
        clearCartonSearchHighlights(wrapper);

        const trimmedQuery = String(query || '').trim();
        if (!trimmedQuery) {
            return false;
        }

        const parsed = parseCartonSearchQuery(trimmedQuery);

        if (parsed.type === 'column_number') {
            return highlightColumnNumber(wrapper, parsed.column, parsed.number);
        }

        if (parsed.type === 'number') {
            return highlightExactNumber(wrapper, parsed.number);
        }

        return false;
    }

    function cartonMatchesSearch(wrapperOrElement, query) {
        const wrapper = resolveCartonWrapper(wrapperOrElement);
        const trimmedQuery = String(query || '').trim();

        if (!wrapper || !trimmedQuery) {
            return true;
        }

        const parsed = parseCartonSearchQuery(trimmedQuery);

        if (parsed.type === 'column_number') {
            return cartonMatchesColumnNumber(wrapper, parsed.column, parsed.number);
        }

        if (parsed.type === 'number') {
            return cartonMatchesExactNumber(wrapper, parsed.number);
        }

        if (parsed.type === 'serial') {
            return cartonMatchesSerial(wrapper, parsed.normalized);
        }

        return false;
    }

    function applyCartonsSearchState(container, query) {
        const root = typeof container === 'string' ? document.querySelector(container) : container;
        if (!root) {
            return { visible: 0, total: 0 };
        }

        const trimmedQuery = String(query || '').trim();
        const hasSearch = trimmedQuery.length > 0;
        const list = root.querySelector('#cartons-list') || root;
        let visible = 0;
        let total = 0;

        list.classList.toggle('cartons-search-active', hasSearch);

        list.querySelectorAll('.bingo-border-carton-select[data-carton-wrapper-id]').forEach(function (wrapper) {
            total++;
            const matches = !hasSearch || cartonMatchesSearch(wrapper, trimmedQuery);
            const shouldHide = hasSearch && !matches;

            wrapper.classList.toggle('carton-filter-hidden', shouldHide);
            wrapper.style.display = shouldHide ? 'none' : '';

            if (shouldHide) {
                clearCartonSearchHighlights(wrapper);
            } else if (hasSearch) {
                applyCartonSearchHighlights(wrapper, trimmedQuery);
                visible++;
            } else {
                clearCartonSearchHighlights(wrapper);
                visible++;
            }
        });

        return { visible: hasSearch ? visible : total, total, hasSearch };
    }

    global.CartonSearch = {
        COLUMN_INDEX,
        HIGHLIGHT_CLASS,
        normalizeCartonSearch,
        parseCartonSearchQuery,
        cartonMatchesSearch,
        cartonMatchesColumnNumber,
        cartonMatchesExactNumber,
        resolveCartonWrapper,
        clearCartonSearchHighlights,
        applyCartonSearchHighlights,
        applyCartonsSearchState,
    };
})(typeof window !== 'undefined' ? window : this);
