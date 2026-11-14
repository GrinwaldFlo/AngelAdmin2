/**
 * Sortable tables (vanilla JS), sorted in the browser
 *
 * <table class="js-sortable">
 *   <th data-sort-type="text|number|date"><button type="button" class="table-sort-btn">Name</button></th>
 *   <td data-sort-value="...">  optional value used instead of the cell text (ISO date, rank, ...)
 *
 * A click on a header sorts its column ascending, a second click descending.
 * Empty values always go last. The sort is stable: equal rows keep their current order.
 */
(function () {
  'use strict';

  function cellValue(row, index) {
    var cell = row.cells[index];
    if (!cell) {
      return '';
    }
    return (cell.dataset.sortValue !== undefined ? cell.dataset.sortValue : cell.textContent).trim();
  }

  function compare(a, b, type) {
    if (type === 'number') {
      return parseFloat(a) - parseFloat(b);
    }
    // ISO dates (yyyy-mm-dd hh:mm:ss) sort as text
    return a.localeCompare(b, undefined, { sensitivity: 'base', numeric: true });
  }

  function sortTable(table, th) {
    var headers = Array.from(th.parentNode.children);
    var index = headers.indexOf(th);
    var type = th.dataset.sortType || 'text';
    var direction = th.getAttribute('aria-sort') === 'ascending' ? -1 : 1;
    var tbody = table.tBodies[0];

    var rows = Array.from(tbody.rows);
    rows.sort(function (rowA, rowB) {
      var a = cellValue(rowA, index);
      var b = cellValue(rowB, index);
      if (a === '' || b === '') {
        return (a === '') - (b === '');
      }
      return compare(a, b, type) * direction;
    });
    rows.forEach(function (row) {
      tbody.appendChild(row);
    });

    headers.forEach(function (header) {
      header.removeAttribute('aria-sort');
    });
    th.setAttribute('aria-sort', direction === 1 ? 'ascending' : 'descending');
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('.js-sortable .table-sort-btn');
    if (!button) {
      return;
    }
    var th = button.closest('th');
    sortTable(th.closest('table'), th);
  });
})();
