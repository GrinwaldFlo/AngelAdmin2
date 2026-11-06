<?php

declare(strict_types=1);

namespace App\Controller\Component;

use App\Controller\PrefResult;
use App\Model\Entity\Member;
use App\Model\Entity\MemberOrder;
use App\Model\Entity\ShopItem;
use Cake\Controller\Component;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Log\Log;
use Cake\Routing\Router;
use Throwable;

/**
 * Shop orders: placed by the members (bill + order lines, stock deducted) and managed by the shop managers
 *
 * @property \App\Controller\Component\MailComponent $Mail
 */
class ShopOrderComponent extends Component
{
    use LocatorAwareTrait;

    /**
     * Maximum quantity of an order line (as in the form)
     */
    public const MAX_QUANTITY = 10;

    /**
     * Results of deleteOrder()
     */
    public const DELETE_DONE = 'done';
    public const DELETE_NO_EMAIL = 'noEmail';
    public const DELETE_FAILED = 'failed';

    protected array $components = ['Mail'];

    /**
     * What a member can order: the active items of its city, with the attributes still available
     * An attribute without stock is hidden, unless the item can be ordered out of stock
     *
     * @return array<int, array{item: \App\Model\Entity\ShopItem, options: array<int, array{attribute: ?string, key: ?string, max: int}>}>
     */
    public function offer(Member $member): array
    {
        $siteId = (int)$member->site_id;
        $stocks = $this->fetchTable('ShopStocks');
        $items = $this->fetchTable('ShopItems')->find('active')->find('inSite', siteId: $siteId)->orderByAsc('label')->all()->toList();
        $itemStocks = $stocks->stocksOf(array_map(fn($item) => $item->id, $items));

        $offer = [];
        foreach ($items as $item) {
            $options = [];
            foreach ($item->orderAttributes() as $attribute) {
                $max = $stocks->orderable($item, $attribute, self::MAX_QUANTITY, $itemStocks);
                if ($max > 0) {
                    $key = $attribute === null ? null : ShopItem::attributeKey($attribute);
                    $options[] = ['attribute' => $attribute, 'key' => $key, 'max' => $max];
                }
            }
            if ($options !== []) {
                $offer[] = ['item' => $item, 'options' => $options];
            }
        }

        return $offer;
    }

    /**
     * Order of a member, posted as shop_order[item id] = quantity, or shop_order[item id][attribute key] = quantity
     * Everything is checked before the bill is created: nothing is saved if a line is not valid
     *
     * @param array|mixed $orderData
     * @return bool true if the order is placed (the messages are flashed)
     */
    public function placeOrder(Member $member, $orderData): bool
    {
        $controller = $this->getController();
        $lines = $this->checkOrder($member, (array)$orderData);
        if (is_string($lines)) {
            $controller->Flash->error($lines);

            return false;
        }
        if ($lines === []) {
            $controller->Flash->error(__('No items were selected for the order.'));

            return false;
        }

        $bills = $this->fetchTable('Bills');
        $error = __('There was an error processing your order. Please try again.');
        $bill = $this->transaction(function () use ($member, $lines, $bills, &$error) {
            $orderItems = array_map(fn($line) => [
                'id' => $line['item']->id,
                'label' => $line['item']->labelWith($line['attribute']),
                'price' => $line['item']->price,
                'quantity' => $line['quantity'],
            ], $lines);
            $bill = $bills->CreateShopBill($member, $orderItems, $lines[0]['item']->site_id);
            if (!$bill) {
                return false;
            }

            $orders = $this->fetchTable('MemberOrders');
            $stocks = $this->fetchTable('ShopStocks');
            foreach ($lines as $line) {
                $order = $orders->newEntity([
                    'shop_item_id' => $line['item']->id,
                    'member_id' => $member->id,
                    'bill_id' => $bill->id,
                    'attribute' => $line['attribute'],
                    'quantity' => $line['quantity'],
                    'delivered' => false,
                ]);
                if (!$orders->save($order)) {
                    return false;
                }
                // Checked again here: another order may have taken the stock since checkOrder()
                if (!$stocks->reserve($line['item'], $line['attribute'], $line['quantity'])) {
                    $error = $this->notEnoughStock($line['item'], $line['attribute']);

                    return false;
                }
            }

            return $bill;
        });

        if (!$bill) {
            $controller->Flash->error($error);

            return false;
        }

        $this->generateBillPdf($bills->get($bill->id, contain: ['Members', 'Sites']));
        $controller->Flash->success(__('Your order has been placed successfully! Total amount: {0}', 'CHF ' . number_format((float)$bill->amount, 2)));

        return true;
    }

