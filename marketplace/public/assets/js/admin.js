/* CyberGaming Lebanon admin: small progressive enhancements. Everything works without this file. */
(function () {
    'use strict';

    /* Confirm before destructive / important actions: <form data-confirm="Are you sure?"> */
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
        return String(parseFloat(pct.toFixed(2))) + '%';
    }

    function parseAmount(text) {
        var clean = String(text || '').replace(',', '.').trim();
        return /^\d+(\.\d{1,2})?$/.test(clean) ? parseFloat(clean) : NaN;
    }

    /* Live "buyer pays X (commission Y)" preview on the product form. */
    var box = document.querySelector('[data-price-preview]');
    if (box) {
        var seller = box.querySelector('[data-pp-seller]');
        var priceInput = box.querySelector('[data-pp-price]');
        var outBuyer = box.querySelector('[data-pp-buyer]');
        var outSeller = box.querySelector('[data-pp-seller-gets]');
        var outCommission = box.querySelector('[data-pp-commission]');
        var outPct = box.querySelector('[data-pp-pct]');
        var label = box.querySelector('[data-pp-label]');

        var update = function () {
            var option = seller.options[seller.selectedIndex];
            var isHouse = seller.value === '';
            var pct = isHouse ? 0 : parseFloat(option.getAttribute('data-pct')) || 0;
            var price = parseAmount(priceInput.value);

            label.textContent = isHouse ? 'Price the buyer pays ($) *' : 'Seller price ($) *';
            outPct.textContent = '(' + pctText(pct) + ')';

            if (isNaN(price) || price <= 0) {
                outBuyer.textContent = outSeller.textContent = outCommission.textContent = '-';
                return;
            }
            var buyer = buyerPrice(price, pct);
            outBuyer.textContent = money(buyer);
            outSeller.textContent = money(isHouse ? buyer : price);
            outCommission.textContent = money(isHouse ? 0 : Math.round((buyer - price) * 100) / 100);
        };
        seller.addEventListener('change', update);
        priceInput.addEventListener('input', update);
        update();
    }

    /* Settings: worked commission example ($10 seller price). */
    var commission = document.querySelector('[data-example="commission"]');
    if (commission) {
        var exPct = document.querySelector('[data-ex-pct]');
        var exOut = document.querySelector('[data-ex-out]');
        var refresh = function () {
            var pct = parseAmount(commission.value);
            if (isNaN(pct)) { return; }
            exPct.textContent = String(pct);
            exOut.textContent = money(buyerPrice(10, pct));
        };
        commission.addEventListener('input', refresh);
        refresh();
    }

    /* Settings: buy-back / trade-in worked example per condition. Same maths as Pricing::buybackOffer():
       floor(round(price * pct, 2) / 50) / 2 where pct = base % x condition factor / 100. */
    var exampleBox = document.querySelector('[data-buyback-example]');
    if (exampleBox) {
        var examplePrice = parseFloat(exampleBox.getAttribute('data-price')) || 20;
        var baseBuyback = document.getElementById('buyback_pct');
        var baseTradein = document.getElementById('tradein_pct');
        var offer = function (base, factor) {
            var pct = base * factor / 100;
            return Math.floor(Math.round(examplePrice * pct * 100) / 100 / 50) / 2;
        };
        var recompute = function () {
            var buyback = parseAmount(baseBuyback.value);
            var tradein = parseAmount(baseTradein.value);
            if (isNaN(buyback) || isNaN(tradein)) { return; }
            Array.prototype.forEach.call(document.querySelectorAll('[data-factor]'), function (input) {
                var factor = parseAmount(input.value);
                var row = exampleBox.querySelector('[data-ex-row="' + input.getAttribute('data-factor') + '"]');
                if (isNaN(factor) || factor > 100 || !row) { return; }
                var cash = offer(buyback, factor);
                var credit = offer(tradein, factor);
                row.querySelector('[data-ex-factor]').textContent = pctText(factor);
                row.querySelector('[data-ex-cash]').textContent = money(cash);
                row.querySelector('[data-ex-credit]').textContent = money(credit);
                if (input.getAttribute('data-factor') === 'Good') {
                    exampleBox.querySelector('[data-ex-good-cash]').textContent = money(cash);
                    exampleBox.querySelector('[data-ex-good-credit]').textContent = money(credit);
                }
            });
        };
        Array.prototype.forEach.call(document.querySelectorAll('[data-factor], #buyback_pct, #tradein_pct'), function (input) {
            input.addEventListener('input', recompute);
        });
    }

    /* Settings: worked member-commission example ($10 member price). */
    var memberCommission = document.querySelector('[data-example="member-commission"]');
    if (memberCommission) {
        var mPct = document.querySelector('[data-ex-mpct]');
        var mOut = document.querySelector('[data-ex-mout]');
        var mRefresh = function () {
            var pct = parseAmount(memberCommission.value);
            if (isNaN(pct)) { return; }
            mPct.textContent = String(pct);
            mOut.textContent = money(buyerPrice(10, pct));
        };
        memberCommission.addEventListener('input', mRefresh);
        mRefresh();
    }

    /* Settings: free-delivery worked example. */
    var freeInput = document.querySelector('[data-example="free-delivery"]');
    if (freeInput) {
        var freeOut = document.querySelector('[data-ex-free]');
        var freeRefresh = function () {
            var v = parseAmount(freeInput.value);
            if (isNaN(v)) { return; }
            freeOut.textContent = v === 0
                ? 'set to 0: every order pays its zone fee'
                : 'a ' + money(v) + ' order ships free, a ' + money(Math.max(v - 1, 0)) + ' order still pays its zone fee';
        };
        freeInput.addEventListener('input', freeRefresh);
        freeRefresh();
    }

    /* Offer form: warn when the credit offer is lower than the cash offer. */
    var offerForm = document.querySelector('[data-offer-form]');
    if (offerForm) {
        var oCash = offerForm.querySelector('[data-offer-cash]');
        var oCredit = offerForm.querySelector('[data-offer-credit]');
        var oWarn = offerForm.querySelector('[data-offer-warn]');
        var offerCheck = function () {
            var cash = parseAmount(oCash.value);
            var credit = parseAmount(oCredit.value);
            oWarn.hidden = !(!isNaN(cash) && !isNaN(credit) && credit < cash);
        };
        oCash.addEventListener('input', offerCheck);
        oCredit.addEventListener('input', offerCheck);
        offerCheck();
    }

    /* Complete form: switching the method loads that method's agreed offer as the default amount. */
    var completeForm = document.querySelector('[data-complete-form]');
    if (completeForm) {
        var cMethod = completeForm.querySelector('[data-final-method]');
        var cAmount = completeForm.querySelector('[data-final-amount]');
        var cHint = completeForm.querySelector('[data-final-hint]');
        cMethod.addEventListener('change', function () {
            var offer = completeForm.getAttribute(cMethod.value === 'credit' ? 'data-offer-credit' : 'data-offer-cash') || '0';
            cAmount.value = offer;
            cHint.textContent = 'Agreed offer for this method: ' + money(parseFloat(offer)) + '.';
        });
    }

    /* Adjust credit: highlight the "I'm sure" box for large amounts and preview the new balance. */
    var adjustForm = document.querySelector('.adjust-form');
    if (adjustForm) {
        var large = parseFloat(adjustForm.getAttribute('data-large')) || 200;
        var aAmount = adjustForm.querySelector('[name="amount"]');
        var aSure = adjustForm.querySelector('[data-adj-sure]');
        var aHint = adjustForm.querySelector('[data-adj-hint]');
        var balanceNow = parseFloat((aHint.querySelector('strong').textContent || '').replace(/[^0-9.\-]/g, '')) || 0;
        aAmount.addEventListener('input', function () {
            var raw = aAmount.value.replace(',', '.').trim();
            var n = /^[+-]?\d+(\.\d{1,2})?$/.test(raw) ? parseFloat(raw) : NaN;
            aSure.classList.toggle('needs', !isNaN(n) && Math.abs(n) > large);
            if (isNaN(n)) {
                aHint.innerHTML = 'Current balance <strong>' + money(balanceNow) + '</strong>.';
            } else {
                var after = Math.round((balanceNow + n) * 100) / 100;
                aHint.innerHTML = 'Current balance <strong>' + money(balanceNow) + '</strong> &rarr; after this <strong>' + (after < 0 ? 'not allowed (below $0)' : money(after)) + '</strong>.';
            }
        });
    }

    /* Character counters for SEO fields. */
    Array.prototype.forEach.call(document.querySelectorAll('[data-count-for]'), function (counter) {
        var input = document.getElementById(counter.getAttribute('data-count-for'));
        var max = counter.getAttribute('data-max');
        if (!input) { return; }
        var show = function () { counter.textContent = '(' + input.value.length + '/' + max + ')'; };
        input.addEventListener('input', show);
        show();
    });

    /* Close the mobile menu after choosing a link. */
    var toggle = document.getElementById('nav-toggle');
    if (toggle) {
        Array.prototype.forEach.call(document.querySelectorAll('.sidebar .nav a'), function (link) {
            link.addEventListener('click', function () { toggle.checked = false; });
        });
    }
})();

