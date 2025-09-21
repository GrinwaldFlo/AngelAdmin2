<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team $team
 */
?>
<div class="row">
  <div class="col">
    <div class="teams view content">
      <h3><?= h($team->name) ?></h3>
      <div class="icon float-end">
        <?php if ($curRole->MemberEditAll) : ?>
          <?= $this->Html->link('<i class="gg-pen"></i>', ['action' => 'edit', $team->id], ['escape' => false]) ?>
        <?php endif; ?>
        <?= $curRole->MemberViewAll ? $this->backButtonCtrl('Teams', 'index') : $this->BackButton('/') ?>
      </div>
      <table class="table table-striped table-hover table-sm">
        <tr>
          <th><?= __('Membership fee') ?></th>
          <td><?= $this->Number->currency($team->membership_fee, 'CHF') ?></td>
        </tr>
        <tr>
          <th><?= __('Active') ?></th>
          <td><?= $team->active ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= __('Description') ?></th>
          <td><?= h($team->description); ?></td>
        </tr>
      </table>
      <canvas id="myChart" width="100" height="40"></canvas>
      <script>
        var timeFormat = 'DD/MM/YY HH:mm';
        var ctx = document.getElementById('myChart');
        var color = Chart.helpers.color;
        var myChart = new Chart(ctx, {
        type: 'line',
                data: {

                datasets: [{
                label: 'Presences',
                        backgroundColor: color(window.chartColors.red).alpha(0.5).rgbString(),
                        borderColor: window.chartColors.red,
                        fill: false,
                        steppedLine : true,
                        data: [
<?php foreach ($team->meetings as $meeting) : ?>
  <?php if ($meeting->present > 0) : ?>
                            {t:'<?= h($meeting->meeting_date) ?>', y:<?= h($meeting->present + $meeting->late) ?>},
  <?php endif; ?>
<?php endforeach; ?>
                        ]
                }]
                },
                options: {
                title: {text: 'Chart.js Time Scale'},
                        scales: {
                        xAxes: [{
                        type: 'time',
                                distribution: 'linear',
                                time: {
                                unit: 'week',
                                        parser: timeFormat,
                                        round: 'day',
                                        tooltipFormat: 'll HH:mm',
                                        displayFormats: {
                                        quarter: 'MMM YYYY'
                                        }
                                },
                                scaleLabel: {
                                display: false,
                                        labelString: 'Date'
                                }
                        }],
                                yAxes: [{
                                scaleLabel: {
                                display: false,
                                        labelString: 'value'
                                }
                                }]
                        }
                }
        });

      </script>
      <div class="row g-4 related">
        <div class="col-12 col-md-6">
          <h4><?= __('Members') ?></h4>
          <?php if (!empty($membersIn)) : ?>
            <div class="d-flex flex-wrap gap-2">
            <?php foreach ($membersIn as $member) : ?>
              <div class="d-flex align-items-center mb-2">
                <?= $this->Html->link($member->FullName, ['controller' => 'Members', 'action' => 'view', $member->id], ['class' => 'me-1']) ?>
                <?= $this->Html->link('-', ['action' => 'removeMember', $team->id, $member->id], [
                  'escape' => false,
                  'class' => 'btn btn-danger btn-sm',
                  'title' => __('Remove member')
                ]) ?>
              </div>
            <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="col-12 col-md-6">
          <h4><?= __('Add members') ?></h4>
          <?php if (!empty($membersOut)) : ?>
            <div class="d-flex flex-wrap gap-2">
            <?php foreach ($membersOut as $member) : ?>
              <div class="d-flex align-items-center mb-2">
                <?= $this->Html->link($member->FullName." (".$member->Age.")", ['controller' => 'Members', 'action' => 'view', $member->id], ['class' => 'me-1']) ?>
                <?= $this->Html->link('+', ['action' => 'addMember', $team->id, $member->id], [
                  'escape' => false,
                  'class' => 'btn btn-success btn-sm',
                  'title' => __('Add member')
                ]) ?>
              </div>
            <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="related mt-4">
        <h4><?= __('Meetings') ?></h4>
        <?php if (!empty($team->meetings)) : ?>
          <div class="table-responsive">
          <table class="table table-striped table-hover table-sm">
            <tr>
              <th><?= __('Meeting date') ?></th>
              <th><?= __('Name') ?></th>
              <th><?= __('Pre/Abs/Exc/Late') ?></th>
              <th><?= __('Meeting date') ?></th>
              <th><?= __('Name') ?></th>
              <th><?= __('Pre/Abs/Exc/Late') ?></th>
            </tr>
            <?php $i = 0; ?>
            <tr>
              <?php foreach ($team->meetings as $meeting) : ?>

                <td><?= h($meeting->meeting_date) ?></td>
                <td><?= $this->Html->link(h($meeting->name), ['controller' => 'Meetings', 'action' => 'view', $meeting->id]) ?></td>
                <td><?= h($meeting->PresencesStr) ?></td>
                <?php
                if ($i == 0)
                {
                  $i = 1;
                }
                else
                {
                  $i = 0;
                  echo "</tr><tr>";
                }
                ?> 
              <?php endforeach; ?>
            <tr>
          </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