    /**
     * Delete an order: the items not given yet go back to the stock
     * The bill is not changed: the sender of the city gets an email to correct it by hand
     *
     * @return string one of the DELETE_ constants
     */
    public function deleteOrder(MemberOrder $order): string
    {
        $orders = $this->fetchTable('MemberOrders');
        $deleted = $this->transaction(function () use ($order, $orders) {
            if (!$order->delivered) {
                $this->fetchTable('ShopStocks')->release($order->shop_item_id, $order->attribute, $order->quantity);
            }

            return $orders->delete($order);
        });
        if (!$deleted) {
            return self::DELETE_FAILED;
        }

        return $order->bill === null || $this->notifyDeletion($order) ? self::DELETE_DONE : self::DELETE_NO_EMAIL;
    }

    /**
     * Change the attribute of an order not given yet, the items are moved in the stock
     *
     * @return string|null error message, null if changed
     */
    public function changeAttribute(MemberOrder $order, string $attribute): ?string
    {
        $item = $order->shop_item;
        if ($order->delivered) {
            return __('This order has already been given to the member.');
        }
        if (!in_array($attribute, $item->getAttributeList(), true)) {
            return __('This attribute is not offered for this item.');
        }
        if ($attribute === $order->attribute) {
            return null;
        }
        $stocks = $this->fetchTable('ShopStocks');
        $orders = $this->fetchTable('MemberOrders');
        $error = __('The order could not be updated. Please, try again.');
        $previous = $order->attribute;
        $changed = $this->transaction(function () use ($order, $item, $attribute, $previous, $orders, $stocks, &$error) {
            // The stock is checked when it is taken: another order may take it at the same time
            if (!$stocks->move($item, $previous, $attribute, $order->quantity)) {
                $error = $this->notEnoughStock($item, $attribute);

                return false;
            }
            $order->attribute = $attribute;

            return (bool)$orders->save($order);
        });
        if (!$changed) {
            $order->attribute = $previous;
        }

        return $changed ? null : $error;
    }

    /**
     * Run the changes in a transaction: rolled back if the callback returns false or throws (logged)
     *
     * @return mixed the result of the callback, false if it failed
     */
    private function transaction(callable $callback): mixed
    {
        try {
            return $this->fetchTable('MemberOrders')->getConnection()->transactional($callback);
        } catch (Throwable $exception) {
            Log::error('Shop order failed: ' . $exception->getMessage());

            return false;
        }
    }

    private function notEnoughStock(ShopItem $item, ?string $attribute): string
    {
        return __('There is not enough stock for {0}.', $item->labelWith($attribute));
    }