/* Product form: "Digital item" checkbox reveals kind/region and hides the physical-only fields. */
(function () {
    'use strict';
    var box = document.querySelector('[data-digital-toggle]');
    if (!box) { return; }
    var form = box.form || document;
    var region = form.querySelector('[data-region-select]');
    var regionOther = form.querySelector('[data-region-other]');
    var seller = form.querySelector('#seller_id');
    var category = form.querySelector('#category_id');
    var stock = form.querySelector('#stock');

    function toggleRegionOther() {
        if (regionOther && region) { regionOther.hidden = !(box.checked && region.value === 'Other'); }
    }

    function apply(userChange) {
        var on = box.checked;
        Array.prototype.forEach.call(form.querySelectorAll('[data-digital-only]'), function (el) { el.hidden = !on; });
        Array.prototype.forEach.call(form.querySelectorAll('[data-physical-only]'), function (el) { el.hidden = on; });
        toggleRegionOther();
        if (seller) {
            if (on) { seller.value = ''; }
            seller.disabled = on;
            seller.dispatchEvent(new Event('change'));
        }
        if (!userChange) { return; }
        if (on) {
            if (category && category.value === '') {
                Array.prototype.forEach.call(category.options, function (opt) {
                    if (opt.getAttribute('data-slug') === 'gift-cards') { category.value = opt.value; }
                });
            }
            if (stock && (stock.value === '' || stock.value === '1')) { stock.value = '999'; }
        } else if (stock && stock.value === '999') {
            stock.value = '1';
        }
    }

    box.addEventListener('change', function () { apply(true); });
    if (region) { region.addEventListener('change', toggleRegionOther); }
    apply(false);
})();

