<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ShopItem $shopItem
 * @var array $categoryOptions
 */
echo $this->element('ShopItems/form', ['shopItem' => $shopItem, 'categoryOptions' => $categoryOptions, 'legend' => __('Add Shop Item')]);
