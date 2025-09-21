<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */

use App\Controller\PrefResult;
use Symfony\Component\String\Inflector\SpanishInflector;
$teamId = $pref->teamId;
$memberFilter = $pref->memberFilter;
$siteId = $pref->siteId;
$teams = $pref->teams;


?>
<div class="row p-0 m-0 mb-1">
    <div class="col p-0 m-0">
        <div class="members view content">
            <?= $this->my->siteLinks($pref, 'index') ?>
      |
            <?= $this->Html->link(__('Active'), ['action' => 'index', $teamId, $memberFilter == 1 ? 0 : 1, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 1 ? ' pressed' : '')]) ?>
            <?= $this->Html->link(__('Alumni'), ['action' => 'index', $teamId, $memberFilter == 2 ? 0 : 2, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 2 ? ' pressed' : '')]) ?>
            <?= $this->Html->link(__('Membership not paid'), ['action' => 'index', $teamId, $memberFilter == 3 ? 0 : 3, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 3 ? ' pressed' : '')]) ?>
            <?= $this->Html->link(__('Non inscrit'), ['action' => 'index', $teamId, $memberFilter == 4 ? 0 : 4, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 4 ? ' pressed' : '')]) ?>
            <?= $this->Html->link(__('No team'), ['action' => 'index', $teamId, $memberFilter == 5 ? 0 : 5, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 5 ? ' pressed' : '')]) ?>
            <br />
            <?= $this->my->teamLinks($pref, 'index') ?>
        </div>
    </div>
</div>

<div class="row p-0 m-0">
    <div class="col p-0 m-0">
        <div class="members view content">
            <?php if ($curRole->MemberEditAll): ?>
                <?= $this->Html->link('', ['action' => 'add'], ['class' => 'float-end gg-add-r']) ?>
            <?php endif; ?>
            <h3 class="d-inline"><?= __('Members') ?></h3>
            <div class="d-inline"><?= '(' . count($members) . ')' ?></div>
            <table class="table table-striped table-hover table-sm" id="membersTable">
                <thead>
                    <tr>
                        <th> ...
                        </th>
                        <th>
                            <?= $this->Paginator->sort('first_name', __("First name")) ?><br />
                            <input type="text" onkeyup="filterTable(2)" placeholder="Filter..." class="form-control form-control-sm"
                                onkeydown="if(event.key==='Escape'){this.value='';filterTable(2);}" />
                        </th>
                        <th>
                            <?= $this->Paginator->sort('last_name', __("Last name")) ?><br />
                            <input type="text" onkeyup="filterTable(3)" placeholder="Filter..." class="form-control form-control-sm"
                                onkeydown="if(event.key==='Escape'){this.value='';filterTable(3);}" />
                        </th>
                        <th>
                            <?= __("Team") ?><br />
                            <input type="text" onkeyup="filterTable(4)" placeholder="Filter..." class="form-control form-control-sm"
                                onkeydown="if(event.key==='Escape'){this.value='';filterTable(4);}" />
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td>
                                <?php
                                if ($member->late_bills_sum > 300) {
                                    $this->my->echo('span', '$$', ["style" => "color:red", "title" => $member->late_bills_sum . " CHF"]);
                                } elseif ($member->late_bills_sum > 150) {
                                    $this->my->echo('span', '$', ["style" => "color:orange", "title" => $member->late_bills_sum . " CHF"]);
                                } else {
                                }

                                if ($member->registered) {
                                    echo $this->my->symbol('check_circle', color: "green");
                                }

                                ?>
                            </td>
                            <td><?= $this->Html->link($member->first_name, ['action' => 'view', $member->id]) ?></td>
                            <td><?= $this->Html->link($member->last_name, ['action' => 'view', $member->id]) ?></td>
                            <td><?= h($member->TeamString) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <script>
                function filterTable(colIndex) {
                  var table = document.getElementById("membersTable");
                  var inputs = table.querySelectorAll("thead input");
                  var filterValues = Array.from(inputs).map(input => input.value.trim().toUpperCase());
                  var rows = table.tBodies[0].rows;
                  for (var i = 0; i < rows.length; i++) {
                    var show = true;
                    for (var j = 0; j < filterValues.length; j++) {
                      var cell = rows[i].cells[j + 1]; // Skip the first column (icons)
                      if (filterValues[j] && cell.textContent.toUpperCase().indexOf(filterValues[j]) === -1) {
                        show = false;
                        break;
                      }
                    }
                    rows[i].style.display = show ? "" : "none";
                  }
                }
            </script>
        </div>
    </div>
</div>
<div class="paginator">
    <ul class="pagination">
        <?= $this->Paginator->first('<< ' . __('first')) ?>
        <?= $this->Paginator->prev('< ' . __('previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('next') . ' >') ?>
        <?= $this->Paginator->last(__('last') . ' >>') ?>
    </ul>
    <p>
        <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
    </p>
</div>
<?=
    ""//$this->Paginator->limitControl([25 => 25, 50 => 50, 100 => 100, 1000 => 1000]); ?>

