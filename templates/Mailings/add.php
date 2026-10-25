<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Mailing $mailing
 * @var array $teams active teams [id => name]
 * @var array $teamIds selected teams
 * @var array $placeholders [placeholder => label]
 */
$this->Html->css('mailings', ['block' => true]);
$this->Html->script('mailings', ['block' => true, 'defer' => true]);
?>
<div class="row">
  <aside class="col-md-3">
    <div class="side-nav">
      <h4 class="heading"><?= __('Actions') ?></h4>
      <?= $this->Html->link(__('Emails to teams'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
    </div>
  </aside>
  <div class="col-md-9">
    <div class="mailings form content">
      <?= $this->Form->create($mailing) ?>
      <fieldset>
        <legend><?= __('New email') ?></legend>
        <?= $this->Form->control('title', ['label' => __('Subject'), 'maxlength' => 100]) ?>
        <?= $this->Form->control('content', ['type' => 'textarea', 'label' => __('Content'), 'rows' => 12, 'class' => 'ml-content',
          'help' => __('HTML is allowed and line returns are kept. {0} is shown as a link with the text "{1}".', '{my_page}', __('My page'))]) ?>
        <div class="ml-placeholders">
          <span><?= __('Insert:') ?></span>
          <?php foreach ($placeholders as $placeholder => $label) : ?>
            <button type="button" class="btn btn-sm ml-placeholder" data-placeholder="<?= h($placeholder) ?>" title="<?= h($label) ?>"><?= h($placeholder) ?></button>
          <?php endforeach; ?>
        </div>
        <?= $this->Form->control('team_ids', ['type' => 'select', 'multiple' => 'checkbox', 'options' => $teams, 'value' => $teamIds, 'label' => __('Teams')]) ?>
      </fieldset>
      <p class="ml-muted"><?= __('The emails are prepared on the next page, where you can start sending them.') ?></p>
      <?= $this->Form->button(__('Next')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
