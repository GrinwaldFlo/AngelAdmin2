<?php
/**
 * Member Bills Section Element
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var bool $allowViewBills
 * @var object $curRole
 */

if (!$allowViewBills) {
    return;
}
?>
<div class="row px-0 mx-0">
    <div class="col px-0 mx-0">
        <div class="members view content">
            <?php if ($curRole->BillEditAll): ?>
                <?= $this->Html->link('', ['controller' => 'Bills', 'action' => 'add', $member->id], ['class' => 'float-end gg-add-r']) ?>
            <?php endif; ?>
            <div class="d-flex align-items-center mb-3 gap-3">
                <h4 class="mb-0"><?= __('Invoices') ?></h4>
                
                <!-- Invoice Filter Buttons -->
                <div class="btn-group" role="group" aria-label="<?= __('Invoice filters') ?>">
                    <button class="btn btn-sm btn-primary bill-filter-btn" data-filter="open" type="button">
                        <?= __('Open') ?>
                    </button>
                    <button class="btn btn-sm btn-outline-success bill-filter-btn" data-filter="paid" type="button" style="color: #198754;">
                        <?= __('Paid') ?>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary bill-filter-btn" data-filter="all" type="button" style="color: #6c757d;">
                        <?= __('All') ?>
                    </button>
                </div>
            </div>
            
            <div id="bills-content">
                <?php if (!empty($member->bills)): ?>
                    <!-- Desktop view -->
                    <div class="d-none d-md-block">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><?= __('Ref.') ?></th>
                                    <th><?= __x('Label on the invoice', 'Denomination') ?></th>
                                    <th><?= __('Amount') ?></th>
                                    <th><?= __('Remin.') ?></th>
                                    <th><?= __('Status') ?></th>
                                    <th><?= __('Due date') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody id="bills-table-body">
                                <?php
                                // Sort bills: Unpaid first, then by due_date ascending
                                $sortedBills = $member->bills;
                                usort($sortedBills, function($a, $b) {
                                    if ($a->paid !== $b->paid) {
                                        return $a->paid <=> $b->paid;
                                    }
                                    return $a->due_date <=> $b->due_date;
                                });
                                
                                foreach ($sortedBills as $bill): 
                                    $billClasses = [];
                                    if ($bill->paid && !$bill->canceled) $billClasses[] = 'bill-paid';
                                    if (!$bill->paid && !$bill->canceled) $billClasses[] = 'bill-open';
                                    if ($bill->canceled) $billClasses[] = 'bill-canceled';
                                    ?>
                                    <tr class="bill-row <?= implode(' ', $billClasses) ?>">
                                        <td><?= $curRole->BillEditAll ? $this->Html->link(h($bill->Reference), ['controller' => 'Bills', 'action' => 'view', $bill->id]) : h($bill->Reference) ?></td>
                                        <td><?= h($bill->label) ?></td>
                                        <td><?= $this->Number->currency($bill->amount, 'CHF') ?></td>
                                        <td><?= h($bill->reminder) ?></td>
                                        <td><span class="badge bg-<?= h($bill->statusHtml) ?>"><?= h($bill->statusString) ?></span></td>
                                        <td><?= h($bill->due_date) ?></td>
                                        <td class="actions">
                                            <div class="icon" style="width: 90px">
                                                <?= ($curRole->BillEditAll && !$bill->paid) || $curRole->Admin ? $this->Html->link('<i class="gg-pen"></i>', ['controller' => 'Bills', 'action' => 'edit', $bill->id], ['escape' => false]) : '' ?>
                                                <?= $this->Html->link('<i class="gg-file-document"></i>', $bill->BillUrl, ['target' => '_blank', 'escape' => false, 'title' => __('Download PDF')]) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile view -->
                    <div class="d-md-none" id="bills-mobile-list">
                        <?php foreach ($sortedBills as $bill): 
                            $billClasses = [];
                            if ($bill->paid && !$bill->canceled) $billClasses[] = 'bill-paid';
                            if (!$bill->paid && !$bill->canceled) $billClasses[] = 'bill-open';
                            if ($bill->canceled) $billClasses[] = 'bill-canceled';
                            ?>
                            <div class="card mb-3 bill-row <?= implode(' ', $billClasses) ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">
                                            <?= $curRole->BillEditAll ?
                                                ('#' . $this->Html->link(h($bill->Reference), ['controller' => 'Bills', 'action' => 'view', $bill->id]) . ($bill->reminder > 0 ? ' (' . __('Remin.') . ' ' . h($bill->reminder) . ')' : ''))
                                                :
                                                '#' . h($bill->Reference)
                                                ?>
                                        </h6>
                                        <span class="badge bg-<?= h($bill->statusHtml) ?>"><?= h($bill->statusString) ?></span>
                                        <div class="icon" style="width: 90px">
                                            <?= ($curRole->BillEditAll && !$bill->paid) || $curRole->Admin ? $this->Html->link('<i class="gg-pen"></i>', ['controller' => 'Bills', 'action' => 'edit', $bill->id], ['escape' => false]) : '' ?>
                                            <?= $this->Html->link('<i class="gg-file-document"></i>', $bill->BillUrl, ['target' => '_blank', 'escape' => false, 'title' => __('Download PDF')]) ?>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-12">
                                            <small class="text-muted"><?= __x('Label on the invoice', 'Denomination') ?>:</small><br />
                                            <span><?= h($bill->label) ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            <small class="text-muted"><?= __('Amount') ?>:</small><br />
                                            <strong class="text-primary"><?= $this->Number->currency($bill->amount, 'CHF') ?></strong>
                                        </div>
                                        <div>
                                            <small class="text-muted"><?= __('Due date') ?>:</small><br />
                                            <span><?= h($bill->due_date) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- No results message (hidden by default) -->
                    <div id="no-bills-message" class="alert alert-info" role="alert" style="display: none;">
                        <span id="no-bills-text"></span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <?= __('No invoices found.') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<hr />

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.bill-filter-btn');
    const billRows = document.querySelectorAll('.bill-row');
    const noBillsMessage = document.getElementById('no-bills-message');
    const noBillsText = document.getElementById('no-bills-text');
    
    // Messages for different filters
    const messages = {
        'open': '<?= __('No open invoices found.') ?>',
        'paid': '<?= __('No paid invoices found.') ?>',
        'all': '<?= __('No invoices found.') ?>'
    };

    function filterBills(filter) {
        let visibleCount = 0;
        
        billRows.forEach(function(row) {
            let shouldShow = false;
            
            switch(filter) {
                case 'open':
                    shouldShow = row.classList.contains('bill-open');
                    break;
                case 'paid':
                    shouldShow = row.classList.contains('bill-paid');
                    break;
                case 'all':
                    shouldShow = true;
                    break;
            }
            
            if (shouldShow) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0 && billRows.length > 0) {
            noBillsText.textContent = messages[filter];
            noBillsMessage.style.display = 'block';
        } else {
            noBillsMessage.style.display = 'none';
        }
    }

    function updateButtonStyles(activeFilter) {
        filterButtons.forEach(function(btn) {
            const filter = btn.getAttribute('data-filter');
            btn.className = 'btn btn-sm bill-filter-btn';
            
            if (filter === activeFilter) {
                switch(filter) {
                    case 'open':
                        btn.classList.add('btn-primary');
                        btn.style.color = '';  // Remove custom color for active state
                        break;
                    case 'paid':
                        btn.classList.add('btn-success');
                        btn.style.color = '';  // Remove custom color for active state
                        break;
                    case 'all':
                        btn.classList.add('btn-secondary');
                        btn.style.color = '';  // Remove custom color for active state
                        break;
                }
            } else {
                switch(filter) {
                    case 'open':
                        btn.classList.add('btn-outline-primary');
                        btn.style.color = '#0d6efd';  // Bootstrap primary color
                        break;
                    case 'paid':
                        btn.classList.add('btn-outline-success');
                        btn.style.color = '#198754';  // Bootstrap success color
                        break;
                    case 'all':
                        btn.classList.add('btn-outline-secondary');
                        btn.style.color = '#6c757d';  // Bootstrap secondary color
                        break;
                }
            }
        });
    }

    // Add click event listeners to filter buttons
    filterButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const filter = this.getAttribute('data-filter');
            
            // Update button styles
            updateButtonStyles(filter);
            
            // Filter bills
            filterBills(filter);
            
            // Store current filter in sessionStorage to persist during page session
            sessionStorage.setItem('billFilter', filter);
        });
    });
    
    // Initialize with default filter (open) or restore from sessionStorage
    const savedFilter = sessionStorage.getItem('billFilter') || 'open';
    updateButtonStyles(savedFilter);
    filterBills(savedFilter);
});
</script>
