<?php
/**
 * Member Files and Registration Element
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var array $files
 * @var array $config
 */

if (!(sizeof($files) > 0 || $member->RegExists($config['year']))) {
    return;
}
?>
<div class="row px-0 mx-0">
    <div class="col px-0 mx-0">
        <div class="members view content">
            <?php
            if ($member->RegExists($config['year'])) {
                echo $this->Html->link(__('Registration {0}-{1}', $config['year'], $config['year'] + 1), $member->GetRegUrl($config['year']), ['target' => '_blank', 'class' => 'badge bg-dark']);
            }
            ?>
            <?php
            foreach ($files as $file) {
                echo $this->Html->link($file['title'], $file['url'], ['target' => '_blank', 'class' => 'badge bg-dark']);
                echo " ";
            }
            ?>
        </div>
    </div>
</div>
<hr />
