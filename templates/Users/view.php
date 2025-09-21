<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<div class="row">
  <div class="col">
    <div class="users view content">
      <div class="icon float-end">
        <?php if ($curRole->Admin) : ?>
          <?= $this->Html->link('<i class="gg-pen"></i>', ['action' => 'edit', $user->id], ['escape' => false]) ?>
        <?php endif; ?>
        <?= $this->backButtonCtrl('Users', 'index') ?>
      </div>
      <h3><?= h($user->id) ?></h3>
      <table class="table table-striped table-hover table-sm">
        <tr>
          <th><?= __('Username') ?></th>
          <td><?= h($user->username) ?></td>
        </tr>
        <tr>
          <th><?= __('Role') ?></th>
          <td><?= $user->has('role') ? $this->Html->link($user->role->name, ['controller' => 'Roles', 'action' => 'view', $user->role->id]) : '' ?></td>
        </tr>
        <tr>
          <th><?= __('Member') ?></th>
          <td><?= $user->has('member') ? $this->Html->link($user->member->FullName, ['controller' => 'Members', 'action' => 'view', $user->member->id]) : '' ?></td>
        </tr>
        <tr>
          <th><?= __('Pass Key') ?></th>
          <td><?= h($user->pass_key) ?></td>
        </tr>
        <tr>
          <th><?= __('Tokenhash') ?></th>
          <td><?= h($user->tokenhash) ?></td>
        </tr>
        <tr>
          <th><?= __('Id') ?></th>
          <td><?= $this->Number->format($user->id) ?></td>
        </tr>
        <tr>
          <th><?= __('Created') ?></th>
          <td><?= h($user->created) ?></td>
        </tr>
        <tr>
          <th><?= __('Modified') ?></th>
          <td><?= h($user->modified) ?></td>
        </tr>
      </table>
    </div>
  </div>
</div>
