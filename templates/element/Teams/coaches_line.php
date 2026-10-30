<?php
/**
 * One line with the coaches then the help-coaches of a team, nothing if the team has none
 *
 * @var \App\View\AppView $this
 * @var array<int, \App\Model\Entity\Member[]> $teamCoaches members by type (TeamsCoachesTable::coachesOf())
 */
use App\Model\Entity\TeamsCoach;

$parts = [];
foreach (TeamsCoach::types() as $type => $label) {
    $names = array_map(fn ($member) => h($member->fullName), $teamCoaches[$type] ?? []);
    if (!empty($names)) {
        $parts[] = '<span class="team-coaches-label">' . h($label) . ':</span> ' . implode(', ', $names);
    }
}
if (empty($parts)) {
    return;
}
$this->Html->css('teams', ['block' => true]);
?>
<div class="team-coaches"><?= implode('<span class="team-coaches-sep">·</span>', $parts) ?></div>
