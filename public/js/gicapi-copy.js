// gicapi-copy.js
(function () {
    function bindCopyButtons() {
        document.querySelectorAll('.gicapi-copy-btn').forEach(function (btn) {
            if (btn.dataset.gicapiCopyBound) return;
            btn.dataset.gicapiCopyBound = '1';
            btn.addEventListener('click', function () {
                var text = this.getAttribute('data-copy');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                } else {
                    var textarea = document.createElement('textarea');
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                }
                var old = this.innerHTML;
                this.innerHTML = '<span style="font-size:11px;">✔</span>';
                setTimeout(() => { this.innerHTML = old; }, 1200);
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindCopyButtons);
    } else {
        bindCopyButtons();
    }
    // In case of dynamic content (e.g. AJAX), expose for manual rebind
    window.gicapiBindCopyButtons = bindCopyButtons;
})(); 