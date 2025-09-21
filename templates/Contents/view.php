<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Content $content
 */
?>
<div class="row">
    <aside class="col-2">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Content'), ['action' => 'edit', $content->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Content'), ['action' => 'delete', $content->id], ['confirm' => __('Are you sure you want to delete # {0}?', $content->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Content'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="col-10">
        <div class="contents view content">
            <h3><?= h($content->id) ?></h3>
            <table class="table table-striped table-hover table-sm">
                <tr>
                    <th><?= __('Url') ?></th>
                    <td><?= h($content->url) ?></td>
                </tr>
                <tr>
                    <th><?= __('Team') ?></th>
                    <td><?= $content->has('team') && $content->team_id != 0 ? $this->Html->link($content->team->name, ['controller' => 'Teams', 'action' => 'view', $content->team->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($content->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Location') ?></th>
                    <td><?= h($content->locationStr) ?></td>
                </tr>
            </table>
            <div class="text">
                <blockquote>
                    <?= $content->text; ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
