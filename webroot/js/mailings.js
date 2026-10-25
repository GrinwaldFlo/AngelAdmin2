/* Emails to teams: placeholders of the form, sending of the queued emails by chunks */
(function () {
  'use strict';

  /* Insert a placeholder at the cursor of the content */
  function initPlaceholders(button) {
    button.addEventListener('click', function () {
      var content = document.querySelector('.ml-content');
      var start = content.selectionStart;
      var end = content.selectionEnd;
      var text = button.dataset.placeholder;
      content.value = content.value.slice(0, start) + text + content.value.slice(end);
      content.focus();
      content.selectionStart = content.selectionEnd = start + text.length;
    });
  }

  /* Send page: call the server until no email is pending, stop at the first error so it can be resumed later */
  function initSend(page) {
    var button = page.querySelector('.ml-start');
    if (!button) {
      return;
    }
    var delay = page.querySelector('.ml-delay');
    var errorBox = page.querySelector('.ml-error');
    var doneBox = page.querySelector('.ml-done');
    var bar = page.querySelector('.ml-bar');
    var deleteBox = page.querySelector('.ml-delete');
    var sentCount = parseInt(page.querySelector('.ml-sent').textContent, 10);
    var running = false;
    // Only one request at a time, even when the sending is stopped and started again during a request
    var inFlight = false;
    var timer = null;

    function setRunning(value) {
      running = value;
      button.textContent = value ? button.dataset.labelStop : button.dataset.labelStart;
      button.classList.toggle('btn-primary', !value);
      button.classList.toggle('btn-danger', value);
      delay.disabled = value;
      // A mailing cannot be deleted while it is sent
      if (deleteBox) {
        deleteBox.hidden = value || sentCount > 0;
      }
      if (!value) {
        clearTimeout(timer);
      }
    }

    function showCounts(result) {
      sentCount = result.sent;
      page.querySelector('.ml-sent').textContent = result.sent;
      page.querySelector('.ml-total').textContent = result.total;
      page.querySelector('.ml-pending').textContent = result.pending;
      page.querySelector('.ml-invalid-count').textContent = result.invalid;
      bar.max = result.total;
      bar.value = result.sent + result.invalid;
    }

    function stopWithError(message) {
      setRunning(false);
      errorBox.textContent = message;
      errorBox.hidden = false;
    }

    function sendNext() {
      inFlight = true;
      fetch(page.dataset.url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': page.dataset.csrf, 'Accept': 'application/json' }
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
        return response.json();
      }).then(function (result) {
        inFlight = false;
        showCounts(result);
        if (result.error) {
          stopWithError(result.error);
        } else if (result.done || result.pending === 0) {
          setRunning(false);
          button.hidden = true;
          delay.parentNode.hidden = true;
          doneBox.hidden = false;
        } else if (running) {
          timer = setTimeout(sendNext, parseInt(delay.value, 10) * 1000);
        }
      }).catch(function () {
        inFlight = false;
        stopWithError(page.dataset.networkError);
      });
    }

    button.addEventListener('click', function () {
      if (running) {
        setRunning(false);
        return;
      }
      errorBox.hidden = true;
      setRunning(true);
      if (!inFlight) {
        sendNext();
      }
    });

    window.addEventListener('beforeunload', function (event) {
      if (running) {
        event.preventDefault();
        event.returnValue = page.dataset.leave;
      }
    });
  }

  document.querySelectorAll('.ml-placeholder').forEach(initPlaceholders);
  document.querySelectorAll('.ml-send').forEach(initSend);
})();
