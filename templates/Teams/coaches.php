<?php
/**
 * Coaches and help-coaches of the active teams
 *
 * @var \App\View\AppView $this
 * @var array<int, string> $teams active teams [id => name]
 * @var array<int, \App\Model\Entity\TeamsCoach[]> $coaches assignments by team id
 * @var array<int, string> $candidates members having a user account [id => full name]
 */
use App\Model\Entity\TeamsCoach;

$this->Html->css('teams', ['block' => true]);
$types = TeamsCoach::types();
?>
<div class="teams coaches content">
  <h3><?= __('Coaches') ?></h3>
  <p class="text-muted"><?= __('Only the members having a user account can be chosen. Coaches and help coaches have the same access to their teams.') ?></p>
  <?php if (empty($teams)) : ?>
    <p><?= __('No active team.') ?></p>
  <?php endif; ?>
  <?php foreach ($teams as $teamId => $teamName) : ?>
    <?php
    $assigned = [];
    foreach ($coaches[$teamId] as $row) {
      $assigned[$row->member_id] = true;
    }
    $available = array_diff_key($candidates, $assigned);
    ?>
    <section class="teams-coaches-team" id="team-<?= $teamId ?>">
      <h4><?= $this->Html->link($teamName, ['action' => 'view', $teamId]) ?></h4>
      <?php if (empty($coaches[$teamId])) : ?>
        <p class="text-muted"><?= __('No coach') ?></p>
      <?php else : ?>
        <ul class="teams-coaches-list">
          <?php foreach ($coaches[$teamId] as $row) : ?>
            <li>
              <span class="teams-coaches-type"><?= h($types[$row->type] ?? '') ?></span>
              <?= h($row->member->fullName) ?>
              <?= $this->Form->postLink('-', ['action' => 'removeCoach', $row->id], [
                'class' => 'btn btn-danger btn-sm',
                'title' => __('Remove'),
                'confirm' => __('Remove {0} from the coaches of {1}?', $row->member->fullName, $teamName),
              ]) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if (!empty($available)) : ?>
        <?= $this->Form->create(null, ['url' => ['action' => 'addCoach'], 'class' => 'teams-coaches-add']) ?>
        <?= $this->Form->hidden('team_id', ['value' => $teamId]) ?>
        <?= $this->Form->control('member_id', [
          'type' => 'select',
          'options' => $available,
          'empty' => __('Choose a member'),
          'required' => true,
          'label' => false,
          'aria-label' => __('Member'),
        ]) ?>
        <?= $this->Form->control('type', [
          'type' => 'select',
          'options' => $types,
          'label' => false,
          'aria-label' => __('Type'),
        ]) ?>
        <?= $this->Form->button(__('Add'), ['class' => 'btn btn-success btn-sm']) ?>
        <?= $this->Form->end() ?>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>
