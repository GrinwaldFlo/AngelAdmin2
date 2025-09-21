<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User[]|\Cake\Collection\CollectionInterface $users
 */
?>
<div class="users index content">
  <?= $this->Html->link(__('New User'), ['action' => 'add'], ['class' => 'button float-end']) ?>
  <h3><?= __('Users') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('id') ?></th>
        <th><?= $this->Paginator->sort('username') ?></th>
        <th><?= $this->Paginator->sort('role_id') ?></th>
        <th><?= $this->Paginator->sort('member_id') ?></th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= $this->Number->format($user->id) ?></td>
          <td><?= $this->Html->link($user->username, ['action' => 'view', $user->id]) ?></td>
          <td><?= $user->has('role') ? $this->Html->link($user->role->name, ['controller' => 'Roles', 'action' => 'view', $user->role->id]) : '' ?></td>
          <td><?= $user->has('member') ? $this->Html->link($user->member->FullName, ['controller' => 'Members', 'action' => 'view', $user->member->id]) : '' ?></td>
          <td class="actions">
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $user->id]) ?>
          </td>
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
