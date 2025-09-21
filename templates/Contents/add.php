<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Content $content
 */

 $this->prepend('script', $this->Html->script(['/js/tinymce/tinymce.min.js']));
?>
<script>
  tinymce.init({
    selector: 'textarea',
    plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap emoticons',
    menubar: 'edit view insert format table help',
    toolbar: 'bold italic underline | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | insertfile image media template link',
    height: 400,
    license_key: 'gpl'
  });
</script>

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
      <?= $this->Form->button(__('Submit')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
