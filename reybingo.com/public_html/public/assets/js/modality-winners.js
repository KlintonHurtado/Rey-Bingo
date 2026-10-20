// Compartido por el jugador, el tablero y Live.
(function () {
    'use strict';

    function normalizeName(value) {
        return typeof value === 'string'
            ? value.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .trim().toLowerCase().replace(/\s+/g, ' ').replace(/^la\s+/, '')
            : '';
    }

    window.markModalityCardWon = function (modalityId, modalityName) {
        const cards = Array.from(document.querySelectorAll(
            '.container-cartons-modalities .carton[id^="modality-"]'
        ));
        const id = String(modalityId == null ? '' : modalityId).trim();
        let targets;

        if (/^[1-9]\d*$/.test(id)) {
            // Un ID conocido nunca debe terminar marcando otra modalidad por su nombre.
            targets = cards.filter(card => card.id === 'modality-' + id);
        } else {
            const name = normalizeName(modalityName || modalityId);
            if (!name) return;

            targets = cards.filter(card => {
                const border = card.closest('.border-carton');
                const label = border && border.querySelector('.modality-name, small');
                return label && normalizeName(label.textContent) === name;
            });

            // Permitir copias de la misma tarjeta, pero no nombres ambiguos entre IDs.
            if (new Set(targets.map(card => card.id)).size !== 1) return;
        }

        targets.forEach(card => {
            card.classList.add('cartn-sing');
            const border = card.closest('.border-carton');
            if (border) border.classList.add('modality-won');
            card.querySelectorAll('.card-number.modality-sing').forEach(cell => {
                cell.classList.add('sing');
                cell.textContent = '⭐️';
            });
        });
    };
})();
