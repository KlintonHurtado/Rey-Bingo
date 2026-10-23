/**
 * Cerrar notificaciones in-app: botón de cerrar X, deslizamiento o auto-cierre fiable.
 */
function attachNotificationSwipeDismiss(notificationEl, onDismiss) {
    if (!notificationEl || notificationEl.dataset.swipeBound === '1') {
        return;
    }
    notificationEl.dataset.swipeBound = '1';

    // Manejar botón de cerrar explícito
    const handleCloseClick = (e) => {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        }
        if (typeof onDismiss === 'function') {
            onDismiss(notificationEl);
        } else if (typeof window.hideNotification === 'function') {
            window.hideNotification(notificationEl);
        } else {
            notificationEl.remove();
        }
    };

    const closeBtns = notificationEl.querySelectorAll('.notification-close-btn, .notification-close');
    closeBtns.forEach((btn) => {
        btn.addEventListener('click', handleCloseClick);
        btn.addEventListener('touchend', handleCloseClick);
    });

    let startX = 0;
    let deltaX = 0;
    let dragging = false;
    const dismissThreshold = 70;

    const clearInlineStyles = () => {
        notificationEl.style.transform = '';
        notificationEl.style.opacity = '';
        notificationEl.style.transition = '';
        notificationEl.classList.remove('is-dragging');
    };

    const finishDrag = () => {
        if (!dragging) {
            return;
        }
        dragging = false;
        if (deltaX >= dismissThreshold) {
            if (typeof onDismiss === 'function') {
                onDismiss(notificationEl);
            }
            return;
        }
        clearInlineStyles();
        if (notificationEl.classList.contains('show')) {
            notificationEl.style.transform = 'translateX(0)';
            notificationEl.style.opacity = '1';
        }

        // CRÍTICO: Re-armar el temporizador de cierre automático para que NUNCA se quede pegada
        if (typeof onDismiss === 'function') {
            if (notificationEl._autoHideTimer) {
                clearTimeout(notificationEl._autoHideTimer);
            }
            notificationEl._autoHideTimer = setTimeout(() => {
                onDismiss(notificationEl);
            }, 6000);
        }
    };

    const startDrag = (clientX) => {
        dragging = true;
        startX = clientX;
        deltaX = 0;
        notificationEl.classList.add('is-dragging');
        notificationEl.style.transition = 'none';
        if (notificationEl._autoHideTimer) {
            clearTimeout(notificationEl._autoHideTimer);
            notificationEl._autoHideTimer = null;
        }
    };

    const moveDrag = (clientX) => {
        if (!dragging) {
            return;
        }
        deltaX = Math.max(0, clientX - startX);
        notificationEl.style.transform = `translateX(${deltaX}px)`;
        notificationEl.style.opacity = String(Math.max(0.25, 1 - deltaX / 220));
    };

    // No iniciar arrastre si se hizo clic/touch en el botón de cerrar
    notificationEl.addEventListener('touchstart', (e) => {
        if (e.target.closest('.notification-close-btn') || e.target.closest('.notification-close')) {
            return;
        }
        startDrag(e.touches[0].clientX);
    }, { passive: true });
    notificationEl.addEventListener('touchmove', (e) => moveDrag(e.touches[0].clientX), { passive: true });
    notificationEl.addEventListener('touchend', finishDrag);
    notificationEl.addEventListener('touchcancel', finishDrag);

    const onMouseMove = (e) => moveDrag(e.clientX);
    const onMouseUp = () => {
        finishDrag();
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    };

    notificationEl.addEventListener('mousedown', (e) => {
        if (e.button !== 0) {
            return;
        }
        if (e.target.closest('.notification-close-btn') || e.target.closest('.notification-close')) {
            return;
        }
        startDrag(e.clientX);
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });

    // TEMPORIZADOR DURO DE SEGURIDAD (Hard Expiry):
    // Garantiza que la notificación desaparecerá sin importar clics o toques accidentales
    if (!notificationEl._hardExpiryTimer) {
        notificationEl._hardExpiryTimer = setTimeout(() => {
            if (typeof onDismiss === 'function' && notificationEl.parentNode) {
                onDismiss(notificationEl);
            }
        }, 12000);
    }
}
