<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Mailing $mailing
 * @var string $search text searched in the bill labels
 * @var \App\Model\Entity\Member[] $recipients members having a matching bill, with these bills
 * @var array $placeholders [placeholder => label]
 */
$this->Html->css('mailings', ['block' => true]);
$this->Html->script('mailings', ['block' => true, 'defer' => true]);
$withEmail = array_filter($recipients, fn ($member) => trim((string)$member->email) !== '');
?>
<div class="row">
  <?= $this->element('Mailings/nav') ?>
  <div class="col-md-9">
    <div class="mailings form content">
      <?= $this->Form->create($mailing) ?>
      <?= $this->Form->hidden('searched', ['value' => $search]) ?>
      <fieldset>
        <legend><?= __('New email by bill') ?></legend>
        <p class="ml-muted"><?= __('The email is sent to the members, active or past, having a bill (not canceled) whose label contains the text.') ?></p>
        <div class="ml-search">
          <?= $this->Form->control('bill_label', ['label' => __('Text in the bill label'), 'maxlength' => 200, 'value' => $search,
            'placeholder' => __('e.g. {0}', 'Uniforme 2025')]) ?>
          <?= $this->Form->button(__('Search'), ['name' => 'step', 'value' => 'search', 'class' => 'btn btn-filter']) ?>
        </div>

        <?php if ($search !== '') : ?>
          <h4 class="ml-recipients-title"><?= __('Recipients') ?> (<?= count($withEmail) ?>)</h4>
          <?php if (empty($recipients)) : ?>
            <p class="ml-invalid"><?= __('No bill contains this text.') ?></p>
          <?php else : ?>
            <p class="ml-muted">
              <?= __('{0} emails will be sent.', count($withEmail)) ?>
              <?php if (count($withEmail) < count($recipients)) : ?>
                <span class="ml-invalid"><?= __('{0} members without email will not receive it.', count($recipients) - count($withEmail)) ?></span>
              <?php endif; ?>
            </p>
            <table class="table table-striped table-sm">
              <thead>
                <tr>
                  <th><?= __('Member') ?></th>
                  <th><?= __('Email') ?></th>
                  <th><?= __('Status') ?></th>
                  <th><?= __('Bills') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recipients as $member) : ?>
                  <tr>
                    <td><?= $this->Html->link($member->FullName, ['controller' => 'Members', 'action' => 'view', $member->id]) ?></td>
                    <td>
                      <?php if (trim((string)$member->email) === '') : ?>
                        <span class="ml-invalid"><?= __('No email, not sent') ?></span>
                      <?php else : ?>
                        <?= h($member->email) ?>
                      <?php endif; ?>
                    </td>
                    <td><?= $member->active ? __('Active') : __('Past member') ?></td>
                    <td><?= h(implode(', ', array_map(fn ($bill) => $bill->label, $member->bills))) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        <?php endif; ?>

        <?= $this->element('Mailings/compose', compact('placeholders')) ?>
      </fieldset>
      <p class="ml-muted"><?= __('The emails are prepared on the next page, where you can start sending them.') ?></p>
      <?= $this->Form->button(__('Next'), ['name' => 'step', 'value' => 'next', 'class' => 'btn btn-save']) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
