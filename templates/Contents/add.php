<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Content $content
 */

?>
<?= $this->element('Contents/editor') ?>

<div class="row">
  <aside class="col-2">
    <div class="side-nav">
      <h4 class="heading"><?= __('Actions') ?></h4>
      <?= $this->Html->link(__('List'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
    </div>
  </aside>
  <div class="col-10">
    <div class="contents form content">
      <?= $this->Form->create($content) ?>
      <fieldset>
        <legend><?= __('Add Content') ?></legend>
        <?php
        echo $this->Form->control('text');
        echo '<br>';
        echo $this->Form->select('location', $content->getLocationList());
        echo $this->Form->control('url');
        echo $this->Form->control('team_id', ['options' => $teams, 'empty' => true]);
        echo $this->Form->control('sort');
        ?>
      </fieldset>
      <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-save']) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
