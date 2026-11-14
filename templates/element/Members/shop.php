<?php
/**
 * Member Shop Section Element
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var array $shopOffer items the member can order with their available attributes (see ShopOrderComponent::offer())
 * @var bool $isOwn
 * @var bool|null $shopForMember a merchandising manager orders for this member
 *   (see MemberOrderPolicy::canOrderFor()), even not registered or with overdue bills
 */

$shopForMember = !$isOwn && !empty($shopForMember);
if (empty($shopOffer) || (!$isOwn && !$shopForMember)) {
    return;
}

if (!$shopForMember && !$member->registered) {
    echo __('You must be registered to place an order.');
    return;
}

if (!$shopForMember && $member->late_bills_sum > 150) {
    echo __('You have too many overdue bills to place a new order.');
    return;
}

$this->Html->css('shop', ['block' => true]);

// Determine the correct form action based on current context
$currentAction = $this->request->getParam('action');
$formUrl = ($currentAction === 'myPage') ? ['action' => 'my-page'] : ['action' => 'view', $member->id];
$texts = [
    'empty' => __('Please select at least one item to order.'),
    'confirm' => __('Please confirm your order:'),
    'total' => __('Total amount: CHF {0}', ''),
    'question' => $shopForMember
        ? __('Do you want to place this order for {0}?', $member->fullName)
        : __('Do you want to place this order?'),
];
?>
<div class="row px-0 mx-0">
    <div class="col px-0 mx-0">
        <div class="members view content">
            <h4>
                <a class="btn btn-filter pt-1 px-1" data-bs-toggle="collapse" href="#shopSection" role="button" aria-expanded="false" aria-controls="shopSection">
                    <?= $this->my->icon("shopping_cart") ?>
                </a>
            </h4>
            <div class="collapse" id="shopSection">
                <div class="card card-body">
                    <?= $this->Form->create(null, ['url' => $formUrl, 'id' => 'shop-order-form', 'data-texts' => json_encode($texts)]) ?>
                    <div class="row g-2">
                        <?php foreach ($shopOffer as $entry) : ?>
                            <?php $item = $entry['item']; ?>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= h($item->label) ?></h5>
                                        <p class="card-text">
                                            <span class="fw-bold"><?= $this->Number->currency($item->price, 'CHF') ?></span>
                                        </p>
                                        <div class="shop-options">
                                            <?php foreach ($entry['options'] as $option) : ?>
                                                <?php
                                                $name = $option['key'] === null ? "shop_order[{$item->id}]" : "shop_order[{$item->id}][{$option['key']}]";
                                                $inputId = 'shop-order-' . $item->id . ($option['key'] === null ? '' : '-' . $option['key']);
                                                ?>
                                                <div class="input-group input-group-sm shop-option">
                                                    <label class="input-group-text shop-option-label" for="<?= h($inputId) ?>">
                                                        <?= $option['attribute'] === null ? __('Quantity') : h($option['attribute']) ?>
                                                    </label>
                                                    <input type="number"
                                                        name="<?= h($name) ?>"
                                                        min="0"
                                                        max="<?= (int)$option['max'] ?>"
                                                        value="0"
                                                        inputmode="numeric"
                                                        class="form-control quantity-input"
                                                        data-price="<?= h($item->price) ?>"
                                                        data-label="<?= h($item->labelWith($option['attribute'])) ?>"
                                                        id="<?= h($inputId) ?>" />
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-6">
                                <div id="order-total" class="h5 text-primary d-none">
                                    <?= __('Total: {0}', '<span id="total-amount">CHF 0.00</span>') ?>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 text-md-end">
                                <?= $this->Form->button(__('Place Order'), ['class' => 'btn btn-save shop-order-button', 'id' => 'place-order-btn', 'type' => 'button']) ?>
                            </div>
                        </div>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('shop-order-form');
    if (!form) {
        return;
    }
    var texts = JSON.parse(form.dataset.texts);
    var inputs = form.querySelectorAll('.quantity-input');

    // Ordered lines: label, quantity and total of each quantity above 0
    function orderLines() {
        var lines = [];
        inputs.forEach(function (input) {
            var quantity = parseInt(input.value, 10) || 0;
            if (quantity > 0) {
                var price = parseFloat(input.dataset.price);
                lines.push({label: input.dataset.label, quantity: quantity, total: price * quantity});
            }
        });
        return lines;
    }

    function orderTotal(lines) {
        return lines.reduce(function (sum, line) { return sum + line.total; }, 0);
    }

    function updateOrderTotal() {
        var lines = orderLines();
        document.getElementById('total-amount').textContent = 'CHF ' + orderTotal(lines).toFixed(2);
        document.getElementById('order-total').classList.toggle('d-none', lines.length === 0);
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', updateOrderTotal);
        input.addEventListener('change', updateOrderTotal);
    });

    document.getElementById('place-order-btn').addEventListener('click', function () {
        var lines = orderLines();
        if (lines.length === 0) {
            alert(texts.empty);
            return;
        }

        var message = texts.confirm + '\n\n';
        lines.forEach(function (line) {
            message += line.label + ' x' + line.quantity + ' = CHF ' + line.total.toFixed(2) + '\n';
        });
        message += '\n' + texts.total + orderTotal(lines).toFixed(2);
        message += '\n\n' + texts.question;

        if (confirm(message)) {
            form.submit();
        }
    });

    updateOrderTotal();
});
</script>
