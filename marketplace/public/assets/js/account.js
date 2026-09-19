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
  }

  // Confirm destructive actions (forms marked data-confirm).
  Array.prototype.forEach.call(document.querySelectorAll('form[data-confirm]'), function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
    });
  });
})();
