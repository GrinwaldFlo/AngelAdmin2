<?php
/**
 * Coaches and help-coaches of the active teams
 *
 * @var \App\View\AppView $this
 * @var array<int, string> $teams active teams [id => name]
 * @var array<int, array<int, \App\Model\Entity\Member[]>> $coaches members by team id, then by type
 * @var array<int, string> $candidates members having a user account [id => full name]
 */
use App\Model\Entity\TeamsCoach;

$this->Html->css('teams', ['block' => true]);
?>
<div class="teams coaches content">
  <?= $this->backButtonCtrl('Teams', 'index') ?>
  <h3><?= __('Coaches') ?></h3>
  <p class="text-muted"><?= __('Only the members having a user account can be chosen. Coaches and help coaches have the same access to their teams.') ?></p>
  <?php if (empty($teams)) : ?>
    <p><?= __('No active team.') ?></p>
  <?php else : ?>
    <?= $this->Form->create(null) ?>
    <div class="table-responsive">
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th><?= __('Team') ?></th>
            <?php foreach (TeamsCoach::types() as $label) : ?>
              <th><?= h($label) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($teams as $teamId => $teamName) : ?>
            <tr>
              <td><?= $this->Html->link($teamName, ['action' => 'view', $teamId]) ?></td>
              <?php foreach (TeamsCoach::types() as $type => $label) : ?>
                <td>
                  <?= $this->Form->control("teams.$teamId.$type", [
                    'type' => 'select',
                    'multiple' => true,
                    'options' => $candidates,
                    'value' => array_map(fn ($member) => $member->id, $coaches[$teamId][$type]),
                    'label' => false,
                    'aria-label' => $teamName . ' - ' . $label,
                    'class' => 'teams-coaches-select',
                  ]) ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= $this->Form->button(__('Save')) ?>
    <?= $this->Form->end() ?>
  <?php endif; ?>
</div>
