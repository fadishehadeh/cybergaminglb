/* CyberGaming account area: progressive enhancement only. Every form works without this file. */
(function () {
  'use strict';

  // Move focus to the error summary after a failed submit so screen readers announce it.
  var errBox = document.querySelector('[data-focus-first]');
  if (errBox) { errBox.focus(); }

  // Offer form: only ask for the collection address when we are collecting the games.
  var offerForm = document.querySelector('[data-offer-form]');
  if (offerForm) {
    var noteRow = offerForm.querySelector('[data-pickup-note]');
    var note = noteRow ? noteRow.querySelector('textarea') : null;
    var radios = offerForm.querySelectorAll('input[name="collection"]');
    var sync = function () {
      var pickup = offerForm.querySelector('input[name="collection"]:checked');
      var show = !!pickup && pickup.value === 'pickup';
      if (noteRow) { noteRow.hidden = !show; }
      if (note) { note.required = show; }
    };
    Array.prototype.forEach.call(radios, function (r) { r.addEventListener('change', sync); });
    sync();

    // What the seller receives: the chosen offer minus the pickup fee when a courier picks the games up.
    var netText = offerForm.querySelector('[data-offer-net-text]');
    var minMsg = offerForm.querySelector('[data-offer-min]');
    var fee = parseFloat(offerForm.getAttribute('data-fee')) || 0;
    var min = parseFloat(offerForm.getAttribute('data-min')) || 0;
    var cash = parseFloat(offerForm.getAttribute('data-cash')) || 0;
    var credit = parseFloat(offerForm.getAttribute('data-credit')) || 0;
    var money = function (n) {
      n = Math.round(n * 100) / 100;
      return '$' + n.toFixed(2);
    };
    var net = function () {
      var m = offerForm.querySelector('input[name="method"]:checked');
      var c = offerForm.querySelector('input[name="collection"]:checked');
      if (!netText || !m || !c) { return; }
      var isCredit = m.value === 'credit';
      var amount = isCredit ? credit : cash;
      var pickup = c.value === 'pickup';
      var out = Math.max(0, Math.round((amount - (pickup ? fee : 0)) * 100) / 100);
      netText.textContent = 'You\'ll receive ' + money(out) + (isCredit ? ' as wallet credit' : ' in cash') + ': ' +
        (pickup ? money(amount) + ' minus the ' + money(fee) + ' pickup fee.' : money(amount) + ', no pickup fee.');
      if (minMsg) { minMsg.hidden = !(pickup && min > 0 && amount + 0.004 < min); }
    };
    Array.prototype.forEach.call(offerForm.querySelectorAll('input[name="method"], input[name="collection"]'), function (r) { r.addEventListener('change', net); });
    net();
  }

  // Confirm destructive actions (forms marked data-confirm).
  Array.prototype.forEach.call(document.querySelectorAll('form[data-confirm]'), function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
    });
  });
})();
