<?php
/**
 * @var \App\View\AppView $this
 * @var array $members
 * @var array $finances
 * @var array $attendance
 * @var \App\Model\Entity\Team[] $teams
 * @var int $siteId
 * @var \DateTime $seasonStart
 */
use Cake\I18n\Date;

$this->loadHelper('Dashboard');
$this->Html->css('admin-dashboard', ['block' => true]);
$d = $this->Dashboard;

$registeredPct = $d->percent($members['registered'], $members['active']);
$feesPaidPct = $d->percent($members['feesPaid'], $members['active']);
$collectedPct = $d->percent($finances['invoicedPaid'], $finances['invoiced']);
$monthlyMax = max([1, ...array_column($finances['monthly'], 'invoiced')]);
?>
<div class="dashboard">
  <div class="dash-header">
    <h2><?= __('Dashboard') ?></h2>
    <span class="dash-scope"><?= isset($navSites[$siteId]) ? h($navSites[$siteId]->city) : __('All locations') ?></span>
    <div class="dash-subtitle"><?= h($config['clubName']) ?> · <?= __('Season since {0}', $seasonStart->format('d.m.Y')) ?></div>
  </div>

  <div class="dash-kpis">
    <?= $d->kpi(
        __('Active members'),
        (string)$members['active'],
        __('+{0} new / -{1} left this season', $members['new'], $members['left'])
    ) ?>
    <?= $d->kpi(
        __('Registered'),
        $d->percentStr($registeredPct),
        __('{0} / {1} members', $members['registered'], $members['active']),
        [$members['registered'], $members['active']],
        $d->rateVariant($registeredPct)
    ) ?>
    <?= $d->kpi(
        __('Membership fee paid'),
        $d->percentStr($feesPaidPct),
        __('{0} / {1} members', $members['feesPaid'], $members['active']),
        [$members['feesPaid'], $members['active']],
        $d->rateVariant($feesPaidPct)
    ) ?>
    <?= $d->kpi(
        __('Open invoices'),
        $d->money($finances['openSum']),
        __('{0} invoices, {1} late', $finances['openCount'], $finances['lateCount'])
    ) ?>
    <?= $d->kpi(
        __('Attendance ({0} days)', $attendance['days']),
        $d->percentStr($attendance['rate']),
        __('{0} presences recorded', array_sum($attendance['states'])),
        $attendance['rate'] === null ? null : [$attendance['rate'], 100],
        $d->rateVariant($attendance['rate'])
    ) ?>
  </div>

  <div class="dash-sections">
    <section class="dash-card">
      <h3><?= __('Members') ?></h3>
      <ul class="dash-list">
        <li>
          <?= $this->Html->link(__('Not registered'), ['controller' => 'Members', 'action' => 'index', 0, 4]) ?>
          <span><?= $members['active'] - $members['registered'] ?></span>
        </li>
        <li>
          <?= $this->Html->link(__('Signatures to validate'), ['controller' => 'Members', 'action' => 'signatures']) ?>
          <span><?= $members['signPending'] ?></span>
        </li>
        <li><?= __('Signatures validated') ?><span><?= $members['signValidated'] ?></span></li>
        <?php if ($members['withoutTeam'] !== null): ?>
          <li>
            <?= $this->Html->link(__('No team'), ['controller' => 'Members', 'action' => 'newMembers']) ?>
            <span><?= $members['withoutTeam'] ?></span>
          </li>
        <?php endif; ?>
      </ul>

      <h4><?= __('Gender') ?></h4>
      <?= $d->bars($members['genders']) ?>

      <h4><?= __('Age') ?></h4>
      <?= $d->bars($members['ages']) ?>

      <h4><?= __('Birthdays in the next {0} days', $members['birthdayDays']) ?></h4>
      <?php if (empty($members['birthdays'])): ?>
        <p class="dash-empty"><?= __('None') ?></p>
      <?php else: ?>
        <ul class="dash-list">
          <?php foreach ($members['birthdays'] as $birthday): ?>
            <li>
              <?= $this->Html->link(h($birthday['member']->first_name . ' ' . $birthday['member']->last_name), ['controller' => 'Members', 'action' => 'view', $birthday['member']->id]) ?>
              <span><?= $birthday['date']->format('d.m') ?> · <?= __('{0} years', $birthday['age']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="dash-card">
      <h3><?= __('Finances') ?></h3>
      <ul class="dash-list">
        <li>
          <?= $this->Html->link(__('Open invoices'), ['controller' => 'Bills', 'action' => 'index', 1]) ?>
          <span><?= $finances['openCount'] ?> · <?= $d->money($finances['openSum']) ?></span>
        </li>
        <li class="<?= $finances['lateCount'] > 0 ? 'dash-alert' : '' ?>">
          <?= $this->Html->link(__('Late invoices'), ['controller' => 'Bills', 'action' => 'index', 2]) ?>
          <span><?= $finances['lateCount'] ?> · <?= $d->money($finances['lateSum']) ?></span>
        </li>
        <li>
          <?= $this->Html->link(__('With reminder'), ['controller' => 'Bills', 'action' => 'sendReminder']) ?>
          <span><?= $finances['reminderCount'] ?></span>
        </li>
        <li>
          <?= $this->Html->link(__('Draft (not printed)'), ['controller' => 'Bills', 'action' => 'mailPrint']) ?>
          <span><?= $finances['draftCount'] ?></span>
        </li>
      </ul>

      <h4><?= __('This season') ?></h4>
      <?= $d->bar(
          __('Collected'),
          $finances['invoicedPaid'],
          $finances['invoiced'],
          $d->percentStr($collectedPct),
          $d->rateVariant($collectedPct)
      ) ?>
      <div class="dash-detail"><?= $d->money($finances['invoicedPaid']) ?> / <?= $d->money($finances['invoiced']) ?></div>
      <?php $feesPct = $d->percent($finances['feesPaid'], $finances['fees']); ?>
      <?= $d->bar(
          __('Membership fees'),
          $finances['feesPaid'],
          $finances['fees'],
          $d->percentStr($feesPct),
          $d->rateVariant($feesPct)
      ) ?>
      <div class="dash-detail"><?= $d->money($finances['feesPaid']) ?> / <?= $d->money($finances['fees']) ?></div>

      <h4><?= __('Invoiced per month') ?></h4>
      <div class="dash-legend">
        <span class="dash-legend-invoiced"><?= __('Invoiced') ?></span>
        <span class="dash-legend-paid"><?= __('Paid') ?></span>
      </div>
      <?php foreach (array_reverse($finances['monthly'], true) as $month => $amounts): ?>
        <div class="dash-month">
          <span class="dash-bar-label"><?= h(Date::parse($month . '-01')->i18nFormat('MMM yy')) ?></span>
          <div class="dash-month-bars">
            <?= $d->progress($amounts['invoiced'], $monthlyMax, __('Invoiced'), 'invoiced') ?>
            <?= $d->progress($amounts['paid'], $monthlyMax, __('Paid'), 'success') ?>
          </div>
          <span class="dash-bar-text"><?= $d->money($amounts['invoiced']) ?></span>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="dash-card">
      <h3><?= __('Attendance') ?></h3>
      <ul class="dash-list">
        <li><?= __('Present') ?><span><?= $attendance['states'][1] ?></span></li>
        <li><?= __('Late') ?><span><?= $attendance['states'][3] ?></span></li>
        <li><?= __('Excused') ?><span><?= $attendance['states'][2] ?></span></li>
        <li><?= __('Absent') ?><span><?= $attendance['states'][0] ?></span></li>
        <li><?= __('Events this season') ?><span><?= $attendance['seasonEvents'] ?></span></li>
      </ul>

      <h4><?= __('Attendance per team ({0} days)', $attendance['days']) ?></h4>
      <?php if (empty($attendance['teamRates'])): ?>
        <p class="dash-empty"><?= __('No presence recorded') ?></p>
      <?php endif; ?>
      <?php foreach ($teams as $team): ?>
        <?php if (isset($attendance['teamRates'][$team->id])): ?>
          <?php $rate = $attendance['teamRates'][$team->id]; ?>
          <?= $d->bar($team->name, $rate, 100, $d->percentStr($rate), $d->rateVariant($rate)) ?>
        <?php endif; ?>
      <?php endforeach; ?>

      <h4><?= __('Next {0} days', $attendance['upcomingDays']) ?></h4>
      <?php if ($attendance['upcoming']->isEmpty()): ?>
        <p class="dash-empty"><?= __('No event') ?></p>
      <?php else: ?>
        <ul class="dash-list">
          <?php foreach ($attendance['upcoming'] as $meeting): ?>
            <li>
              <?= $this->Html->link(h($meeting->name), ['controller' => 'Meetings', 'action' => 'view', $meeting->id]) ?>
              <span><?= $meeting->meeting_date->i18nFormat($config['dateEvent']) ?> · <?= h($meeting->team->name) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <h3 class="dash-title"><?= __('Teams') ?></h3>
  <div class="dash-teams">
    <?php foreach ($teams as $team): ?>
      <?php $rate = $attendance['teamRates'][$team->id] ?? null; ?>
      <div class="dash-card dash-team">
        <div class="dash-team-header">
          <?= $this->Html->link(h($team->name), ['controller' => 'Teams', 'action' => 'view', $team->id], ['class' => 'dash-team-name']) ?>
          <?php if ($rate !== null): ?>
            <span class="dash-badge dash-badge-<?= $d->rateVariant($rate) ?>" title="<?= __('Attendance ({0} days)', $attendance['days']) ?>"><?= $rate ?> %</span>
          <?php endif; ?>
        </div>
        <div class="dash-team-members"><?= __('{0} members', $team->NbMembers) ?></div>
        <div class="dash-team-coaches">
          <?php foreach ($team->Coachs as $coach): ?>
            <span class="dash-chip"><?= h($coach->first_name) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
