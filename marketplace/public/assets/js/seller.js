/* CyberGaming Lebanon seller portal: small progressive enhancements. Everything works without this file. */
(function () {
    'use strict';

    /* Confirm before important actions: <form data-confirm="Are you sure?"> */
    document.addEventListener('submit', function (event) {
        var form = event.target;
        var message = form && form.getAttribute ? form.getAttribute('data-confirm') : null;
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    /* Same maths as App\Support\Pricing::buyerPrice(): ceil(round(price * (100 + pct), 2) / 50) / 2 */
    function buyerPrice(price, pct) {
        return Math.ceil(Math.round(price * (100 + pct) * 100) / 100 / 50) / 2;
    }

    function money(amount) {
        return '$' + (Math.floor(amount) === amount ? amount.toFixed(0) : amount.toFixed(2));
    }

    function pctText(pct) {
        return String(parseFloat(pct.toFixed(2)));
    }

    function parseAmount(text) {
        var clean = String(text || '').replace(',', '.').trim();
        return /^\d+(\.\d{1,2})?$/.test(clean) ? parseFloat(clean) : NaN;
    }

    /* Live "Buyers will see $X" preview on the listing form. */
    var box = document.querySelector('[data-price-preview]');
    if (box) {
        var pct = parseFloat(box.getAttribute('data-pct')) || 0;
        var input = box.querySelector('[data-pp-price]');
        var out = box.querySelector('[data-pp-text]');
        var update = function () {
            var price = parseAmount(input.value);
            var tail = '(your price + ' + pctText(pct) + '% commission, rounded up to $0.50)';
            out.textContent = '';
            if (isNaN(price) || price <= 0) {
                out.textContent = 'Enter your price to see what buyers will pay ' + tail + '.';
                return;
            }
            var strong = document.createElement('strong');
            strong.textContent = money(buyerPrice(price, pct));
            out.appendChild(document.createTextNode('Buyers will see '));
            out.appendChild(strong);
            out.appendChild(document.createTextNode(' ' + tail));
        };
        input.addEventListener('input', update);
        update();
    }

    /* New / Used: show the grade + included items + photo requirement only for used games. */
    var cond = document.querySelector('[data-cond]');
    if (cond) {
        var usedBox = cond.querySelector('[data-used-only]');
        var radios = cond.querySelectorAll('[data-cond-type]');
        var badges = document.querySelectorAll('[data-req-badge]');
        var syncCondition = function () {
            var type = '';
            Array.prototype.forEach.call(radios, function (r) { if (r.checked) { type = r.value; } });
            if (usedBox) { usedBox.hidden = type !== 'used'; }
            Array.prototype.forEach.call(badges, function (b) {
                b.textContent = type === 'used' ? 'Required' : (type === 'new' ? 'Optional' : 'Required for used');
                b.classList.toggle('is-optional', type === 'new');
            });
        };
        Array.prototype.forEach.call(radios, function (r) { r.addEventListener('change', syncCondition); });
        syncCondition();
    }

    /* Photos: check size / count early, show the file name and a small preview (data: URL, allowed by the CSP). */
    Array.prototype.forEach.call(document.querySelectorAll('input[type="file"][data-photo]'), function (input) {
        var holder = input.parentNode;
        var note = holder.querySelector('[data-photo-name]');
        var preview = holder.querySelector('[data-photo-preview]');
        var max = parseInt(input.getAttribute('data-max-bytes'), 10) || 10485760;
        var maxFiles = parseInt(input.getAttribute('data-max-files'), 10) || 1;
        var say = function (text, bad) {
            if (note) { note.textContent = text; note.classList.toggle('is-bad', !!bad); }
        };
        input.addEventListener('change', function () {
            var files = input.files ? Array.prototype.slice.call(input.files) : [];
            if (preview) { preview.hidden = true; preview.removeAttribute('src'); }
            if (!files.length) { say('', false); return; }
            if (files.length > maxFiles) {
                input.value = '';
                say('Please choose at most ' + maxFiles + ' photos.', true);
                return;
            }
            for (var i = 0; i < files.length; i++) {
                if (files[i].size > max) {
                    input.value = '';
                    say('"' + files[i].name + '" is larger than 10 MB. Please choose a smaller photo.', true);
                    return;
                }
                if (files[i].type && !/^image\/(jpeg|png|webp)$/.test(files[i].type)) {
                    input.value = '';
                    say('"' + files[i].name + '" is not a JPG, PNG or WebP photo.', true);
                    return;
                }
            }
            say(files.map(function (f) { return f.name; }).join(', '), false);
            if (preview && window.FileReader && files[0].size < 8 * 1024 * 1024) {
                var reader = new FileReader();
                reader.onload = function () { preview.src = String(reader.result); preview.hidden = false; };
                reader.readAsDataURL(files[0]);
            }
        });
    });

    /* Warn early about photos over 3 MB. */
    var file = document.querySelector('input[type="file"][data-max-bytes]:not([data-photo])');
    if (file) {
        file.addEventListener('change', function () {
            var max = parseInt(file.getAttribute('data-max-bytes'), 10);
            if (file.files && file.files[0] && file.files[0].size > max) {
                window.alert('That photo is larger than 3 MB. Please choose a smaller one.');
                file.value = '';
            }
        });
    }

    /* Close the mobile menu after choosing a link. */
    var toggle = document.getElementById('sp-nav-toggle');
    if (toggle) {
        Array.prototype.forEach.call(document.querySelectorAll('.sp-nav a'), function (link) {
            link.addEventListener('click', function () { toggle.checked = false; });
        });
    }
})();
