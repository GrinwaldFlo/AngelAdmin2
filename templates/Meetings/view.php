<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Meeting $meeting
 * @var array $attendance list of ['id', 'name', 'state', 'inTeam'], see MeetingsTable::attendanceList()
 * @var array $neighbours ['prev' => Meeting|null, 'next' => Meeting|null]
 */
use App\Model\Entity\Presence;
use Cake\Routing\Router;

$this->loadHelper('Meeting');
$this->Html->css('meetings', ['block' => true]);
$this->Html->script('meetings', ['block' => true, 'defer' => true]);
$m = $this->Meeting;
$separatorShown = false;
?>
<div class="mt-page mt-attendance"
     data-url="<?= Router::url(['controller' => 'Presences', 'action' => 'setPresence', $meeting->id]) ?>"
     data-csrf="<?= h($this->request->getAttribute('csrfToken')) ?>"
     data-error="<?= h(__('The attendance could not be saved. Check your connection and try again.')) ?>">
  <div class="mt-view-head">
    <?= $this->Html->link($m->icon('arrow_back'), ['action' => 'index', $meeting->team_id], ['class' => 'mt-icon-btn', 'escape' => false, 'title' => __('Back'), 'aria-label' => __('Back')]) ?>
    <div class="mt-view-title">
      <h2 class="mt-title"><?= h($meeting->name) ?></h2>
      <div class="mt-muted">
        <?= h($meeting->team->name) ?> · <?= h($meeting->meeting_date->i18nFormat('EEE dd.MM.yyyy HH:mm')) ?>
        <?php if (!empty($meeting->url)) : ?>
          · <?= $this->Html->link(__('Link'), $meeting->url, ['target' => '_blank', 'rel' => 'noopener']) ?>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($curRole->MemberEditAll) : ?>
      <div class="dropdown">
        <button class="mt-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= __('Actions') ?>"><?= $m->icon('more_vert') ?></button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><?= $this->Html->link($m->icon('edit') . ' ' . __('Edit'), ['action' => 'edit', $meeting->id], ['class' => 'dropdown-item', 'escape' => false]) ?></li>
          <li><?= $this->Form->postLink($m->icon('delete') . ' ' . __('Delete'), ['action' => 'delete', $meeting->id], ['class' => 'dropdown-item text-danger', 'escape' => false, 'confirm' => __('Are you sure you want to delete {0}?', $meeting->name)]) ?></li>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <nav class="mt-neighbours" aria-label="<?= __('Other meetings') ?>">
    <?php foreach (['prev' => ['chevron_left', __('Previous')], 'next' => ['chevron_right', __('Next')]] as $key => [$icon, $label]) : ?>
      <?php if ($neighbours[$key]) : ?>
        <?= $this->Html->link(
            ($key == 'prev' ? $m->icon($icon) : '') . '<span>' . h($neighbours[$key]->meeting_date->i18nFormat('dd.MM')) . ' ' . h($neighbours[$key]->name) . '</span>' . ($key == 'next' ? $m->icon($icon) : ''),
            ['action' => 'view', $neighbours[$key]->id],
            ['class' => 'mt-neighbour mt-neighbour-' . $key, 'escape' => false, 'title' => $label]
        ) ?>
      <?php else : ?>
        <span class="mt-neighbour"></span>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <div class="mt-toolbar">
    <div class="mt-filters">
      <?= $m->filterButton('all', __('All'), 'all', 'groups') ?>
      <?= $m->filterButton((string)Presence::NONE, __('Not set'), 'none', 'help') ?>
      <?php foreach (Presence::states() as $state => $info) : ?>
        <?= $m->filterButton((string)$state, $info['label'], $info['key'], $info['icon']) ?>
      <?php endforeach; ?>
    </div>
    <button type="button" class="mt-btn mt-btn-outline mt-bulk" data-state="<?= Presence::PRESENT ?>" hidden>
      <?= $m->icon('done_all') ?> <?= __('Mark the rest as present') ?>
    </button>
  </div>

  <?php if (empty($attendance)) : ?>
    <div class="mt-empty">
      <?= $m->icon('group_off', 'mt-empty-icon') ?>
      <p><?= __('No active member in this team') ?></p>
    </div>
  <?php endif; ?>

  <ul class="mt-members">
    <?php foreach ($attendance as $row) : ?>
      <?php if (!$row['inTeam'] && !$separatorShown) : ?>
        <?php $separatorShown = true; ?>
        <li class="mt-separator"><?= __('Not in the team anymore') ?></li>
      <?php endif; ?>
      <li class="mt-member" data-member="<?= $row['id'] ?>" data-state="<?= $row['state'] ?>">
        <span class="mt-member-name"><?= h($row['name']) ?></span>
        <?= $m->stateButtons((int)$row['state']) ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="mt-toast" role="alert" hidden></div>
</div>
