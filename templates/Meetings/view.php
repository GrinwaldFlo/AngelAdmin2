<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Meeting $meeting
 * 
 *                   <a id="Del<?= h($member['id']) ?>" class="tag is-delete"></a>

 */
use Cake\Routing\Router;
function cmp_obj($a, $b)
{
  return $a['name'] > $b['name'];
}

usort($members, "cmp_obj");
//debug($members);
//$this->Form->postLink(__('Delete'), ['controller' => 'Presences', 'action' => 'delete', $presences->id], ['confirm' => __('Are you sure you want to delete # {0}?', $presences->id)])
?>
<div class="row px-0 mx-0">
  <div class="col px-0 mx-0">
    <div class="meetings view content">
      <div class="icon float-end">
        <?= $curRole->MemberEditAll ? $this->Html->link('<i class="gg-pen"></i>', ['action' => 'edit', $meeting->id], ['escape' => false]) : '' ?>
        <?= $this->Form->postLink('<i class="gg-erase"></i>', ['action' => 'delete', $meeting->id], ['confirm' => __('Are you sure you want to delete # {0}?', $meeting->id), 'escape' => false]) ?>
        <?= $this->backButtonCtrl('Meetings', 'index') ?>
      </div>
      <h3><?= h($meeting->name) . ' / ' . ($meeting->has('team') ? $this->Html->link($meeting->team->name, ['controller' => 'Teams', 'action' => 'view', $meeting->team->id]) : '') . ' - ' . h($meeting->meeting_date->i18nFormat($config['dateEvent'])) ?></h3>
      <div class="related">
        <h4><?= __('Attendance') ?></h4>
        <div class="row">
          <?php foreach ($members as $member) : ?>
            <div class="btn-team" role="team" aria-label="Presences">            
              <button 
                type="button" 
                class="btn btn-secondary btn-<?= $this->my->PresenceStateToHtml($member['state']) ?>"
                id = "Tag<?= h($member['id']) ?>" 
                style="width:150px;">
                <?= h($member['name']) ?></button>
              <button type="button" id="Pres<?= h($member['id']) ?>" class="btn btn-secondary btn-success">Pres.</button>
              <button type="button" id="Abs<?= h($member['id']) ?>" class="btn btn-secondary btn-danger">Abs.</button>
              <button type="button" id="Exc<?= h($member['id']) ?>" class="btn btn-secondary btn-primary">Exc.</button>
              <button type="button" id="Late<?= h($member['id']) ?>" class="btn btn-secondary btn-warning">Late.</button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
<?php foreach ($members as $member) : ?>
      $("#Pres<?= h($member['id']) ?>").click(function ()
      {
        $("#Tag<?= h($member['id']) ?>").attr("class", "btn btn-secondary btn-<?= $this->my->PresenceStateToHtml(1) ?>");
        $.ajax("<?= Router::url(array('controller' => 'presences', 'action' => 'setPresence', $meeting->id, $member['id'], 1), true) ?>");
      });
      $("#Abs<?= h($member['id']) ?>").click(function ()
      {
        $("#Tag<?= h($member['id']) ?>").attr("class", "btn btn-secondary btn-<?= $this->my->PresenceStateToHtml(0) ?>");
        $.ajax("<?= Router::url(array('controller' => 'presences', 'action' => 'setPresence', $meeting->id, $member['id'], 0), true) ?>");
      });
      $("#Exc<?= h($member['id']) ?>").click(function ()
      {
        $("#Tag<?= h($member['id']) ?>").attr("class", "btn btn-secondary btn-<?= $this->my->PresenceStateToHtml(2) ?>");
        $.ajax("<?= Router::url(array('controller' => 'presences', 'action' => 'setPresence', $meeting->id, $member['id'], 2), true) ?>");
      });
      $("#Late<?= h($member['id']) ?>").click(function ()
      {
        $("#Tag<?= h($member['id']) ?>").attr("class", "btn btn-secondary btn-<?= $this->my->PresenceStateToHtml(3) ?>");
        $.ajax("<?= Router::url(array('controller' => 'presences', 'action' => 'setPresence', $meeting->id, $member['id'], 3), true) ?>");
      });
      $("#Del<?= h($member['id']) ?>").click(function ()
      {
        $("#Tag<?= h($member['id']) ?>").attr("class", "btn btn-secondary btn-<?= $this->my->PresenceStateToHtml(-1) ?>");
        $.ajax("<?= Router::url(array('controller' => 'presences', 'action' => 'setPresence', $meeting->id, $member['id'], -1), true) ?>");
      });
<?php endforeach; ?>
  });

</script>
