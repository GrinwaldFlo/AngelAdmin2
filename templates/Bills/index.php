<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bill[]|\Cake\Collection\CollectionInterface $bills
 * 
 *     
 */
?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($sites as $key => $site): ?>
        <?= $this->Html->link(h($site), ['action' => 'index', $billStatus, $memberStatus, $teamId, $key], ['class' => 'btn btn-primary btn-sm' . ($key == $siteId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
      |
      <?= $this->Html->link(__('Active'), ['action' => 'index', $billStatus, $memberStatus == 1 ? 0 : 1, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'index', $billStatus, $memberStatus == 2 ? 0 : 2, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 2 ? ' pressed' : '')]) ?>
      |
      <?= $this->Html->link(__('Open'), ['action' => 'index', $billStatus == 1 ? 0 : 1, $memberStatus, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($billStatus == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Late'), ['action' => 'index', $billStatus == 2 ? 0 : 2, $memberStatus, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($billStatus == 2 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Paid'), ['action' => 'index', $billStatus == 3 ? 0 : 3, $memberStatus, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($billStatus == 3 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Draft'), ['action' => 'index', $billStatus == 4 ? 0 : 4, $memberStatus, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($billStatus == 4 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'index', $billStatus, $memberStatus, $teamId == $key ? 0 : $key], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="bills index content">
  <h3><?= __('Invoices') . ' (' . sizeof($bills) . ' ' . __x('Elements in a list', 'items') . ')' ?></h3>
  <table class="table table-striped table-hover table-sm" id="billsTable">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('id', __('Ref.')) ?><br>
          <input type="text" onkeyup="filterBillsTable(0)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(0);}">
        </th>
        <th><?= $this->Paginator->sort('member_id') ?><br>
          <input type="text" onkeyup="filterBillsTable(1)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(1);}">
        </th>
        <th><?= $this->Paginator->sort('label', __x('Label on the invoice', 'Denomination')) ?><br>
          <input type="text" onkeyup="filterBillsTable(2)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(2);}">
        </th>
        <th><?= $this->Paginator->sort('amount', __('Amount')) ?><br>
          <input type="text" onkeyup="filterBillsTable(3)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(3);}">
        </th>
        <th><?= $this->Paginator->sort('status', __('Status')) ?><br>
          <input type="text" onkeyup="filterBillsTable(4)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(4);}">
        </th>
        <th><?= $this->Paginator->sort('reminder') ?><br>
          <input type="text" onkeyup="filterBillsTable(5)" placeholder="Filter..." class="form-control form-control-sm" onkeydown="if(event.key==='Escape'){this.value='';filterBillsTable(5);}">
        </th>
        <th><?= $this->Paginator->sort('due_date') ?>
        </th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bills as $bill): ?>
        <?php //debug($bill); break; ?>
        <tr>
          <td><?= $this->Html->link(h($bill->Reference), ['controller' => 'Bills', 'action' => 'view', $bill->id]) ?></td>
          <td><?= $bill->has('member') ? $this->Html->link($bill->member->FullName, ['controller' => 'Members', 'action' => 'view', $bill->member->id]) : '' ?></td>
          <td><?= h($bill->label) ?></td>
          <td><?= $this->Number->currency($bill->amount, 'CHF') ?></td>
          <td><span class="badge bg-<?= h($bill->statusHtml) ?>"><?= h($bill->statusString) ?></span></td>
          <td><?= $this->Number->format($bill->reminder) ?></td>
          <td><?= h($bill->due_date) ?></td>
          <td class="actions">
            <div class="icon" style="width : 100px">
              <?= $curRole->BillEditAll && !$bill->paid ? $this->Html->link('<i class="gg-pen"></i>', ['controller' => 'Bills', 'action' => 'edit', $bill->id], ['escape' => false]) : '' ?>
              <?= $this->Html->link('<i class="gg-file-document"></i>', '/img/members/' . $bill->member->hash . '/Invoice_' . $bill->Reference . '.pdf', ['target' => '_blank', 'escape' => false]) ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <script>
    function filterBillsTable(colIndex) {
      var table = document.getElementById("billsTable");
      var inputs = table.querySelectorAll("thead input");
      var filterValues = Array.from(inputs).map(input => input.value.trim().toUpperCase());
      var rows = table.tBodies[0].rows;
      for (var i = 0; i < rows.length; i++) {
        var show = true;
        for (var j = 0; j < filterValues.length; j++) {
          var cell = rows[i].cells[j];
          if (filterValues[j] && cell && cell.textContent.toUpperCase().indexOf(filterValues[j]) === -1) {
            show = false;
            break;
          }
        }
        rows[i].style.display = show ? "" : "none";
      }
    }
  </script>
  <div class="paginator">
    <ul class="pagination">
      <?= $this->Paginator->first('<< ' . __('first')) ?>
      <?= $this->Paginator->prev('< ' . __('previous')) ?>
      <?= $this->Paginator->numbers() ?>
      <?= $this->Paginator->next(__('next') . ' >') ?>
      <?= $this->Paginator->last(__('last') . ' >>') ?>
    </ul>
    <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
  </div>
</div>


