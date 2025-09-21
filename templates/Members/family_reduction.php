<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */
?>

<div class="members index content">
  <h3><?= __('List') ?></h3>
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr>
          <th><?= __("Name") ?></th>
          <th><?= __("Team") ?></th>
          <th><?= __("Age") ?></th>
          <th><?= __("Discount") ?></th>
          <th><?= __("Address") ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members2 as $member): ?>
          <tr>
            <td><?= $this->Html->link($member->fullName, ['action' => 'view', $member->id]) ?></td>
            <td><?= h($member->TeamString) ?></td>
            <td><?= h($member->age) ?></td>
            <td><?= h($member->discount) ?></td>
            <td><?= h($member->FullAddress) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
</div>


