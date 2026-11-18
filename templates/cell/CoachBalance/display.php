<?php

/**
 * Balance, payments and IBAN of the coach on their own page (coach payment module)
 *
 * @var \App\View\AppView $this
 * @var array<int, array{site: \App\Model\Entity\Site|null, balance: array|null, payments: \App\Model\Entity\CoachPayment[], account: \App\Model\Entity\CoachAccount|null}> $cards by site id
 */
if (empty($cards)) {
    return;
}
$multiSite = count($cards) > 1;
?>
<?= $this->Html->css('coach-payments') ?>
<div class="row px-0 mx-0" id="coach-balance">
  <div class="col px-0 mx-0">
    <div class="members view content">
      <h4><?= __('Coach payments') ?></h4>
      <?php foreach ($cards as $siteId => $card) : ?>
            <?php
            $balance = $card['balance'];
            $formId = 'coach-iban-' . (int)$siteId;
            ?>
        <section class="cp-balance-card">
            <?php if ($multiSite) : ?>
            <h5><?= h($card['site']->city ?? '') ?></h5>
            <?php endif; ?>
          <dl class="cp-balance-grid">
            <dt><?= __('Balance to be paid') ?></dt>
            <dd class="cp-amount"><?= $this->Number->currency($balance['dueTotal'] ?? 0, 'CHF') ?></dd>
            <dt><?= __('Planned') ?></dt>
            <dd><?= $this->Number->currency($balance['plannedTotal'] ?? 0, 'CHF') ?>
              <span class="text-muted">(<?= __n('{0} meeting', '{0} meetings', count($balance['planned'] ?? []), count($balance['planned'] ?? [])) ?>)</span></dd>
            <dt><?= __('IBAN') ?></dt>
            <dd>
                <?php if ($card['account'] !== null) : ?>
                <span class="cp-iban"><?= h($card['account']->masked_iban) ?></span>
                <button type="button" class="btn btn-edit btn-sm" data-bs-toggle="collapse" data-bs-target="#<?= $formId ?>" aria-expanded="false" aria-controls="<?= $formId ?>"><?= __('Change IBAN') ?></button>
                <?php else : ?>
                <div class="alert alert-warning cp-iban-missing"><?= __('No IBAN set: you can\'t be paid until you set one.') ?></div>
                <button type="button" class="btn btn-add btn-sm" data-bs-toggle="collapse" data-bs-target="#<?= $formId ?>" aria-expanded="false" aria-controls="<?= $formId ?>"><?= __('Set IBAN') ?></button>
                <?php endif; ?>
            </dd>
          </dl>
          <div class="collapse" id="<?= $formId ?>">
            <?= $this->Form->create(null, ['url' => ['controller' => 'CoachAccounts', 'action' => 'setIban', $siteId], 'class' => 'cp-iban-form']) ?>
            <?= $this->Form->control('iban', [
                'label' => __('New IBAN (Swiss or Liechtenstein account)'),
                'value' => '',
                'autocomplete' => 'off',
                'required' => true,
                'maxlength' => 42,
                'placeholder' => 'CH00 0000 0000 0000 0000 0',
            ]) ?>
            <?= $this->Form->button(__('Save'), ['class' => 'btn btn-save']) ?>
            <?= $this->Form->end() ?>
          </div>
            <?php if (!empty($card['payments'])) : ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover table-sm">
                <thead>
                  <tr><th><?= __('Paid on') ?></th><th class="text-end"><?= __('Amount') ?></th><th class="text-end"><?= __('Meetings') ?></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($card['payments'] as $payment) : ?>
                    <tr>
                      <td><?= h($payment->paid_date->i18nFormat('dd.MM.yyyy')) ?></td>
                      <td class="text-end"><?= $this->Number->currency((float)$payment->amount, 'CHF') ?></td>
                      <td class="text-end"><?= (int)$payment->presences_count ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
        </section>
      <?php endforeach; ?>
    </div>
  </div>
</div>
