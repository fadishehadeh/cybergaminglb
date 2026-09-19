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

/* Checkout: update delivery fee, credit, prepayment and cash due when the area or the credit checkbox changes.
   The server recomputes everything on submit (zone rules, fee, prepayment); this only previews it. */
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
  var digital = parseFloat(form.getAttribute('data-digital')) || 0;
  var freeOver = parseFloat(form.getAttribute('data-free-over')) || 0;
  var balance = parseFloat(form.getAttribute('data-balance')) || 0;
  var remotePrepay = form.getAttribute('data-remote-prepay') === '1';
  var out = {};
  document.querySelectorAll('[data-totals] [data-out]').forEach(function (el) { out[el.getAttribute('data-out')] = el; });
  var creditRow = document.querySelector('[data-totals] [data-row="credit"]');
  var prepaidRow = document.querySelector('[data-totals] [data-row="prepaid"]');
  var hints = form.querySelectorAll('[data-hint]');
  var noteCod = form.querySelector('[data-note-cod]');
  var notePrepay = form.querySelector('[data-note-prepay]');
  if (!area || !out.fee || !out.grand || !out.cash) { return; }

  function money(n) {
    n = Math.round(n * 100) / 100;
    return '$' + (n === Math.floor(n) ? n.toFixed(0) : n.toFixed(2));
  }

  function showHint(key) {
    Array.prototype.forEach.call(hints, function (h) { h.hidden = h.getAttribute('data-hint') !== key; });
  }

  function update() {
    var opt = area.options[area.selectedIndex];
    var raw = opt ? opt.getAttribute('data-fee') : '';
    if (!opt || opt.value === '' || raw === null || raw === '') {
      out.fee.textContent = 'Choose your area';
      out.grand.textContent = money(subtotal) + ' + delivery';
      out.cash.textContent = 'Choose your area';
      if (creditRow) { creditRow.hidden = true; }
      if (prepaidRow) { prepaidRow.hidden = digital <= 0; if (out.prepaid) { out.prepaid.textContent = money(digital); } }
      showHint('none');
      if (noteCod) { noteCod.hidden = false; }
      if (notePrepay) { notePrepay.hidden = true; }
      return;
    }
    var remote = opt.getAttribute('data-mode') === 'remote';
    var prepay = remote && remotePrepay;
    var fee = (freeOver > 0 && physical >= freeOver) ? 0 : (parseFloat(raw) || 0);
    var grand = Math.round((subtotal + fee) * 100) / 100;
    var creditable = Math.round((physical + fee) * 100) / 100;
    var credit = (useCredit && useCredit.checked) ? Math.round(Math.min(balance, creditable) * 100) / 100 : 0;
    var physicalDue = Math.round((creditable - credit) * 100) / 100;
    var cash = prepay ? 0 : physicalDue;
    var prepaid = Math.round((digital + (prepay ? physicalDue : 0)) * 100) / 100;
    out.fee.textContent = fee > 0 ? money(fee) : 'Free';
    out.grand.textContent = money(grand);
    out.cash.textContent = cash > 0 ? money(cash) : (prepay && prepaid > 0 ? '$0: you prepay by OMT / Whish' : '$0 (paid with credit)');
    if (creditRow && out.credit) {
      creditRow.hidden = credit <= 0;
      out.credit.textContent = money(credit);
    }
    if (prepaidRow && out.prepaid) {
      prepaidRow.hidden = prepaid <= 0;
      out.prepaid.textContent = money(prepaid);
    }
    showHint(remote ? (prepay ? 'prepay' : 'cod') : 'local');
    if (noteCod) { noteCod.hidden = prepay; }
    if (notePrepay) { notePrepay.hidden = !prepay; }
  }

  area.addEventListener('change', update);
  if (useCredit) { useCredit.addEventListener('change', update); }
  update();
})();

/* Sell / trade request form: preview what the seller receives and which shipping rules apply for the chosen zone.
   The server decides everything (zone mode, pickup fee, remote minimum); this only mirrors it. */
(function () {
  'use strict';

  document.querySelectorAll('[data-ship]').forEach(function (box) {
    var form = box.closest('form');
    var zone = box.querySelector('[data-ship-zone]');
    if (!form || !zone) { return; }
    var fee = parseFloat(box.getAttribute('data-fee')) || 0;
    var min = parseFloat(box.getAttribute('data-min')) || 0;
    var cash = parseFloat(box.getAttribute('data-cash')) || 0;
    var credit = parseFloat(box.getAttribute('data-credit')) || 0;
    var netText = box.querySelector('[data-ship-net-text]');
    var minMsg = box.querySelector('[data-ship-min]');
    var rules = box.querySelector('.ship-rules');

    function money(n) {
      n = Math.round(n * 100) / 100;
      return '$' + (n === Math.floor(n) ? n.toFixed(0) : n.toFixed(2));
    }

    function update() {
      var opt = zone.options[zone.selectedIndex];
      var mode = opt ? opt.getAttribute('data-mode') : '';
      var key = mode === 'local' || mode === 'remote' ? mode : 'none';
      var coll = form.querySelector('input[name="collection"]:checked');
      var pickup = !!coll && coll.value === 'pickup';
      var meth = form.querySelector('input[name="preferred_method"]:checked');
      var isCash = !!meth && meth.value === 'cash';
      var estimate = isCash ? cash : credit;
      var net = Math.max(0, Math.round((estimate - (pickup ? fee : 0)) * 100) / 100);

      box.querySelectorAll('[data-ship-for]').forEach(function (el) { el.hidden = el.getAttribute('data-ship-for') !== key; });
      box.querySelectorAll('[data-ship-rule]').forEach(function (el) { el.hidden = el.getAttribute('data-ship-rule') !== (key === 'none' ? 'remote' : key); });
      if (netText) {
        netText.textContent = '';
        var strong = document.createElement('strong');
        strong.textContent = "You'll receive " + money(net);
        netText.appendChild(strong);
        netText.appendChild(document.createTextNode(' ' + (isCash ? 'in cash' : 'as store credit') + ': ' +
          (pickup ? 'estimate ' + money(estimate) + ' minus the ' + money(fee) + ' pickup fee' : 'estimate ' + money(estimate) + ', no pickup fee') + '.'));
      }
      if (minMsg) { minMsg.hidden = !(key === 'remote' && pickup && min > 0 && estimate + 0.004 < min); }
      if (rules && key === 'remote' && pickup) { rules.open = true; }
    }

    zone.addEventListener('change', update);
    form.querySelectorAll('input[name="collection"], input[name="preferred_method"]').forEach(function (r) { r.addEventListener('change', update); });
    update();
  });
})();
