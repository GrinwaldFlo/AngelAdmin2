<?php

/**
 * @var \App\View\AppView $this
 * @var array<array{member: \App\Model\Entity\Member, bill: \App\Model\Entity\Bill|null}> $rows
 * @var int $memberStatus
 * @var int $teamId
 * @var array $teams
 * @var int $seasons
 * @var string $label
 */
use App\Controller\BillsController;

$this->Html->css('bills', ['block' => true]);
$this->Html->script('table-sort', ['block' => true]);

$query = $label !== '' ? ['?' => ['label' => $label]] : [];
$url = fn(int $team, int $season, int $status): array => ['action' => 'search', $team, $season, $status] + $query;
$filterLink = fn(string $title, array $link, bool $pressed): string => $this->Html->link(
    $title,
    $link,
    ['class' => 'btn btn-filter btn-sm' . ($pressed ? ' pressed' : '')],
);
$seasonLabels = [1 => __('This season'), 2 => __('Two seasons'), 3 => __('Three seasons'), 0 => __('All')];
// Status order of the sort: problems first
$statusRank = ['danger' => 1, 'missing' => 2, 'warning' => 3, 'info' => 4, 'success' => 5, 'dark' => 6];
$sortHeader = fn(string $title, string $type, bool $sorted = false): string => '<th data-sort-type="' . $type . '"'
    . ($sorted ? ' aria-sort="ascending"' : '') . '>'
    . '<button type="button" class="table-sort-btn">' . h($title) . '</button></th>';
$memberFilters = $this->my->memberFilterLinks(
    ['action' => 'search', $teamId, $seasons] + $query,
    $memberStatus,
    [1, 2],
);
?>
<div class="bills index content">
    <h3><?= __('Invoice search') ?></h3>
    <div class="btn-bar">
        <?= $memberFilters ?>
        <?php foreach (BillsController::SEARCH_SEASONS as $season) : ?>
            <?= $filterLink($seasonLabels[$season], $url($teamId, $season, $memberStatus), $season === $seasons) ?>
        <?php endforeach; ?>
    </div>
    <div class="btn-bar">
        <?php foreach ($teams as $key => $team) : ?>
            <?= $filterLink(
                (string)$team,
                $url($teamId == $key ? 0 : $key, $seasons, $memberStatus),
                $key == $teamId,
            ) ?>
        <?php endforeach; ?>
    </div>
    <?= $this->Form->create(null, [
        'type' => 'get',
        'url' => $url($teamId, $seasons, $memberStatus),
        'class' => 'bills-search-form',
    ]) ?>
    <?= $this->Form->control('label', [
        'label' => false,
        'value' => $label,
        'placeholder' => __('Invoice label'),
        'templates' => ['inputContainer' => '{{content}}'],
    ]) ?>
    <?= $this->Form->button(__('Search'), ['class' => 'btn btn-save']) ?>
    <?php if ($label !== '') : ?>
        <?= $this->Html->link(
            __('Clear'),
            ['action' => 'search', $teamId, $seasons, $memberStatus],
            ['class' => 'btn btn-nav'],
        ) ?>
    <?php endif; ?>
    <?= $this->Form->end() ?>

    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm bills-search-table js-sortable">
            <thead>
                <tr>
                    <?= $sortHeader(__('Member'), 'text', true) ?>
                    <?= $sortHeader(__x('Label on the invoice', 'Denomination'), 'text') ?>
                    <?= $sortHeader(__('Date'), 'date') ?>
                    <?= $sortHeader(__('Status'), 'number') ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) :
                    $member = $row['member'];
                    $bill = $row['bill'];
                    $rank = $bill === null ? $statusRank['missing'] : $statusRank[$bill->statusHtml] ?? 9; ?>
                    <tr<?= $bill === null ? ' class="bills-search-missing"' : '' ?>>
                        <td class="bills-search-member">
                            <?= $this->Html->link(
                                (string)$member->FullName,
                                ['controller' => 'Members', 'action' => 'view', $member->id],
                            ) ?>
                        </td>
                        <?php if ($bill === null) : ?>
                            <td class="bills-search-label" data-sort-value="">&mdash;</td>
                            <td class="bills-search-date"></td>
                            <td class="bills-search-status" data-sort-value="<?= $rank ?>">
                                <span class="badge bg-danger"><?= __('No invoice') ?></span>
                            </td>
                        <?php else : ?>
                            <td class="bills-search-label">
                                <?= $this->Html->link((string)$bill->label, ['action' => 'view', $bill->id]) ?>
                            </td>
                            <?php if ($bill->created) : ?>
                                <td class="bills-search-date"
                                    data-sort-value="<?= h($bill->created->format('Y-m-d H:i:s')) ?>">
                                    <?= h($bill->created->i18nFormat('dd.MM.yyyy')) ?>
                                </td>
                            <?php else : ?>
                                <td class="bills-search-date"></td>
                            <?php endif; ?>
                            <td class="bills-search-status" data-sort-value="<?= $rank ?>">
                                <span class="badge bg-<?= h($bill->statusHtml) ?>"><?= h($bill->statusString) ?></span>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($rows === []) : ?>
        <p class="bills-search-empty"><?= __('Nothing to show.') ?></p>
    <?php endif; ?>
</div>
