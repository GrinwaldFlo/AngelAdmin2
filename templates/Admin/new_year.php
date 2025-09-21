<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Configuration $configuration
 */
?>
<div class="row">
  <div class="col">
    <div class="configurations view content">
      <h3><?= __("New year"); ?></h3>
      <table class="table table-striped table-hover table-sm">
        <tr>
          <th><?= __('First day of this season') ?></th>
          <td><?= h($config['dateSeasonStart']->format('d M y')) ?>
            <br>
            <?= $this->Html->link(__('Update first day of the season'), ['controller' => 'Configurations', 'action' => 'edit', 3]) ?>
            <br>
            <?= $this->Html->link(__('Update current year'), ['controller' => 'Configurations', 'action' => 'edit', 4]) ?>
          </td>
        </tr>
        <tr>
          <th><?= __('Actions') ?></th>
          <td>
            <?= $this->Html->link(__('Reset membership fees').'...', ['action' => 'resetMembership'], ['class' => 'btn btn-primary btn-sm']) ?>
            <?= $this->Html->link(__('Generate membership fees').'...', ['action' => 'generateMembership'], ['class' => 'btn btn-primary btn-sm']) ?>
            <?= $this->Html->link(__('Reset registration').'...', ['action' => 'resetRegistration'], ['class' => 'btn btn-primary btn-sm']) ?>
          </td>
        </tr>
      </table>
    </div>
  </div>
</div>
