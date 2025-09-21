<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $role
 */
?>
<div class="row">
  <div class="col">
    <div class="roles view content">
      <div class="icon float-end">
        <?= $this->Html->link('<i class="gg-pen"></i>', ['action' => 'edit', $role->id], ['escape' => false]) ?>
        <?= $curRole->MemberViewAll ? $this->backButtonCtrl('Roles', 'index') : $this->BackButton('/') ?>
      </div>
      <h3><?= h($role->name) ?></h3>
      <table class="table table-striped table-hover table-sm">
        <tr>
          <th><?= __('Name') ?></th>
          <td><?= h($role->name) ?></td>
        </tr>
        <tr>
          <th><?= __('Id') ?></th>
          <td><?= $this->Number->format($role->id) ?></td>
        </tr>
        <tr>
          <th><?= ('MemberViewAll') ?></th>
          <td><?= $role->MemberViewAll ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= ('MemberViewOwn') ?></th>
          <td><?= $role->MemberViewOwn ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= ('MemberEditAll') ?></th>
          <td><?= $role->MemberEditAll ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= ('MemberEditOwn') ?></th>
          <td><?= $role->MemberEditOwn ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= ('BillViewAll') ?></th>
          <td><?= $role->BillViewAll ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= ('BillViewOwn') ?></th>
          <td><?= $role->BillViewOwn ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= ('BillEditAll') ?></th>
          <td><?= $role->BillEditAll ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= ('BillValidate') ?></th>
          <td><?= $role->BillValidate ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= __x('person', 'Editor') ?></th>
          <td><?= $role->Editor ? __('Yes') : __('No'); ?></td>
        </tr>
      </table>
      <div class="related">
        <h4><?= __('Users') ?></h4>
        <?php if (!empty($role->users)) : ?>
          <table class="table table-striped table-hover table-sm">
            <tr>
              <th><?= __('Id') ?></th>
              <th><?= __('Username') ?></th>
            </tr>
            <?php foreach ($role->users as $users) : ?>
              <tr>
                <td><?= h($users->id) ?></td>
                <td><?= $this->Html->link(h($users->username), ['controller' => 'Users', 'action' => 'view', $users->id]) ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
