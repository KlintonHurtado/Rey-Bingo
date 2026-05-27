/**
 * Cerrar notificaciones in-app deslizando hacia la derecha (sin botón X).
 */
function attachNotificationSwipeDismiss(notificationEl, onDismiss) {
    if (!notificationEl || notificationEl.dataset.swipeBound === '1') {
        return;
    }
    notificationEl.dataset.swipeBound = '1';

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
            onDismiss(notificationEl);
            return;
        }
        clearInlineStyles();
        if (notificationEl.classList.contains('show')) {
            notificationEl.style.transform = 'translateX(0)';
            notificationEl.style.opacity = '1';
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

    notificationEl.addEventListener('touchstart', (e) => startDrag(e.touches[0].clientX), { passive: true });
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
        startDrag(e.clientX);
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });
}
