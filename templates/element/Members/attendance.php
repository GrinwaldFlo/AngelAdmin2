<?php
/**
 * Member Attendance Section Element
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var array $attendance
 */

if (empty($member->presences) || sizeof($member->presences) == 0) {
    return;
}

function cmp_obj($a, $b): int
{
    return ($a->meeting->meeting_date <=> $b->meeting->meeting_date) * -1;
}

usort($member->presences, "cmp_obj");
?>
<div class="row px-0 mx-0">
    <div class="col px-0 mx-0">
        <div class="members view content" style="min-height:300px">
            <h4><?= __('Attendance') ?></h4>
            <ul class="nav nav-tabs" id="attendanceTab" role="tablist">
                <?php
                // Sort attendance keys in descending order (newest first)
                $sortedAttendance = $attendance;
                krsort($sortedAttendance);
                
                $firstTab = true;
                foreach ($sortedAttendance as $key => $value):
                    $id = "Y" . str_replace("-", "", $key);
                    ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link<?= $firstTab ? ' active' : '' ?>" 
                                id="<?= $id ?>-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#<?= $id ?>" 
                                type="button" 
                                role="tab"
                                aria-controls="<?= $id ?>" 
                                aria-selected="<?= $firstTab ? 'true' : 'false' ?>">
                            <?= h($key) ?>
                        </button>
                    </li>
                    <?php $firstTab = false; ?>
                <?php endforeach; ?>
            </ul>
            <div class="tab-content mt-3" id="attendanceTabContent">
                <?php
                $firstContent = true;
                foreach ($sortedAttendance as $key => $items):
                    $id = "Y" . str_replace("-", "", $key);
                    ?>
                    <div class="tab-pane fade<?= $firstContent ? ' show active' : '' ?>" 
                         id="<?= $id ?>" 
                         role="tabpanel" 
                         aria-labelledby="<?= $id ?>-tab"
                         tabindex="0">
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($items as $itemKey => $item): ?>
                                <span class="badge bg-<?= h($item->statusHtml) ?>" 
                                      data-bs-toggle="tooltip" 
                                      data-bs-placement="top"
                                      data-bs-title="<?= h($item->meeting->name) ?>">
                                    <?= $item->meeting->meeting_date->i18nFormat('dd MMM YYYY') ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $firstContent = false; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap 5 tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
