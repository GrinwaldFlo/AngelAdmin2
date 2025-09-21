<?php

// Set default values for the date inputs
$curDay = new DateTime();
$curDay->setTime(0, 0, 0, 0);
$finalDay = new DateTime();
$finalDay->add(new DateInterval('P300D'));

?>
<div class="row">
  <div class="col">
    <div class="meetings form content">
      <div class="tags float-end" style="width: 100px">
        <?= $this->backButtonCtrl('Meetings', 'index') ?>
      </div>
      <?= $this->Form->create($meeting) ?>
      <fieldset>
        <legend><?= __('Add Meeting') ?></legend>
        <div>
          <label for="startDate"><?= __('Start Date') ?>:</label>
          <input type="date" id="startDate" value="<?= $curDay->format('Y-m-d') ?>">
          <label for="endDate" style="margin-left:10px;"><?= __('End Date') ?>:</label>
          <input type="date" id="endDate" value="<?= $finalDay->format('Y-m-d') ?>">
        </div>
        <div style="margin:10px 0;">
          <?php
            $days = [__('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun')];
            foreach ($days as $i => $day) {
              echo "<label style='margin-right:8px;'><input type='checkbox' class='weekday' value='$i' > $day</label>";
            }
          ?>
        </div>
        <div id="dateCheckboxes"></div>
        <?php
        echo $this->Form->control('meeting_date', ['type' => 'time', 'step' => 60 * 15, 'label' => 'Time']);
        echo $this->Form->control('team_id', ['options' => $teams]);
        echo $this->Form->control('name');
        echo $this->Form->control('big_event');
        echo $this->Form->control('url');
        echo $this->Form->control('doodle');
        ?>
      </fieldset>
      <?= $this->Form->button(__('Submit')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
<script>
function getSelectedWeekdays() {
  return Array.from(document.querySelectorAll('.weekday:checked')).map(cb => parseInt(cb.value));
}

function renderDateCheckboxes() {
  const start = document.getElementById('startDate').value;
  const end = document.getElementById('endDate').value;
  const weekdays = getSelectedWeekdays();
  const container = document.getElementById('dateCheckboxes');
  container.innerHTML = '';
  if (!start || !end) return;
  let cur = new Date(start);
  const final = new Date(end);
  let html = '';
  while (cur <= final) {
    const day = cur.getDay(); // 0=Sun, 1=Mon, ..., 6=Sat
    // Map JS getDay to our weekday index (Mon=0,...,Sun=6)
    const weekdayIndex = (day + 6) % 7;
    if (weekdays.includes(weekdayIndex)) {
      const ts = Math.floor(cur.getTime() / 1000);
      const label = cur.toLocaleDateString(undefined, { weekday: 'short', day: '2-digit', month: 'short' });
      html += `
        <div class="mb-3 form-check checkbox">
          <input type="hidden" name="Date[${ts}]" value="0">
          <input type="checkbox" name="Date[${ts}]" value="1" id="date-${ts}" class="form-check-input" checked>
          <label class="form-check-label" for="date-${ts}">${label}</label>
        </div>
      `;
    }
    cur.setDate(cur.getDate() + 1);
  }
  container.innerHTML = html;
}

// Initial render
document.addEventListener('DOMContentLoaded', function() {
  renderDateCheckboxes();
  document.getElementById('startDate').addEventListener('change', renderDateCheckboxes);
  document.getElementById('endDate').addEventListener('change', renderDateCheckboxes);
  document.querySelectorAll('.weekday').forEach(cb => cb.addEventListener('change', renderDateCheckboxes));
});
</script>
