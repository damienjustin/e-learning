document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form.matches('form') && form.querySelector('button[type="submit"]')) {
        var btn = form.querySelector('button[type="submit"]');
        btn.dataset.originalText = btn.textContent;
        btn.disabled = true;
        setTimeout(function () {
            btn.disabled = false;
        }, 4000);
    }
});
