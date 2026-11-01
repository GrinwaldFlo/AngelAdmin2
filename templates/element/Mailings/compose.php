<?php
/**
 * Subject and content of a mailing, with the buttons inserting the placeholders
 * @var \App\View\AppView $this
 * @var array $placeholders [placeholder => label]
 */
?>
<?= $this->Form->control('title', ['label' => __('Subject'), 'maxlength' => 100]) ?>
<?= $this->Form->control('content', ['type' => 'textarea', 'label' => __('Content'), 'rows' => 12, 'class' => 'ml-content',
  'help' => __('HTML is allowed and line returns are kept. {0} is shown as a link with the text "{1}".', '{my_page}', __('My page'))]) ?>
<div class="ml-placeholders">
  <span><?= __('Insert:') ?></span>
  <?php foreach ($placeholders as $placeholder => $label) : ?>
    <button type="button" class="btn btn-sm ml-placeholder" data-placeholder="<?= h($placeholder) ?>" title="<?= h($label) ?>"><?= h($placeholder) ?></button>
  <?php endforeach; ?>
</div>
