<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Configuration $configuration
 */
?>
<div class="row">
    <aside class="col-4">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Configuration'), ['action' => 'edit', $configuration->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Configurations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="col-8">
        <div class="configurations view content">
            <h3><?= h($configuration->id) ?></h3>
            <table class="table table-striped table-hover table-sm">
                <tr>
                    <th><?= __x('noun','Label') ?></th>
                    <td><?= h($configuration->label) ?></td>
                </tr>
                <tr>
                    <th><?= __('Value') ?></th>
                    <td><?= h($configuration->value) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($configuration->id) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
