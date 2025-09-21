<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BillTemplate $billTemplate
 */
?>
<div class="row">
  <aside class="col-4">
    <div class="side-nav">
      <h4 class="heading"><?= __('Actions') ?></h4>
      <?= $this->Html->link(__('Edit invoice Template'), ['action' => 'edit', $billTemplate->id], ['class' => 'side-nav-item']) ?>
      <?= $this->Form->postLink(__('Delete invoice Template'), ['action' => 'delete', $billTemplate->id], ['confirm' => __('Are you sure you want to delete # {0}?', $billTemplate->id), 'class' => 'side-nav-item']) ?>
      <?= $this->Html->link(__('List invoice Templates'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
      <?= $this->Html->link(__('New invoice Template'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
    </div>
  </aside>
  <div class="col-8">
    <div class="billTemplates view content">
      <h3><?= h($billTemplate->id) ?></h3>
      <table class="table table-striped table-hover table-sm">
        <tr>
          <th><?= __x('Label on the invoice', 'Denomination') ?></th>
          <td><?= h($billTemplate->label) ?></td>
        </tr>
        <tr>
          <th><?= __x('Physical location', 'Site') ?></th>
          <td><?= $billTemplate->has('site') ? $this->Html->link($billTemplate->site->city, ['controller' => 'Sites', 'action' => 'view', $billTemplate->site->id]) : '' ?></td>
        </tr>
        <tr>
          <th><?= __('Id') ?></th>
          <td><?= $this->Number->format($billTemplate->id) ?></td>
        </tr>
        <tr>
          <th><?= __('Amount') ?></th>
          <td><?= $this->Number->format($billTemplate->amount) ?></td>
        </tr>
        <tr>
          <th><?= __('Membership Fee') ?></th>
          <td><?= $billTemplate->membership_fee ? __('Yes') : __('No'); ?></td>
        </tr>
      </table>
    </div>
  </div>
</div>
