/* Coach payment module: presence type of the coaches on the attendance page
 * The screen is updated at once, the save runs in background and is undone on failure */
(function () {
  'use strict';

  function initCoaches(block) {
    var error = block.querySelector('.cp-error');

    function setRowType(row, type) {
      row.dataset.type = type;
      row.querySelectorAll('button.cp-type').forEach(function (button) {
        button.setAttribute('aria-pressed', button.dataset.type === type ? 'true' : 'false');
      });
    }

    function showError(message) {
      error.textContent = message || block.dataset.error;
      error.hidden = false;
    }

    function save(row, type) {
      var previous = row.dataset.type;
      row.saveToken = (row.saveToken || 0) + 1;
      var token = row.saveToken;
      setRowType(row, type);
      error.hidden = true;

      var body = new FormData();
      body.append('member_id', row.dataset.member);
      body.append('type_id', type);

      fetch(block.dataset.url, {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': block.dataset.csrf, 'Accept': 'application/json' }
      }).then(function (response) {
        if (response.ok) {
          return null;
        }
        return response.json().catch(function () { return {}; }).then(function (json) {
          throw new Error(json.error || '');
        });
      }).catch(function (exception) {
        // A newer click on the same row must not be undone by the failure of an older one
        if (row.saveToken === token) {
          setRowType(row, previous);
        }
        showError(exception.message);
      });
    }

    block.addEventListener('click', function (event) {
      var button = event.target.closest('button.cp-type');
      if (!button) {
        return;
      }
      var row = button.closest('.cp-coach');
      // The selected type again = not declared
      save(row, row.dataset.type === button.dataset.type ? '' : button.dataset.type);
    });
  }

  document.querySelectorAll('.cp-coaches').forEach(initCoaches);
})();
