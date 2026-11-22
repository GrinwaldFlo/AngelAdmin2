<?php

/**
 * Coach payment settings of a site: presence types and IBAN of the coaches
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Site $site
 * @var iterable<\App\Model\Entity\CoachPresenceType> $types
 * @var \App\Model\Entity\CoachPresenceType $newType
 * @var array<int, \App\Model\Entity\Member> $coaches by member id
 * @var array<int, \App\Model\Entity\CoachAccount> $accounts by member id
 * @var array<int, string[]> $teamNames coached teams by member id
 * @var \App\Model\Entity\CoachPresenceType[] $activeTypes active types of the site, columns of the limits
 * @var array<int, string> $teams active teams of the site, names by id
 * @var array<int, array<int, int>> $limits [team id => [type id => max coaches per meeting]]
 */
use App\Model\Table\CoachPresenceLimitsTable;

$this->Html->css('coach-payments', ['block' => true]);
?>
<div class="content">
  <div class="btn-bar float-end">
    <?= $this->Html->link(__('Coach payments'), ['action' => 'index'], ['class' => 'btn btn-nav btn-sm']) ?>
  </div>
  <h3><?= __('Coach payment settings') ?> - <?= h($site->city) ?></h3>

  <h4><?= __('Presence types') ?></h4>
  <div class="table-responsive">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr>
          <th><?= __('Label') ?></th>
          <th class="text-end"><?= __('Amount') ?></th>
          <th class="text-end"><?= __('Order') ?></th>
          <th><?= __('Active') ?></th>
          <th class="actions"><?= __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($types as $type) : ?>
          <tr>
            <td><?= h($type->label) ?></td>
            <td class="text-end"><?= $this->Number->currency((float)$type->amount, 'CHF') ?></td>
            <td class="text-end"><?= (int)$type->sort ?></td>
            <td><span class="badge bg-<?= $type->status_html ?>"><?= h($type->status_string) ?></span></td>
            <td class="actions">
              <?= $this->my->iconLink('edit', ['action' => 'editType', $type->id], __('Edit')) ?>
              <?= $this->my->iconPostLink('delete', ['action' => 'deleteType', $type->id], __('Delete'), [
                  'confirm' => __('Are you sure you want to delete {0}?', $type->label),
                  'class' => 'icon-btn-delete',
              ]) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= $this->Form->create($newType, ['url' => ['action' => 'addType'], 'class' => 'cp-type-form']) ?>
  <div class="row g-2 align-items-end">
    <div class="col-12 col-md-4"><?= $this->Form->control('label', ['label' => __('Label')]) ?></div>
    <div class="col-6 col-md-3"><?= $this->Form->control('amount', ['label' => __('Amount (CHF)'), 'type' => 'number', 'step' => '0.05', 'min' => 0]) ?></div>
    <div class="col-6 col-md-2"><?= $this->Form->control('sort', ['label' => __('Order'), 'type' => 'number']) ?></div>
    <div class="col-12 col-md-3 cp-type-submit"><?= $this->Form->button(__('Add'), ['class' => 'btn btn-add']) ?></div>
  </div>
  <?= $this->Form->end() ?>

  <h4 class="cp-section-title" id="limits"><?= __('Limits per team') ?></h4>
  <?php if (empty($teams) || empty($activeTypes)) : ?>
    <p class="text-muted"><?= empty($teams) ? __('No active team in this city.') : __('Add an active presence type first.') ?></p>
  <?php else : ?>
    <p class="text-muted"><?= __('Maximum number of coaches with each presence type at one meeting of the team. Empty = no limit, 0 = type not allowed.') ?></p>
    <?= $this->Form->create(null, ['url' => ['action' => 'saveLimits']]) ?>
    <div class="table-responsive">
      <table class="table table-striped table-hover table-sm cp-limits">
        <thead>
          <tr>
            <th><?= __('Team') ?></th>
            <?php foreach ($activeTypes as $type) : ?>
              <th class="text-end"><?= h($type->label) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($teams as $teamId => $teamName) : ?>
            <tr>
              <td><?= h($teamName) ?></td>
              <?php foreach ($activeTypes as $type) : ?>
                <td class="text-end">
                  <?= $this->Form->control("limits.{$teamId}.{$type->id}", [
                      'label' => false,
                      'type' => 'number',
                      'min' => 0,
                      'max' => CoachPresenceLimitsTable::MAX,
                      'step' => 1,
                      'value' => $limits[$teamId][$type->id] ?? '',
                      'required' => false,
                      'class' => 'cp-limit-input',
                      'aria-label' => __('Maximum of {0} for {1}', $type->label, $teamName),
                  ]) ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= $this->Form->button(__('Save'), ['class' => 'btn btn-save']) ?>
    <?= $this->Form->end() ?>
  <?php endif; ?>

  <h4 class="cp-section-title"><?= __('Coaches') ?></h4>
  <?php if (empty($coaches)) : ?>
    <p class="text-muted"><?= __('No coach in this city.') ?></p>
  <?php else : ?>
    <div class="table-responsive">
      <table class="table table-striped table-hover table-sm">
        <thead>
          <tr>
            <th><?= __('Coach') ?></th>
            <th><?= __('Teams') ?></th>
            <th><?= __('IBAN') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($coaches as $memberId => $coach) : ?>
                <?php $account = $accounts[$memberId] ?? null; ?>
            <tr>
              <td>
                    <?= $this->Html->link($coach->full_name, ['controller' => 'Members', 'action' => 'view', $memberId]) ?>
                    <?php if (empty($coach->address) || empty($coach->postcode) || empty($coach->city)) : ?>
                  <span class="badge bg-warning"><?= __('Address incomplete') ?></span>
                    <?php endif; ?>
              </td>
              <td><?= h(implode(', ', $teamNames[$memberId] ?? [])) ?></td>
              <td>
                    <?php if ($account !== null) : ?>
                  <span class="cp-iban"><?= h($account->masked_iban) ?></span>
                    <?php else : ?>
                  <span class="badge bg-warning"><?= __('No IBAN set') ?></span>
                    <?php endif; ?>
              </td>
              <td class="actions">
                <?= $this->Form->create(null, ['url' => ['action' => 'setIban', $memberId], 'class' => 'cp-iban-inline']) ?>
                <?= $this->Form->control('iban', ['label' => false, 'value' => '', 'autocomplete' => 'off', 'required' => true, 'maxlength' => 42, 'placeholder' => __('New IBAN'), 'aria-label' => __('New IBAN of {0}', $coach->full_name)]) ?>
                <?= $this->Form->button(__('Save'), ['class' => 'btn btn-save btn-sm']) ?>
                <?= $this->Form->end() ?>
                    <?php if ($account !== null) : ?>
                        <?= $this->my->iconPostLink('delete', ['action' => 'removeIban', $memberId], __('Remove IBAN'), [
                            'confirm' => __('Remove the IBAN of {0}?', $coach->full_name),
                            'class' => 'icon-btn-delete',
                        ]) ?>
                    <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
