<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($sites as $key => $site): ?>
        <?= $this->Html->link(h($site), ['action' => 'batch_validation', $key], ['class' => 'btn btn-primary btn-sm' . ($key == $siteId ? ' pressed' : '')]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="bills index content">
  <h3><?= __('Batch validation') ?></h3>
  <?= $this->Form->create() ?>
  <table class="table table-striped table-hover table-sm" id="billsTable">
    <thead>
      <tr>
        <th></th>
        <th></th>
        <th>
          <input type="text" onkeyup="filterBillsTable(2)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(2);}">
        </th>
        <th>
          <input type="text" onkeyup="filterBillsTable(3)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(3);}">
        </th>
        <th>
          <input type="text" onkeyup="filterBillsTable(4)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(4);}">
        </th>
        <th>
          <input type="text" onkeyup="filterBillsTable(5)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(5);}">
        </th>
        <th>
          <input type="text" onkeyup="filterBillsTable(6)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(6);}">
        </th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bills as $bill): ?>
        <tr>
          <td>
            <?= $this->Form->control($bill->id, ['type' => 'checkbox', 'label' => false]); ?>
          </td>
          <td>
            <button type="button"
              class="btn btn-sm py-0 px-1 copy-btn"
              style="background: transparent; border: none; box-shadow: none;"
              title="Copy to clipboard"
              data-copy="<?= h("{$bill->member->FullName}\t{$bill->Reference}\t{$bill->label}\t{$bill->amount}") ?>">
              <span class="material-symbols-outlined" style="color: #45006f">content_copy</span>
            </button>
          </td>
          <td><?= $bill->has('member') ? h($bill->member->FullName) : '' ?></td>
          <td><?= h($bill->Reference) ?></td>
          <td><?= h($bill->label) ?></td>
          <td><?= $bill->amount ?></td>
          <td><?= h($bill->member->address) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?= $this->Form->button(__('Submit')) ?>
  <?= $this->Form->end() ?>
</div>

<script>
function filterBillsTable(colIndex) {
  var table = document.getElementById("billsTable");
  var inputs = table.querySelectorAll("thead input");
  var filterValues = Array.from(inputs).map(input => input.value.trim().toUpperCase());
  var rows = table.tBodies[0].rows;
  for (var i = 0; i < rows.length; i++) {
    var show = true;
    for (var j = 0; j < filterValues.length; j++) {
      var cell = rows[i].cells[j + 2]; // skip checkbox and copy columns
      if (filterValues[j] && cell && cell.textContent.toUpperCase().indexOf(filterValues[j]) === -1) {
        show = false;
        break;
      }
    }
    rows[i].style.display = show ? "" : "none";
  }
}

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const text = btn.getAttribute('data-copy');
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
      } else {
        // fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
      }
    });
  });
});
</script>
