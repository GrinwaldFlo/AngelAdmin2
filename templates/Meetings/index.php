<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Meeting[] $meetings
 * @var array $teams [id => name]
 * @var int $teamId 0 = no team selected
 * @var int $meetingFilter
 * @var int $limit
 * @var bool $hasMore
 */
use App\Model\Table\MeetingsTable;
use Cake\Routing\Router;

$this->loadHelper('Meeting');
$this->Html->css('meetings', ['block' => true]);
$this->Html->script('meetings', ['block' => true, 'defer' => true]);
$m = $this->Meeting;

$isPast = $meetingFilter == MeetingsTable::FILTER_PAST;
$today = array_values(array_filter($meetings, fn($meeting) => !$isPast && $m->isToday($meeting)));
$others = array_filter($meetings, fn($meeting) => $isPast || !$m->isToday($meeting));
?>
<div class="mt-page">
  <div class="mt-header">
    <h2 class="mt-title"><?= __('Attendance') ?></h2>
    <?php if ($teamId > 0 && count($teams) > 1) : ?>
      <select class="form-select mt-team-select" aria-label="<?= __('Team') ?>">
        <?php foreach ($teams as $id => $name) : ?>
          <option value="<?= $id ?>" data-url="<?= Router::url(['action' => 'index', $id, $meetingFilter]) ?>" <?= $id == $teamId ? 'selected' : '' ?>><?= h($name) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
  </div>

  <?php if ($teamId == 0) : ?>
    <p class="mt-muted"><?= __('Choose a team') ?></p>
    <div class="mt-team-grid">
      <?php foreach ($teams as $id => $name) : ?>
        <?= $this->Html->link($m->icon('groups') . '<span>' . h($name) . '</span>', ['action' => 'index', $id], ['class' => 'mt-card mt-team-card', 'escape' => false]) ?>
      <?php endforeach; ?>
      <?php if (empty($teams)) : ?>
        <p class="mt-muted"><?= __('No active team') ?></p>
      <?php endif; ?>
    </div>
  <?php else : ?>
    <nav class="mt-tabs" aria-label="<?= __('Meetings') ?>">
      <?= $this->Html->link(__('Upcoming'), ['action' => 'index', $teamId, MeetingsTable::FILTER_UPCOMING], ['class' => 'mt-tab' . ($isPast ? '' : ' is-active')]) ?>
      <?= $this->Html->link(__('Past'), ['action' => 'index', $teamId, MeetingsTable::FILTER_PAST], ['class' => 'mt-tab' . ($isPast ? ' is-active' : '')]) ?>
    </nav>

    <?php foreach ($today as $meeting) : ?>
      <div class="mt-card mt-today">
        <div class="mt-today-label"><?= $m->icon('today') ?><?= __('Today') ?></div>
        <div class="mt-today-name"><?= h($meeting->name) ?></div>
        <?= $m->badges($meeting) ?>
        <?= $m->counts($meeting) ?>
        <?= $this->Html->link(__('Take attendance') . $m->icon('arrow_forward'), ['action' => 'view', $meeting->id], ['class' => 'mt-btn mt-btn-primary mt-today-btn', 'escape' => false]) ?>
      </div>
    <?php endforeach; ?>

    <?php if (empty($meetings)) : ?>
      <div class="mt-empty">
        <?= $m->icon('event_available', 'mt-empty-icon') ?>
        <p><?= $isPast ? __('No past meeting') : __('No upcoming meeting') ?></p>
      </div>
    <?php endif; ?>

    <?php $month = null; ?>
    <?php foreach ($others as $meeting) : ?>
      <?php $curMonth = $meeting->meeting_date->i18nFormat('LLLL yyyy'); ?>
      <?php if ($curMonth !== $month) : ?>
        <?= $month === null ? '' : '</ul>' ?>
        <h3 class="mt-month"><?= h($curMonth) ?></h3>
        <ul class="mt-list">
        <?php $month = $curMonth; ?>
      <?php endif; ?>
      <li>
        <a class="mt-item" href="<?= Router::url(['action' => 'view', $meeting->id]) ?>">
          <?= $m->dateBlock($meeting) ?>
          <div class="mt-item-body">
            <div class="mt-item-name"><?= h($meeting->name) ?></div>
            <?= $m->badges($meeting) ?>
            <?= $isPast ? $m->counts($meeting) : '' ?>
          </div>
          <?= $m->icon('chevron_right', 'mt-chevron') ?>
        </a>
      </li>
    <?php endforeach; ?>
    <?= $month === null ? '' : '</ul>' ?>

    <?php if ($hasMore) : ?>
      <?= $this->Html->link(__('Show more'), ['action' => 'index', $teamId, $meetingFilter, '?' => ['n' => $limit * 2]], ['class' => 'mt-btn mt-btn-outline mt-more']) ?>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($curRole->MemberEditAll) : ?>
    <?= $this->Html->link($m->icon('add'), ['action' => 'addMultiple'], ['class' => 'mt-fab', 'escape' => false, 'title' => __('New meeting'), 'aria-label' => __('New meeting')]) ?>
  <?php endif; ?>
</div>
