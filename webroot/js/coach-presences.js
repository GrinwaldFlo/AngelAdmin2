/* Coach payment module: presence type of the coaches on the attendance page
 * The screen is updated at once, the save runs in background and is undone on failure */
(function () {
  'use strict';

  function initCoaches(block) {
    var error = block.querySelector('.cp-error');

    // A type whose limit is reached by the other coaches can't be chosen (the selected one stays clickable to clear it)
    function refreshLimits() {
      var counts = {};
      block.querySelectorAll('.cp-coach').forEach(function (row) {
        if (row.dataset.type !== '') {
          counts[row.dataset.type] = (counts[row.dataset.type] || 0) + 1;
        }
      });
      block.querySelectorAll('button.cp-type[data-max]').forEach(function (button) {
        var selected = button.closest('.cp-coach').dataset.type === button.dataset.type;
        button.disabled = !selected && (counts[button.dataset.type] || 0) >= parseInt(button.dataset.max, 10);
      });
    }

    function setRowType(row, type) {
      row.dataset.type = type;
      row.querySelectorAll('button.cp-type').forEach(function (button) {
        button.setAttribute('aria-pressed', button.dataset.type === type ? 'true' : 'false');
      });
      refreshLimits();
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

    refreshLimits();
  }

  document.querySelectorAll('.cp-coaches').forEach(initCoaches);
})();
