<?php ?>


<div class="members index content">
  <h3><?= __('Reset membership fees') ?></h3>
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr>
          <th><?= __("Name") ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $member): ?>
          <tr>
            <td><?= $this->Html->link($member->fullName, ['action' => 'view', $member->id]) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?= $this->Html->link(__('Reset registration').' !', ['action' => 'resetRegistration', $resetValidation], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
