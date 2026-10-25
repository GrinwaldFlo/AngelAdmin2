/* Meetings pages: team picker, attendance taking, creation of recurring meetings */
(function () {
  'use strict';

  /* Team picker of the list: go to the selected team (URL built by the server) */
  function initTeamSelect(select) {
    select.addEventListener('change', function () {
      window.location.href = select.options[select.selectedIndex].dataset.url;
    });
  }

  /* Attendance page: the screen is updated at once, the save runs in background and is undone on failure */
  function initAttendance(page) {
    var rows = Array.prototype.slice.call(page.querySelectorAll('.mt-member'));
    var filters = page.querySelectorAll('.mt-filter');
    var separator = page.querySelector('.mt-separator');
    var bulk = page.querySelector('.mt-bulk');
    var toast = page.querySelector('.mt-toast');
    var filter = 'all';
    var toastTimer = null;

    function setRowState(row, state) {
      row.dataset.state = String(state);
      row.querySelectorAll('.mt-state').forEach(function (button) {
        button.setAttribute('aria-pressed', button.dataset.state === String(state) ? 'true' : 'false');
      });
    }

    function refresh() {
      var counts = {};
      rows.forEach(function (row) {
        counts[row.dataset.state] = (counts[row.dataset.state] || 0) + 1;
      });
      filters.forEach(function (button) {
        var value = button.dataset.filter;
        button.querySelector('.mt-count').textContent = value === 'all' ? rows.length : (counts[value] || 0);
        button.classList.toggle('is-active', value === filter);
        button.setAttribute('aria-pressed', value === filter ? 'true' : 'false');
      });
      rows.forEach(function (row) {
        row.hidden = filter !== 'all' && row.dataset.state !== filter;
      });
      if (separator) {
        separator.hidden = filter !== 'all';
      }
      if (bulk) {
        bulk.hidden = !counts['-1'];
      }
    }

    function showError() {
      toast.textContent = page.dataset.error;
      toast.hidden = false;
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () { toast.hidden = true; }, 5000);
    }

    function save(targetRows, state) {
      // A newer click on the same row must not be undone by the failure of an older one
      var tokens = targetRows.map(function (row) {
        row.saveToken = (row.saveToken || 0) + 1;
        return { row: row, token: row.saveToken, previous: row.dataset.state };
      });
      targetRows.forEach(function (row) { setRowState(row, state); });
      refresh();

      var body = new FormData();
      body.append('state', String(state));
      targetRows.forEach(function (row) { body.append('member_id[]', row.dataset.member); });

      fetch(page.dataset.url, {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': page.dataset.csrf, 'Accept': 'application/json' }
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
      }).catch(function () {
        tokens.forEach(function (item) {
          if (item.row.saveToken === item.token) {
            setRowState(item.row, item.previous);
          }
        });
        refresh();
        showError();
      });
    }

    page.addEventListener('click', function (event) {
      var stateButton = event.target.closest('.mt-state');
      if (stateButton) {
        var row = stateButton.closest('.mt-member');
        if (row.dataset.state !== stateButton.dataset.state) {
          save([row], stateButton.dataset.state);
        }
        return;
      }
      var filterButton = event.target.closest('.mt-filter');
      if (filterButton) {
        filter = filterButton.dataset.filter === filter ? 'all' : filterButton.dataset.filter;
        refresh();
        return;
      }
      if (event.target.closest('.mt-bulk')) {
        save(rows.filter(function (row) { return row.dataset.state === '-1'; }), bulk.dataset.state);
      }
    });

    refresh();
  }

  /* Creation form: "once" or "weekly" with a preview of the dates that can be excluded */
  function initAddForm(form) {
    var MAX_DAYS = 2 * 366;
    var preview = form.querySelector('[data-preview]');
    var hint = form.querySelector('[data-preview-hint]');
    var submit = form.querySelector('[data-label-one]');
    var excluded = {};
    var lang = form.dataset.locale || undefined;

    function mode() {
      var checked = form.querySelector('input[name="mode"]:checked');
      return checked ? checked.value : 'weekly';
    }

    // Dates are handled in UTC so the browser time zone can't shift them
    function parseDate(value) {
      return value ? new Date(value + 'T00:00:00Z') : null;
    }

    function chip(value, date) {
      var label = document.createElement('label');
      label.className = 'mt-chip';
      var input = document.createElement('input');
      input.type = 'checkbox';
      input.name = 'dates[]';
      input.value = value;
      input.checked = !excluded[value];
      var text = document.createElement('span');
      text.textContent = date.toLocaleDateString(lang, { weekday: 'short', day: '2-digit', month: '2-digit', timeZone: 'UTC' });
      label.appendChild(input);
      label.appendChild(text);
      return label;
    }

    function renderDates() {
      var weekdays = Array.prototype.map.call(form.querySelectorAll('input[name="weekdays[]"]:checked'), function (input) {
        return Number(input.value);
      });
      var current = parseDate(form.querySelector('input[name="from"]').value);
      var end = parseDate(form.querySelector('input[name="to"]').value);
      preview.textContent = '';
      hint.textContent = weekdays.length ? hint.dataset.text : hint.dataset.empty;
      if (!current || !end || !weekdays.length) {
        return;
      }
      for (var i = 0; current <= end && i < MAX_DAYS; i++) {
        var isoDay = current.getUTCDay() || 7;
        if (weekdays.indexOf(isoDay) !== -1) {
          preview.appendChild(chip(current.toISOString().slice(0, 10), current));
        }
        current = new Date(current.getTime() + 86400000);
      }
    }

    function updateCount() {
      var count = mode() === 'once'
        ? (form.querySelector('input[name="date"]').value ? 1 : 0)
        : preview.querySelectorAll('input:checked').length;
      if (count === 0) {
        submit.textContent = submit.dataset.labelNone;
      } else {
        submit.textContent = count === 1 ? submit.dataset.labelOne : submit.dataset.labelMany.replace('{0}', count);
      }
      submit.disabled = count === 0;
    }

    function update() {
      var current = mode();
      form.querySelectorAll('[data-mode]').forEach(function (section) {
        var active = section.dataset.mode === current;
        section.hidden = !active;
        section.querySelectorAll('input').forEach(function (input) { input.disabled = !active; });
      });
      if (current === 'weekly') {
        renderDates();
      }
      updateCount();
    }

    form.addEventListener('change', function (event) {
      if (event.target.name === 'dates[]') {
        excluded[event.target.value] = !event.target.checked;
        updateCount();
      } else {
        update();
      }
    });
    form.addEventListener('input', function (event) {
      if (event.target.type === 'date') {
        update();
      }
    });

    update();
  }

  document.querySelectorAll('.mt-team-select').forEach(initTeamSelect);
  document.querySelectorAll('.mt-attendance').forEach(initAttendance);
  document.querySelectorAll('.mt-add-form').forEach(initAddForm);
})();
