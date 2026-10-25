<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Meeting $meeting
 * @var array $teams [id => name]
 * @var \DateTime $today
 * @var \DateTime $seasonEnd
 */
use Cake\I18n\I18n;

$this->loadHelper('Meeting');
$this->Html->css('meetings', ['block' => true]);
$this->Html->script('meetings', ['block' => true, 'defer' => true]);

$mode = $this->request->getData('mode', 'weekly');
$weekdays = array_map('intval', (array)$this->request->getData('weekdays', []));
$dayNames = [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];
?>
<div class="mt-page">
  <div class="mt-view-head">
    <?= $this->Html->link($this->Meeting->icon('arrow_back'), ['action' => 'index'], ['class' => 'mt-icon-btn', 'escape' => false, 'title' => __('Back'), 'aria-label' => __('Back')]) ?>
    <h2 class="mt-title"><?= __('New meeting(s)') ?></h2>
  </div>

  <?= $this->Form->create($meeting, ['class' => 'mt-add-form', 'data-locale' => str_replace('_', '-', I18n::getLocale())]) ?>
  <div class="row g-3">
    <div class="col-lg-5">
      <fieldset class="mt-card">
        <legend class="mt-card-title"><?= __('Meeting') ?></legend>
        <?= $this->element('Meetings/form_fields', ['teams' => $teams]) ?>
        <?= $this->Form->control('time', ['type' => 'time', 'label' => __('Time'), 'step' => 60, 'format' => 'H:i', 'required' => true]) ?>
      </fieldset>
    </div>

    <div class="col-lg-7">
      <fieldset class="mt-card">
        <legend class="mt-card-title"><?= __('Dates') ?></legend>
        <div class="mt-segmented" role="group" aria-label="<?= __('Repeat') ?>">
          <input type="radio" class="mt-toggle-input" name="mode" id="mode-once" value="once" <?= $mode == 'once' ? 'checked' : '' ?>>
          <label class="mt-toggle" for="mode-once"><?= __('Once') ?></label>
          <input type="radio" class="mt-toggle-input" name="mode" id="mode-weekly" value="weekly" <?= $mode == 'once' ? '' : 'checked' ?>>
          <label class="mt-toggle" for="mode-weekly"><?= __('Weekly') ?></label>
        </div>

        <div data-mode="once">
          <?= $this->Form->control('date', ['type' => 'date', 'label' => __('Date'), 'default' => $today->format('Y-m-d')]) ?>
        </div>

        <div data-mode="weekly">
          <div class="mb-3">
            <div class="form-label"><?= __('Days') ?></div>
            <div class="mt-weekdays">
              <?php foreach ($dayNames as $iso => $dayName) : ?>
                <input type="checkbox" class="mt-toggle-input" name="weekdays[]" id="weekday-<?= $iso ?>" value="<?= $iso ?>" autocomplete="off" <?= in_array($iso, $weekdays) ? 'checked' : '' ?>>
                <label class="mt-toggle" for="weekday-<?= $iso ?>"><?= h($dayName) ?></label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="row g-2">
            <div class="col-sm-6"><?= $this->Form->control('from', ['type' => 'date', 'label' => __('From'), 'default' => $today->format('Y-m-d')]) ?></div>
            <div class="col-sm-6"><?= $this->Form->control('to', ['type' => 'date', 'label' => __('To'), 'default' => $seasonEnd->format('Y-m-d')]) ?></div>
          </div>
          <p class="mt-muted mt-preview-hint"
             data-preview-hint
             data-empty="<?= h(__('Choose at least one day.')) ?>"
             data-text="<?= h(__('Tap a date to exclude it (holidays, ...).')) ?>"></p>
          <div class="mt-date-chips" data-preview></div>
        </div>
      </fieldset>
    </div>
  </div>

  <div class="mt-submit">
    <?= $this->Form->button(__('Create'), [
        'class' => 'mt-btn mt-btn-primary',
        'data-label-none' => __('Create'),
        'data-label-one' => __('Create 1 meeting'),
        'data-label-many' => __('Create {0} meetings'),
    ]) ?>
  </div>
  <?= $this->Form->end() ?>
</div>
