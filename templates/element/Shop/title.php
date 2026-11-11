<?php
/**
 * Title of a shop page with its city
 * @var \App\View\AppView $this
 * @var string $title
 * @var \App\Model\Entity\Site|null $shopSite
 * @var int|null $count number of rows, null = not shown
 */
?>
<div class="shop-title">
    <h3><?= h($title) ?></h3>
    <?php if (isset($count)) : ?>
        <span class="mo-count">(<?= $this->Number->format($count) ?>)</span>
    <?php endif; ?>
    <?php if (!empty($shopSite)) : ?>
        <span class="badge bg-secondary shop-site"><?= h($shopSite->city) ?></span>
    <?php endif; ?>
</div>
