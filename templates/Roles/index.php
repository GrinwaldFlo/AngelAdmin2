<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role[]|\Cake\Collection\CollectionInterface $roles
 */
?>
<div class="roles index content">
  <?= $this->Html->link(__('New Role'), ['action' => 'add'], ['class' => 'button float-end']) ?>
  <h3><?= __('Roles') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('id') ?></th>
        <th><?= $this->Paginator->sort('name') ?></th>
        <th><?= $this->Paginator->sort('MemberViewAll') ?></th>
        <th><?= $this->Paginator->sort('MemberEditAll') ?></th>
        <th><?= $this->Paginator->sort('MemberEditOwn') ?></th>
        <th><?= $this->Paginator->sort('BillViewAll') ?></th>
        <th><?= $this->Paginator->sort('BillEditAll') ?></th>
        <th><?= $this->Paginator->sort('BillValidate') ?></th>
        <th><?= $this->Paginator->sort('Editor') ?></th>
        <th><?= $this->Paginator->sort('Admin') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($roles as $role): ?>
        <tr>
          <td><?= $this->Number->format($role->id) ?></td>
          <td><?= $this->Html->link(h($role->name), ['action' => 'view', $role->id]) ?></td>
          <td><?= h($role->MemberViewAll) ?></td>
          <td><?= h($role->MemberEditAll) ?></td>
          <td><?= h($role->MemberEditOwn) ?></td>
          <td><?= h($role->BillViewAll) ?></td>
          <td><?= h($role->BillEditAll) ?></td>
          <td><?= h($role->BillValidate) ?></td>
          <td><?= h($role->Editor) ?></td>
          <td><?= h($role->Admin) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
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