    /**
     * Check every line of an order
     *
     * @return array<int, array{item: \App\Model\Entity\ShopItem, attribute: ?string, quantity: int}>|string lines, or the error message
     */
    private function checkOrder(Member $member, array $orderData): array|string
    {
        $invalid = __('The order is not valid. Please try again.');
        $siteId = (int)$member->site_id;
        $stocks = $this->fetchTable('ShopStocks');
        $itemIds = array_map('intval', array_keys($orderData));
        // Only the items still for sale, in the city of the member
        $items = $itemIds === [] ? [] : $this->fetchTable('ShopItems')->find('active')->find('inSite', siteId: $siteId)
            ->where(['ShopItems.id IN' => $itemIds])->all()->indexBy('id')->toArray();
        $itemStocks = $stocks->stocksOf(array_keys($items));

        $lines = [];
        foreach ($orderData as $itemId => $quantities) {
            // An item without attribute has one quantity, an item with attributes one quantity by attribute
            $quantities = is_array($quantities) ? $quantities : ['' => $quantities];
            $item = $items[(int)$itemId] ?? null;
            foreach ($quantities as $key => $quantity) {
                // Same limits as the form: whole numbers up to the maximum
                $quantity = $quantity === '' ? 0 : filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => self::MAX_QUANTITY]]);
                if ($quantity === false) {
                    return $invalid;
                }
                if ($quantity === 0) {
                    continue;
                }
                if ($item === null || ($key === '') === $item->hasAttributes()) {
                    return $invalid;
                }
                $attribute = $key === '' ? null : $item->attributeFromKey((string)$key);
                if ($key !== '' && $attribute === null) {
                    return __('A choice of {0} is not offered anymore, please check your order.', $item->label);
                }
                if ($stocks->orderable($item, $attribute, self::MAX_QUANTITY, $itemStocks) < $quantity) {
                    return $this->notEnoughStock($item, $attribute);
                }
                $lines[] = ['item' => $item, 'attribute' => $attribute, 'quantity' => $quantity];
            }
        }

        return $lines;
    }

    /**
     * Email to the sender of the city: the bill of a deleted order has to be corrected by hand
     */
    private function notifyDeletion(MemberOrder $order): bool
    {
        $controller = $this->getController();
        $site = PrefResult::getSite((int)$order->shop_item->site_id);
        $to = $site?->senderEmail((string)($controller->config['email'] ?? '')) ?? '';
        if ($to === '') {
            Log::warning(sprintf('Shop order #%d deleted: no email to tell about the bill %s', $order->id, $order->bill->reference));

            return false;
        }

        $bill = $order->bill;
        $lines = [
            __('A shop order has been deleted, its bill has not been changed: please correct it by hand.'),
            __('Member: {0}', h($order->member->fullName)),
            __('Item: {0}', h($order->shop_item->labelWith($order->attribute))),
            __('Quantity: {0}', $order->quantity),
            __('Bill: {0} ({1})', h($bill->reference), h($bill->statusString)),
            __('Amount of the bill: {0}', 'CHF ' . number_format((float)$bill->amount, 2)),
            __('Deleted by: {0}', h($controller->curMember->fullName ?? '')),
            Router::url(['controller' => 'Bills', 'action' => 'view', $bill->id], true),
        ];

        try {
            $this->Mail->deliver($to, (string)($site->sender ?? ''), __('Shop order deleted - bill {0}', $bill->reference), ['content' => implode("\n", $lines)]);
        } catch (Throwable $exception) {
            Log::error(sprintf('Shop order #%d deleted, the email about the bill %s failed: %s', $order->id, $bill->reference, $exception->getMessage()));

            return false;
        }

        return true;
    }

    /**
     * Generate PDF for shop bill without redirecting
     */
    private function generateBillPdf($bill): void
    {
        $controller = $this->getController();
        try {
            // Ensure member folder exists
            $bill->member->CheckFolder();

            // Set PDF generation parameters
            $controller->set('outputType', 'F');
            $controller->set('outputPath', $bill->BillPath);
            $controller->set('bill', $bill);

            // Use PDF layout and render
            $controller->viewBuilder()->setLayout('pdf');
            $controller->render('/Bills/pdf');

            Log::info('PDF Generated for shop bill ' . $bill->id);
        } catch (Throwable $e) {
            Log::error('Failed to generate PDF for shop bill ' . $bill->id . ': ' . $e->getMessage());
            // The order is saved: just log it, the PDF can be generated again from the bill
        }
    }
}
