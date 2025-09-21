<?php
$rowCount = 0;
?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($sites as $key => $site): ?>
        <?= $this->Html->link(h($site), ['action' => 'overview', $memberStatus, $teamId, $year, $billTemplateId, $siteId == $key ? 0 : $key], ['class' => 'btn btn-primary btn-sm' . ($key == $siteId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
      |
      <?= $this->Html->link(__('Active'), ['action' => 'overview', $memberStatus == 1 ? 0 : 1, $teamId, $year, $billTemplateId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'overview', $memberStatus == 2 ? 0 : 2, $teamId, $year, $billTemplateId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 2 ? ' pressed' : '')]) ?>
      |      
      <?= $this->Html->link(__('This year'), ['action' => 'overview', $memberStatus, $teamId, 0, $billTemplateId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($year == 0 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Two years'), ['action' => 'overview', $memberStatus, $teamId, 1, $billTemplateId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($year == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Three years'), ['action' => 'overview', $memberStatus, $teamId, 2, $billTemplateId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($year == 2 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($billTemplates as $bill): ?>
        <?= $this->Html->link(h($bill->label), ['action' => 'overview', $memberStatus, $teamId, $year, $billTemplateId == $bill->id ? 0 : $bill->id, $siteId], ['class' => 'btn btn-primary btn-sm' . ($bill->id == $billTemplateId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
      <br>  
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'overview', $memberStatus, $teamId == $key ? 0 : $key, $year, $billTemplateId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="bills index content">
  <h3><?= __('Invoice overview') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= __('Member') ?></th>
        <?php foreach ($colName as $InvoiceName => $InvoiceItem) : ?>
          <th><?= $InvoiceName ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($result as $memberId => $InvoiceInfo) : $rowCount++; ?>
        <?php if ($rowCount > 7) : $rowCount = 0; ?>
          <tr>
            <th><?= __('Member') ?></th>
            <?php foreach ($colName as $InvoiceName => $InvoiceItem) : ?>
              <th><?= $InvoiceName ?></th>
            <?php endforeach; ?>
          </tr>
        <?php endif; ?>

        <tr>
          <td><?= $this->Html->link($InvoiceInfo['Name'], ['controller' => 'Members', 'action' => 'view', $memberId]) ?></td>
          <?php foreach ($colName as $InvoiceName => $InvoiceItem) : ?>
            <td>
              <?php if (empty($InvoiceInfo[$InvoiceName])) : ?>
                <?php if ($InvoiceItem > 0) : ?>
                  <?= $this->Form->create() ?>
                  <?= $this->Form->control('memberId', ['type' => 'hidden', 'value' => $memberId]) ?>
                  <?= $this->Form->control('amount', ['type' => 'hidden', 'value' => $InvoiceItem]) ?>
                  <?= $this->Form->control('name', ['type' => 'hidden', 'value' => $InvoiceName]) ?>
                  <?= $this->Form->submit(__('Add {0}CHF', $InvoiceItem)); ?>
                  <?= $this->Form->end() ?>
                <?php endif; ?>
              <?php else : ?>
                <h4><span class="badge bg-<?= h($InvoiceInfo[$InvoiceName]['Status']) ?>"><?= $InvoiceInfo[$InvoiceName]['Amount'] ?></span></h4>
                <?php endif; ?>
            </td>
          <?php endforeach; ?>
          <td><?= $this->Html->link($InvoiceInfo['Name'], ['controller' => 'Members', 'action' => 'view', $memberId]) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
