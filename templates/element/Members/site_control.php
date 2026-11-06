<?php

/**
 * City (location) of the member: a list when the club has several locations, the only one otherwise
 *
 * @var \App\View\AppView $this
 * @var array<int, string> $sites id => city
 * @var int|null $default preselected city of a new member
 * @var bool $changeWarning tell that the teams of the other locations are left
 */
if (count($sites) > 1) {
    echo $this->Form->control('site_id', [
        'options' => $sites,
        'label' => __('Location'),
        'empty' => __('Choose a location'),
        'required' => true,
        'default' => $default ?? null,
        'help' => empty($changeWarning) ? null : __('Changing the location removes the member from its teams: edit the member again to choose the teams of the new location.'),
    ]);
} else {
    echo $this->Form->control('site_id', ['type' => 'hidden', 'value' => array_key_first($sites)]);
}
