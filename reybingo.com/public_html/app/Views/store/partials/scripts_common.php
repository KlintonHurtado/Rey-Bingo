<script type="text/javascript">
    function storeUpdateBalance(amount) {
        const formatted = parseFloat(amount || 0).toFixed(2);
        $('.store-balance-amount').text('<?= esc(systemGet('currency'), 'js'); ?> ' + formatted);
    }

    function storeShowToast(message, type) {
        Toastify({
            text: message,
            duration: 4000,
            gravity: 'top',
            position: 'right',
            style: { background: type === 'success' ? '#198754' : '#dc3545' },
            stopOnFocus: true
        }).showToast();
    }

    function storeClearErrors(formSelector) {
        $(formSelector + ' .text-danger').addClass('d-none').text('');
        $(formSelector + ' .form-control').removeClass('is-invalid');
    }

    document.addEventListener('click', function(event) {
        const link = event.target.closest('a.store-nav-link');
        if (!link) {
            return;
        }

        const href = link.getAttribute('href');
        if (!href) {
            return;
        }

        event.preventDefault();

        if (link.classList.contains('active')) {
            window.location.reload();
            return;
        }

        window.location.assign(href);
    });

    $(function() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('store_registered') === '1') {
            storeShowToast('<?= esc(translate('store account created successfully'), 'js'); ?>', 'success');
            params.delete('store_registered');
            const query = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (query ? '?' + query : '') + window.location.hash);
        }
    });
</script>
