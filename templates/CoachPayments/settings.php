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
 */
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
            <td><span class="badge bg-<?= $type->active ? 'success' : 'dark' ?>"><?= $type->active ? __('Active') : __('Inactive') ?></span></td>
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