/* Product form: New / Used choice shows the used-only parts (grade, included items, "required" badges, missing-photos notice). */
(function () {
    'use strict';
    var radios = document.querySelectorAll('[data-cond-type]');
    if (!radios.length) { return; }
    var form = radios[0].form || document;
    function apply() {
        var used = false;
        Array.prototype.forEach.call(radios, function (r) { if (r.checked && r.value === 'used') { used = true; } });
        Array.prototype.forEach.call(form.querySelectorAll('[data-used-only]'), function (el) { el.hidden = !used; });
    }
    Array.prototype.forEach.call(radios, function (r) { r.addEventListener('change', apply); });
    apply();
})();

/* Photo inputs: preview the chosen file, refuse files above 10 MB and too many extras (the server checks again). */
(function () {
    'use strict';
    Array.prototype.forEach.call(document.querySelectorAll('input[data-photo]'), function (input) {
        var slot = input.parentNode;
        var preview = slot.querySelector('[data-photo-preview]');
        var note = slot.querySelector('[data-photo-note]');
        var maxBytes = parseInt(input.getAttribute('data-max-bytes'), 10) || 10485760;
        var maxFiles = parseInt(input.getAttribute('data-max-files'), 10) || 1;

        function say(text, bad) {
            if (!note) { return; }
            note.textContent = text;
            note.classList.toggle('ph-note-bad', !!bad);
        }

        input.addEventListener('change', function () {
            var files = input.files ? Array.prototype.slice.call(input.files) : [];
            if (preview) { preview.hidden = true; preview.removeAttribute('src'); }
            say('', false);
            if (!files.length) { return; }
            if (files.length > maxFiles) {
                input.value = '';
                say('You can add up to ' + maxFiles + ' photos at a time.', true);
                return;
            }
            for (var i = 0; i < files.length; i++) {
                if (files[i].size > maxBytes) {
                    input.value = '';
                    say('"' + files[i].name + '" is larger than 10 MB. Choose a smaller photo.', true);
                    return;
                }
            }
            say(files.length === 1 ? files[0].name : files.length + ' photos selected', false);
            if (preview && window.FileReader && files[0].type.indexOf('image/') === 0) {
                var reader = new FileReader();
                reader.onload = function () { preview.src = String(reader.result); preview.hidden = false; };
                reader.readAsDataURL(files[0]);
            }
        });
    });
})();

/* Review page: while photos are missing, Approve stays disabled until "Publish without photos (legacy stock)" is ticked. */
(function () {
    'use strict';
    var form = document.querySelector('[data-legacy-gate]');
    if (!form) { return; }
    var check = form.querySelector('[data-legacy-check]');
    var button = form.querySelector('[data-legacy-approve]');
    if (!check || !button) { return; }
    check.addEventListener('change', function () { button.disabled = !check.checked; });
    button.disabled = !check.checked;
})();
