<?php

/**
 * Coaches of the meeting with their presence type (coach payment module)
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Meeting $meeting
 * @var array<int, array{member: \App\Model\Entity\Member, coachType: int|null, presence: \App\Model\Entity\CoachPresence|null}> $rows
 * @var array<int, \App\Model\Entity\CoachPresenceType> $types all the types of the site, by id
 */
use App\Model\Entity\TeamsCoach;
use Cake\Routing\Router;

if (empty($rows)) {
    return;
}
$coachTypes = TeamsCoach::types();
?>
<?= $this->Html->css('coach-payments') ?>
<?= $this->Html->script('coach-presences', ['defer' => true]) ?>
<section class="cp-coaches"
         data-url="<?= Router::url(['controller' => 'CoachPresences', 'action' => 'setType', $meeting->id]) ?>"
         data-csrf="<?= h($this->request->getAttribute('csrfToken')) ?>"
         data-error="<?= h(__('The presence of the coach could not be saved.')) ?>">
  <h3 class="cp-title"><?= __('Coaches') ?></h3>
  <?php if (!array_filter($types, fn ($type) => $type->active)) : ?>
    <p class="mt-muted"><?= __('No presence type is defined for this city.') ?></p>
  <?php endif; ?>
  <ul class="cp-coach-list">
    <?php foreach ($rows as $memberId => $row) : ?>
        <?php
        $presence = $row['presence'];
        $paid = $presence?->isPaid() ?? false;
        $current = $presence?->coach_presence_type_id;
        ?>
      <li class="cp-coach" data-member="<?= (int)$memberId ?>" data-type="<?= $current === null ? '' : (int)$current ?>">
        <span class="cp-coach-name">
            <?= h($row['member']->full_name) ?>
          <span class="mt-muted"><?= h($row['coachType'] === null ? __('Not coach of the team anymore') : $coachTypes[$row['coachType']] ?? '') ?></span>
        </span>
        <div class="cp-types" role="group" aria-label="<?= __('Presence type') ?>">
          <?php if ($paid) : ?>
            <span class="cp-type" aria-pressed="true"><?= h($types[$current]->label ?? '') ?></span>
            <span class="badge bg-success"><?= __('Paid') ?></span>
          <?php else : ?>
                <?php foreach ($types as $typeId => $type) : ?>
                    <?php if ($type->active || $typeId === $current) : ?>
              <button type="button" class="cp-type" data-type="<?= (int)$typeId ?>"
                      aria-pressed="<?= $typeId === $current ? 'true' : 'false' ?>"
                      title="<?= h($this->Number->currency((float)$type->amount, 'CHF')) ?>"><?= h($type->label) ?></button>
                    <?php endif; ?>
                <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
  <p class="mt-muted cp-hint"><?= __('Tap the selected type again to clear it.') ?></p>
  <div class="alert alert-danger cp-error" role="alert" hidden></div>
</section>
