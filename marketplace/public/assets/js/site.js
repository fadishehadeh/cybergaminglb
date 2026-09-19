/* CyberGaming storefront: progressive enhancement only. The site works without this file. */
(function () {
  'use strict';

  var toggle = document.getElementById('nav-toggle');
  if (toggle) {
    // close the mobile menu after picking a link or pressing Escape
    document.querySelectorAll('.mainnav a').forEach(function (a) {
      a.addEventListener('click', function () { toggle.checked = false; });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.checked) { toggle.checked = false; toggle.focus(); }
    });
  }

  // sort / quantity selects submit on change (the Apply / Update buttons still work without JS)
  document.querySelectorAll('#f-sort, select[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { if (sel.form) { sel.form.submit(); } });
  });

  // Product photo gallery: it works without JavaScript (radio buttons + labels). Enhancement: tapping or
  // clicking the large photo moves to the next one.
  var gallery = document.querySelector('.gallery');
  if (gallery) {
    var radios = Array.prototype.slice.call(gallery.querySelectorAll('.gal-radio'));
    var big = gallery.querySelector('.gal-main');
    if (big && radios.length > 1) {
      big.style.cursor = 'pointer';
      big.addEventListener('click', function () {
        var i = radios.findIndex(function (r) { return r.checked; });
        radios[(i + 1) % radios.length].checked = true;
      });
    }
  }
})();

/* Sell / trade / swap enhancements (all optional: the forms work without JavaScript). */
(function () {
  'use strict';

  // stop double submits: disable the button once a form marked data-once is sent
  document.querySelectorAll('form[data-once]').forEach(function (form) {
    form.addEventListener('submit', function () {
      form.querySelectorAll('button[type="submit"]').forEach(function (b) {
        window.setTimeout(function () { b.disabled = true; }, 0);
      });
    });
  });

  // sell / trade photo picker: say how many photos are chosen and warn when there are more than the limit
  document.querySelectorAll('input[type="file"][data-max]').forEach(function (input) {
    var out = input.form ? input.form.querySelector('[data-photo-count]') : null;
    var max = parseInt(input.getAttribute('data-max'), 10) || 6;
    if (!out) { return; }
    input.addEventListener('change', function () {
      var n = input.files ? input.files.length : 0;
      out.hidden = n === 0;
      out.classList.toggle('is-over', n > max);
      out.textContent = n > max
        ? n + ' photos chosen: only the first ' + max + ' will be sent.'
        : n + (n === 1 ? ' photo' : ' photos') + ' chosen.';
    });
  });

  // "Copy code" button on the thank-you pages (hidden until we know clipboard access exists)
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    if (!navigator.clipboard || !navigator.clipboard.writeText) { return; }
    btn.hidden = false;
    btn.addEventListener('click', function () {
      navigator.clipboard.writeText(btn.getAttribute('data-copy')).then(function () {
        var old = btn.textContent;
        btn.textContent = 'Copied';
        window.setTimeout(function () { btn.textContent = old; }, 1800);
      });
    });
  });

  // coming back with the browser Back button restores the page from cache: re-enable the buttons
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
      document.querySelectorAll('form[data-once] button[type="submit"]').forEach(function (b) { b.disabled = false; });
    }
  });
})();

/* Checkout: update delivery fee, credit and cash due when the area or the credit checkbox changes.
   The server recomputes everything on submit; this only previews it. */
(function () {
  'use strict';

  var form = document.querySelector('form[data-checkout]');
  if (!form) { return; }
  var area = form.querySelector('[data-area]');
  var useCredit = form.querySelector('[data-use-credit]');
  var subtotal = parseFloat(form.getAttribute('data-subtotal')) || 0;
  // delivery and store credit only ever apply to the physical part; digital lines are prepaid and not creditable
  var physical = parseFloat(form.getAttribute('data-physical'));
  if (isNaN(physical)) { physical = subtotal; }
  var freeOver = parseFloat(form.getAttribute('data-free-over')) || 0;
  var balance = parseFloat(form.getAttribute('data-balance')) || 0;
  var out = {};
  document.querySelectorAll('[data-totals] [data-out]').forEach(function (el) { out[el.getAttribute('data-out')] = el; });
  var creditRow = document.querySelector('[data-totals] [data-row="credit"]');
  if (!area || !out.fee || !out.grand || !out.cash) { return; }

  function money(n) {
    n = Math.round(n * 100) / 100;
    return '$' + (n === Math.floor(n) ? n.toFixed(0) : n.toFixed(2));
  }

  function update() {
    var opt = area.options[area.selectedIndex];
    var raw = opt ? opt.getAttribute('data-fee') : '';
    if (!opt || opt.value === '' || raw === null || raw === '') {
      out.fee.textContent = 'Choose your area';
      out.grand.textContent = money(subtotal) + ' + delivery';
      out.cash.textContent = 'Choose your area';
      if (creditRow) { creditRow.hidden = true; }
      return;
    }
    var fee = (freeOver > 0 && physical >= freeOver) ? 0 : (parseFloat(raw) || 0);
    var grand = Math.round((subtotal + fee) * 100) / 100;
    var creditable = Math.round((physical + fee) * 100) / 100;
    var credit = (useCredit && useCredit.checked) ? Math.round(Math.min(balance, creditable) * 100) / 100 : 0;
    var cash = Math.round((creditable - credit) * 100) / 100;
    out.fee.textContent = fee > 0 ? money(fee) : 'Free';
    out.grand.textContent = money(grand);
    out.cash.textContent = cash > 0 ? money(cash) : '$0 (paid with credit)';
    if (creditRow && out.credit) {
      creditRow.hidden = credit <= 0;
      out.credit.textContent = money(credit);
    }
  }

  area.addEventListener('change', update);
  if (useCredit) { useCredit.addEventListener('change', update); }
  update();
})();
