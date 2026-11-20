<?php

/**
 * Coach payments of a site: one section per coach with statistics, open presences, QR code and payments
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Site $site
 * @var array<int, \App\Model\Entity\Member> $coaches by member id
 * @var array<int, \App\Model\Entity\CoachAccount> $accounts by member id
 * @var array<int, string[]> $teamNames coached teams by member id
 * @var array<int, \App\Model\Entity\CoachPresenceType> $types by id
 * @var array<int, array> $balances see CoachPresencesTable::balances()
 * @var array<int, array<string, array<int, int>>> $statistics see CoachPresencesTable::statistics()
 * @var array<int, \App\Model\Entity\CoachPayment[]> $payments by member id
 * @var array<int, string> $qrCodes SVG data URI by member id
 * @var array<int, string[]> $qrMissing why the QR code can't be built, by member id
 */
$this->Html->css('coach-payments', ['block' => true]);

// Coaches with presences or payments first, the others at the end
$active = array_filter($coaches, fn ($coach) => isset($balances[$coach->id]) || isset($statistics[$coach->id]) || isset($payments[$coach->id]));
$inactive = array_diff_key($coaches, $active);
$currency = fn ($amount) => $this->Number->currency((float)$amount, 'CHF');
?>
<div class="content">
  <div class="btn-bar float-end">
    <?= $this->Html->link(__('Settings'), ['action' => 'settings'], ['class' => 'btn btn-nav btn-sm']) ?>
  </div>
  <h3><?= __('Coach payments') ?> - <?= h($site->city) ?></h3>
  <?php if (empty($types)) : ?>
    <div class="alert alert-warning"><?= __('No presence type is defined for this city.') ?> <?= $this->Html->link(__('Settings'), ['action' => 'settings']) ?></div>
  <?php endif; ?>
  <?php if (empty($active)) : ?>
    <p class="text-muted"><?= __('No coach presence in this city.') ?></p>
  <?php endif; ?>

  <?php foreach ($active as $memberId => $coach) : ?>
        <?php
        $balance = $balances[$memberId] ?? ['due' => [], 'planned' => [], 'dueTotal' => 0.0, 'plannedTotal' => 0.0];
        ?>
    <section class="cp-coach-section" id="coach-<?= (int)$memberId ?>">
      <h4>
            <?= h($coach->full_name) ?>
        <span class="cp-balance badge bg-<?= $balance['dueTotal'] > 0 ? 'warning' : 'success' ?>"><?= $currency($balance['dueTotal']) ?></span>
      </h4>
      <p class="text-muted"><?= h(implode(', ', $teamNames[$memberId] ?? [])) ?>
        · <?= __('IBAN') ?>: <?= isset($accounts[$memberId]) ? h($accounts[$memberId]->masked_iban) : __('No IBAN set') ?></p>

        <?php if (!empty($statistics[$memberId])) : ?>
        <h5><?= __('Season statistics') ?></h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm">
            <thead>
              <tr>
                <th><?= __('Team') ?></th>
                <?php foreach ($types as $type) : ?>
                  <th class="text-end"><?= h($type->label) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($statistics[$memberId] as $teamName => $counts) : ?>
                <tr>
                  <td><?= h($teamName) ?></td>
                    <?php foreach ($types as $typeId => $type) : ?>
                    <td class="text-end"><?= (int)($counts[$typeId] ?? 0) ?></td>
                    <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($balance['due']) || !empty($balance['planned'])) : ?>
        <h5><?= __('Open presences') ?></h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm">
            <thead>
              <tr>
                <th><?= __('Date') ?></th>
                <th><?= __('Meeting') ?></th>
                <th><?= __('Team') ?></th>
                <th><?= __('Type') ?></th>
                <th class="text-end"><?= __('Amount') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (['due' => '', 'planned' => 'cp-planned'] as $key => $class) : ?>
                    <?php foreach ($balance[$key] as $presence) : ?>
                  <tr class="<?= $class ?>">
                    <td><?= h($presence->meeting->meeting_date->i18nFormat('dd.MM.yyyy')) ?></td>
                    <td><?= $this->Html->link($presence->meeting->name, ['controller' => 'Meetings', 'action' => 'view', $presence->meeting_id]) ?></td>
                    <td><?= h($presence->meeting->team->name) ?></td>
                    <td><?= h($presence->coach_presence_type->label) ?>
                        <?php if ($key === 'planned') : ?>
                        <span class="badge bg-info"><?= __('Planned') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= $currency($presence->value()) ?></td>
                  </tr>
                    <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr><th colspan="4"><?= __('Balance to be paid') ?></th><th class="text-end"><?= $currency($balance['dueTotal']) ?></th></tr>
              <?php if ($balance['plannedTotal'] > 0) : ?>
                <tr><td colspan="4"><?= __('Planned (not due yet)') ?></td><td class="text-end"><?= $currency($balance['plannedTotal']) ?></td></tr>
              <?php endif; ?>
            </tfoot>
          </table>
        </div>
        <?php endif; ?>

        <?php if ($balance['dueTotal'] > 0) : ?>
            <?php if (isset($qrCodes[$memberId])) : ?>
          <div class="cp-pay">
            <img class="cp-qr" src="<?= $qrCodes[$memberId] ?>" alt="<?= h(__('QR code to pay {0}', $coach->full_name)) ?>">
            <div>
              <p><?= __('Scan the QR code with your banking app, then mark the balance as paid.') ?></p>
                <?= $this->Form->postLink(__('Mark {0} as paid', $currency($balance['dueTotal'])), ['action' => 'pay', $memberId], [
                    'data' => ['amount' => number_format($balance['dueTotal'], 2, '.', '')],
                    'class' => 'btn btn-add',
                    'confirm' => __('Mark {0} paid to {1}?', $currency($balance['dueTotal']), $coach->full_name),
                ]) ?>
            </div>
          </div>
            <?php else : ?>
          <div class="alert alert-warning">
                <?= __('The QR code can\'t be created:') ?> <?= h(implode('. ', $qrMissing[$memberId] ?? [])) ?>.
                <?= $this->Html->link(__('Settings'), ['action' => 'settings']) ?>
          </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($payments[$memberId])) : ?>
        <h5><?= __('Payments') ?></h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm">
            <thead>
              <tr>
                <th><?= __('Paid on') ?></th>
                <th class="text-end"><?= __('Amount') ?></th>
                <th class="text-end"><?= __('Meetings') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments[$memberId] as $payment) : ?>
                <tr>
                  <td><?= h($payment->paid_date->i18nFormat('dd.MM.yyyy')) ?></td>
                  <td class="text-end"><?= $currency($payment->amount) ?></td>
                  <td class="text-end"><?= (int)$payment->presences_count ?></td>
                  <td class="actions">
                    <?= $this->my->iconPostLink('delete', ['action' => 'deletePayment', $payment->id], __('Delete'), [
                        'confirm' => __('Delete this payment? Its presences become open again.'),
                        'class' => 'icon-btn-delete',
                    ]) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
    </section>
  <?php endforeach; ?>

  <?php if (!empty($inactive)) : ?>
    <details class="cp-inactive">
      <summary><?= __('Coaches without presence ({0})', count($inactive)) ?></summary>
      <ul>
        <?php foreach ($inactive as $coach) : ?>
          <li><?= h($coach->full_name) ?></li>
        <?php endforeach; ?>
      </ul>
    </details>
  <?php endif; ?>
</div>
