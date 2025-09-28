<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 */
function cmp_obj($a, $b): int
{
    return ($a->meeting->meeting_date <=> $b->meeting->meeting_date) * -1;
}

if (isset($curUser)) {
    $allowEdit = $curRole->MemberEditAll || ($curRole->MemberEditOwn && $member->id == $curUser->member_id);
    $allowQuit = $curRole->MemberEditAll || ($curRole->MemberEditOwn && $member->id == $curUser->member_id && $config['allowResiliation']);
    $allowViewBills = $curRole->BillViewAll || $member->id == $curUser->member_id;
    $isOwn = ($curRole->MemberEditOwn && $member->id == $curUser->member_id);

    $imgUrl = $member->ImgExists ? $member->GetImgUrl(500) . '?' . rand() : '/img/cheerPlaceholder.jpg';
    $imgIdUrl = $member->ImgIdExists ? $member->GetImgIdUrl(500) . '?' . rand() : '/img/carteId.jpg';
} else {
    $allowEdit = false;
    $allowQuit = false;
    $allowViewBills = false;
    $isOwn = false;
}
?>

<?php if (isset($hashError) && $hashError): ?>
    <!-- Error page for invalid hash -->
    <div class="row p-0 m-0 mb-1">
        <div class="col p-0 m-0">
            <div class="members view content">
                <div class="text-center">
                    <h1 class="text-danger"><?= __('Access Denied') ?></h1>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h4><?= __('Invalid or Expired Link') ?></h4>
                        <p><?= __('The link you used to access this page is either invalid or has expired.') ?></p>
                        <p><?= __('This could happen if:') ?></p>
                        <ul class="text-start">
                            <li><?= __('The link was copied incorrectly') ?></li>
                            <li><?= __('Your membership information has been updated') ?></li>
                            <li><?= __('The link has expired') ?></li>
                        </ul>
                    </div>

                    <div class="mt-4">
                        <h5><?= __('What can you do?') ?></h5>
                        <div class="d-grid gap-2 d-md-block">
                            <?= $this->Html->link(__('Go to Homepage'), ['controller' => 'Pages', 'action' => 'Home'], ['class' => 'btn btn-primary']) ?>
                            <?php if (isset($contactEmail)): ?>
                                <a href="mailto:<?= h($contactEmail) ?>?subject=<?= urlencode(__('Problem accessing personal page')) ?>" class="btn btn-secondary">
                                    <?= __('Contact Support') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4 text-muted">
                        <small>
                            <?= __('If you believe this is an error, please contact the administrator with the details of how you accessed this page.') ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row p-0 m-0 mb-1">
        <div class="col p-0 m-0">
            <div class="members view content">
                <div class="icon float-end" style="width: 400px">
                    <?php if (!$isLogged): ?>
                        <?php if (!empty($member->user)): ?>
                            <?= $this->Html->link(__('Login'), ['action' => 'view', $member->id], ['class' => 'btn btn-primary btn-sm', 'style' => 'width:300px;height:32px']) ?>
                            <?= __('Your user name is {0}', $member->user->username) ?>
                        <?php else: ?>
                            <?= $this->Html->link(__('Create account with this member'), ['controller' => 'Users', 'action' => 'register', $member->hash], ['class' => 'btn btn-primary btn-sm', 'style' => 'width:300px;height:32px']) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= $curRole->MemberEditAll ? $this->backButtonCtrl('Members', 'index') : $this->BackButton('/') ?>
                    <?php endif; ?>
                </div>

                <h1><?= h(($member->coach ? __('Coach') . ' ' : '') . $member->fullName) ?></h1>
                <h4><?= h($member->teamString) ?></h4>

                <?php if ($isOwn && $member->RegistrationStep($config['year']) > 0): ?>
                    <div class="col-12">
                        <div class="alert alert-danger" role="alert">
                            <h4><?= __('Registration for the year {0}-{1}', $config['year'], $config['year'] + 1) ?></h4>
                            <i><?= __('Each year you need to register for the current season, please follow the steps below:') ?></i>

                            <br /><br />
                            <?php if ($member->RegistrationStep($config['year']) == 1): ?>
                                <strong><?= __('Please check your contact information') ?></strong>
                                <?= $this->Html->link(__('The information is correct'), ['action' => 'checked', $member->id], ['class' => 'btn btn-primary btn-sm']) ?>

                                <?php if (isset($contactEmail)): ?>
                                    <a href="mailto:<?= h($contactEmail) ?>?subject=<?= urlencode(__('Ask for modifications')) ?>" class="btn btn-secondary">
                                        <?= __('Ask for modifications') ?>
                                    </a>
                                <?php endif; ?>
                            <?php elseif ($member->RegistrationStep($config['year']) == 2): ?>
                                <?= __('You are a past member') ?>
                                <?= $this->Html->link(__('I want to start again !'), ['controller' => 'Members', 'action' => 'active', $member->id], ['class' => 'btn btn-primary btn-sm']) ?>
                            <?php elseif ($member->RegistrationStep($config['year']) == 3): ?>
                                <?php if (!$isMobile): ?>
                                    <?= __('You will need to sign the aggreement for this year. A mobile phone is recomended') ?>
                                <?php endif; ?>
                                <br />
                                <strong><?= __('You are not registered for this season') ?></strong>
                                <?= $this->Html->link(__('I register for the season'), ['controller' => 'Members', 'action' => 'agreement', $member->id], ['class' => 'btn btn-primary btn-sm']) ?>
                            <?php elseif ($member->RegistrationStep($config['year']) == 4): ?>
                                <?= __('You have to be approved by a coach, please wait') ?>
                            <?php elseif ($member->RegistrationStep($config['year']) == 6): ?>
                                <?= __('You have no teams, wait a while or check with your coach') ?>
                            <?php else: ?>
                                <?= __('This should not append') ?>
                            <?php endif; ?>

                            <br /><br />
                            <?php if ($member->active): ?>
                                <?= __('But if you would not like to continue') ?>
                                <?= $this->Html->link(__('Quit membership'), ['action' => 'cancelRegistration', $member->id], ['class' => 'btn btn-primary btn-sm']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>


                <div class="row px-0 mx-0">
                    <div class="col-sm-12 col-md-4 px-0 mx-0">
                        <div class="card">
                            <div class="d-flex justify-content-between align-items-center" style="position: relative;">
                                <?= $allowEdit ? $this->my->butPhotoAddEdit($member->ImgExists, 'addPhoto', $member->id) : "" ?>
                                <?= $this->Html->image($imgUrl, ['alt' => 'Portrait', 'class' => 'card-img-top']); ?>
                            </div>
                            <br />
                            <?= __("Identity card") ?>
                            <div class="d-flex justify-content-between align-items-center" style="position: relative;">
                                <?= $allowEdit ? $this->my->butPhotoAddEdit($member->ImgIdExists, 'addPhotoId', $member->id) : "" ?>
                                <?= $this->Html->image($imgIdUrl, ['alt' => 'Id card', 'class' => 'card-img-top']); ?>
                            </div>

                            <div class="card-body">
                                <?php if ($member->active): ?>
                                    <span class="badge bg-info"><?= __('Active') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?= __('Past') ?></span>
                                <?php endif; ?>
                                <?php if ($member->registered): ?>
                                    <span class="badge bg-info"><?= __('Registered') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?= __('Not registered') ?></span>
                                <?php endif; ?>
                                <?php if (!$member->checked): ?>
                                    <span class="badge bg-danger"><?= __('Not reviewed') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-8">
                        <?php
                        $this->my->respGrid(__('Gender'), h($member->GenderStr), true);
                        if (empty($member->address) || empty($member->postcode) || empty($member->city))
                            $this->my->respGrid(__('Adress'), "", true);
                        else
                            $this->my->respGrid(__('Adress'), ($this->Html->link(h($member->address . ", " . $member->postcode . " " . $member->city), 'https://maps.google.ch/maps?q=' . str_replace(" ", "+", $member->address . ", " . $member->postcode . ",+CH"), ['target' => '_blank'])));

                        if ($member->phone_mobile)
                            $this->my->respGrid(__('Mobile'), $this->Html->link(h($member->phone_mobile), 'tel:' . $member->phone_mobile));
                        else
                            $this->my->respGrid(__('Mobile'), "", true);

                        if ($member->email)
                            $this->my->respGrid(__('Email'), $this->Html->link(h($member->email) . ($member->email_valid ? ' ' . __('Valid') : ''), 'mailto:' . $member->email));
                        else
                            $this->my->respGrid(__('Email'), "", true);

                        $this->my->respGrid(__('Nationality'), h($member->nationality), true);
                        $this->my->fieldArray($member->fields, !$member->checked);

                        if ($config['showCommMethod'])
                            $this->my->respGrid(__('Communication'), $member->CommunicationMethodStr);
                        if ($curRole->MemberEditAll)
                            $this->my->respGrid(__('Membership paid'), $this->Number->currency($member->membership_fee_paid, 'CHF'));
                        if ($member->discount)
                            $this->my->respGrid(__('Family discount'), $this->Number->currency($member->discount, 'CHF'));
                        $this->my->respGrid(__('Invoices (Open/Total)'), __('{0}/{1}', $this->Number->format($member->InvoicesOpen), $this->Number->format($member->InvoicesTotal)));
                        $this->my->respGrid(__('Date of birth'), h($member->date_birth));
                        $this->my->respGrid(__x('Payment splited in multiple installments', "Split payment"), $member->multi_payment);
                        $this->my->respGrid(__('Date of joining'), h($member->date_arrival));
                        if (!$member->active) {
                            $this->my->respGrid(__('Date of leaving'), h($member->date_fin));
                            $this->my->respGrid(__('Leaving comment'), h($member->leaving_comment));
                        }
                        if (!empty($member->user))
                            $this->my->respGrid(__('Username'), $member->user->username);
                        $this->my->respGrid(__(''), ($member->bvr) ? $this->my->tags("span", __('Payment slip'), ['class' => "badge bg-info"]) : '');
                        ?>
                        <br />
                        <?= $allowEdit && !$member->checked ? $this->Html->link(__('The information is correct'), ['action' => 'checked', $member->id], ['class' => 'btn btn-primary btn-sm']) : '' ?>
                        <?= $allowQuit && $member->active ? $this->Html->link(__('Quit membership'), ['action' => 'cancelRegistration', $member->id], ['class' => 'btn btn-primary btn-sm']) : '' ?>
                        <?= $allowEdit ? $this->Html->link(__('Edit'), ['action' => 'edit', $member->id], ['class' => 'btn btn-primary btn-sm']) : '' ?>
                    </div>
                </div>
            </div>
        <hr />
        <?php if (sizeof($files) > 0 || $member->RegExists($config['year'])): ?>
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
        <?php endif; ?>

        <?php if (!empty($shopItems) && $isOwn && sizeof($shopItems) > 0 && $member->registered): ?>
            <div class="row px-0 mx-0">
                <div class="col px-0 mx-0">
                    <div class="members view content">
                        <h4>
                            <a class="btn btn-primary pt-1 px-1" data-bs-toggle="collapse" href="#shopSection" role="button" aria-expanded="false" aria-controls="shopSection">
                                <?= $this->my->icon("shopping_cart") ?>
                            </a>
                        </h4>
                        <div class="collapse" id="shopSection">
                            <div class="card card-body">
                                <?= $this->Form->create(null, ['url' => ['action' => 'my-page'], 'id' => 'shop-order-form']) ?>
                                <div class="row">
                                    <?php foreach ($shopItems as $item): ?>
                                        <div class="col-md-6 col-lg-3 m-0 p-0">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?= h($item->label) ?></h5>
                                                    <p class="card-text">
                                                        <span class="fw-bold" data-price="<?= $item->price ?>"><?= $this->Number->currency($item->price, 'CHF') ?></span>
                                                    </p>
                                                    <div class="m-0">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text"><?= __('Quantity') ?></span>
                                                            <input type="number"
                                                                name="shop_order[<?= $item->id ?>]"
                                                                min="0"
                                                                max="10"
                                                                value="0"
                                                                class="form-control quantity-input"
                                                                data-price="<?= $item->price ?>"
                                                                data-label="<?= h($item->label) ?>"
                                                                id="shop-order-<?= $item->id ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div id="order-total" class="h5 text-primary" style="display: none;">
                                                <?= __('Total: {0}', '<span id="total-amount">CHF 0.00</span>') ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <?= $this->Form->button(__('Place Order'), ['class' => 'btn btn-primary', 'id' => 'place-order-btn', 'type' => 'button']) ?>
                                        </div>
                                    </div>
                                </div>
                                <?= $this->Form->end() ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr />

            <script>
                $(document).ready(function() {
                    // Update total when quantity changes
                    $('.quantity-input').on('input change', function() {
                        updateOrderTotal();
                    });

                    // Handle place order button click
                    $('#place-order-btn').on('click', function() {
                        var orderItems = [];
                        var totalAmount = 0;

                        $('.quantity-input').each(function() {
                            var quantity = parseInt($(this).val()) || 0;
                            if (quantity > 0) {
                                var price = parseFloat($(this).data('price'));
                                var label = $(this).data('label');
                                var itemTotal = price * quantity;

                                orderItems.push({
                                    label: label,
                                    quantity: quantity,
                                    price: price,
                                    total: itemTotal
                                });

                                totalAmount += itemTotal;
                            }
                        });

                        if (orderItems.length === 0) {
                            alert('<?= __('Please select at least one item to order.') ?>');
                            return;
                        }

                        // Build confirmation message
                        var confirmMessage = '<?= __('Please confirm your order:') ?>\n\n';
                        orderItems.forEach(function(item) {
                            confirmMessage += item.label + ' x' + item.quantity + ' = CHF ' + item.total.toFixed(2) + '\n';
                        });
                        confirmMessage += '\n<?= __('Total amount: CHF {0}', '') ?>' + totalAmount.toFixed(2);
                        confirmMessage += '\n\n<?= __('Do you want to place this order?') ?>';

                        if (confirm(confirmMessage)) {
                            $('#shop-order-form').submit();
                        }
                    });

                    function updateOrderTotal() {
                        var total = 0;
                        var hasItems = false;

                        $('.quantity-input').each(function() {
                            var quantity = parseInt($(this).val()) || 0;
                            if (quantity > 0) {
                                var price = parseFloat($(this).data('price'));
                                total += price * quantity;
                                hasItems = true;
                            }
                        });

                        if (hasItems) {
                            $('#total-amount').text('CHF ' + total.toFixed(2));
                            $('#order-total').show();
                        } else {
                            $('#order-total').hide();
                        }
                    }

                    // Initial calculation
                    updateOrderTotal();
                });
            </script>
        <?php endif; ?>

        <?php if ($allowViewBills): ?>
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
        <?php endif; ?>

        <?php if (!empty($member->presences) && sizeof($member->presences) > 0): ?>
            <hr />
            <?php usort($member->presences, "cmp_obj"); ?>
            <div class="row px-0 mx-0">
                <div class="col px-0 mx-0">
                    <div class="members view content" style="min-height:300px">
                        <h4><?= __('Attendance') ?></h4>
                        <ul class="nav nav-tabs" id="attendanceTab" role="tablist">
                            <?php
                            // Sort attendance keys in descending order (newest first)
                            $sortedAttendance = $attendance;
                            krsort($sortedAttendance);
                            
                            $firstTab = true;
                            foreach ($sortedAttendance as $key => $value):
                                $id = "Y" . str_replace("-", "", $key);
                                ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link<?= $firstTab ? ' active' : '' ?>" 
                                            id="<?= $id ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?= $id ?>" 
                                            type="button" 
                                            role="tab"
                                            aria-controls="<?= $id ?>" 
                                            aria-selected="<?= $firstTab ? 'true' : 'false' ?>">
                                        <?= h($key) ?>
                                    </button>
                                </li>
                                <?php $firstTab = false; ?>
                            <?php endforeach; ?>
                        </ul>
                        <div class="tab-content mt-3" id="attendanceTabContent">
                            <?php
                            $firstContent = true;
                            foreach ($sortedAttendance as $key => $items):
                                $id = "Y" . str_replace("-", "", $key);
                                ?>
                                <div class="tab-pane fade<?= $firstContent ? ' show active' : '' ?>" 
                                     id="<?= $id ?>" 
                                     role="tabpanel" 
                                     aria-labelledby="<?= $id ?>-tab"
                                     tabindex="0">
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($items as $itemKey => $item): ?>
                                            <span class="badge bg-<?= h($item->statusHtml) ?>" 
                                                  data-bs-toggle="tooltip" 
                                                  data-bs-placement="top"
                                                  data-bs-title="<?= h($item->meeting->name) ?>">
                                                <?= $item->meeting->meeting_date->i18nFormat('dd MMM YYYY') ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php $firstContent = false; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Bootstrap 5 tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
            </script>
        <?php endif; ?>
<?php endif; ?>

