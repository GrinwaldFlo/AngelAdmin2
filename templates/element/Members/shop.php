<?php
/**
 * Member Shop Section Element
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var array $shopItems
 * @var bool $isOwn
 */

if (empty($shopItems) || !$isOwn || sizeof($shopItems) == 0 || !$member->registered) {
    return;
}
?>
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
