<?php
/**
 * Attributes of an item as small badges
 * @var \App\View\AppView $this
 * @var string[] $attributes
 */
?>
<?php if ($attributes === []) : ?>
    <span class="text-muted">-</span>
<?php else : ?>
    <span class="shop-attributes">
        <?php foreach ($attributes as $attribute) : ?>
            <span class="badge bg-light text-dark shop-attribute"><?= h($attribute) ?></span>
        <?php endforeach; ?>
    </span>
<?php endif; ?>
