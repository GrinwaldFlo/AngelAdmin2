<?php

/**
 * Shop page opened with "All locations" chosen in the menu
 * @var \App\View\AppView $this
 */
$this->Html->css('shop', ['block' => true]);
?>
<div class="content shop-choose-site">
    <span class="material-icons shop-choose-site-icon" aria-hidden="true">location_city</span>
    <h3><?= __('Choose a location') ?></h3>
    <p><?= __('The shop is managed by location: choose one in the location menu at the top of the page.') ?></p>
</div>
