<?php ?>


<div class="members index content">
  <h3><?= __('Reset membership fees') ?></h3>
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr>
          <th><?= __("Name") ?></th>
          <th><?= __("Membership fee") ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $member): ?>
          <tr>
            <td><?= $this->Html->link($member->fullName, ['action' => 'view', $member->id]) ?></td>
            <td><?= h($member->membership_fee_paid) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?= $this->Html->link(__('Reset membership fees').' !', ['action' => 'resetMembership', $resetValidation], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
