<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Orders of the chosen city: detail (delete an order, change its attribute) and summary by item and attribute (stock)
 * The items are given to the members on the member orders page (MemberOrdersController)
 *
 * @property \App\Model\Table\MemberOrdersTable $MemberOrders
 * @property \App\Controller\Component\ShopOrderComponent $ShopOrder
 */
class ShopOrdersController extends AppController
{
    use ShopSiteTrait;

    protected ?string $defaultTable = 'MemberOrders';

    /**
     * Operations on the stock of an item and attribute (see updateStock())
     */
    public const STOCK_ORDERED = 'ordered';
    public const STOCK_RECEIVED = 'received';
    public const STOCK_ADJUST = 'adjust';

    public array $paginate = [
        'limit' => 500,
        'maxLimit' => 1000,
        'order' => ['MemberOrders.created' => 'desc'],
        'sortableFields' => [
            'id', 'quantity', 'delivered', 'created', 'attribute',
            'Members.first_name', 'ShopItems.label', 'Bills.id',
        ],
    ];

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('ShopOrder');
    }

    /**
     * Detail: every order line
     *
     * @param int|null $itemFilter ShopItem id, 0 = all
     * @param int|null $deliveredFilter 0 = all, 1 = given, 2 = not given
     * @return \Cake\Http\Response|null|void
     */
    public function index(int|null $itemFilter = null, int|null $deliveredFilter = null)
    {
        $this->Authorization->authorize($this->MemberOrders->newEmptyEntity(), 'viewall');
        $siteId = $this->shopSiteId();
        if ($siteId === 0) {
            return $this->renderChooseSite();
        }

        $itemFilter = $this->getPrefSession('shopOrdersItemFilter', $itemFilter, 0);
        $deliveredFilter = $this->getPrefSession('shopOrdersDeliveredFilter', $deliveredFilter, 0);

        $query = $this->MemberOrders->find('filtered', siteId: $siteId, delivered: $deliveredFilter, item: $itemFilter)
            ->contain(MemberOrdersController::CONTAIN);
        $memberOrders = $this->paginate($query);
        $itemOptions = $this->fetchTable('ShopItems')->find('list')->find('inSite', siteId: $siteId)->orderBy(['label' => 'ASC'])->toArray();

        $this->set(compact('memberOrders', 'itemFilter', 'deliveredFilter', 'itemOptions'));
    }

    /**
     * Summary: ordered items by item and attribute, with the stock
     *
     * @return \Cake\Http\Response|null|void
     */
    public function summary()
    {
        $this->Authorization->authorize($this->fetchTable('ShopItems')->newEmptyEntity(), 'manageStock');
        $siteId = $this->shopSiteId();
        if ($siteId === 0) {
            return $this->renderChooseSite();
        }

        $onlyToOrder = (bool)$this->request->getQuery('toOrder', false);
        $rows = $this->fetchTable('ShopStocks')->summary($siteId);
        if ($onlyToOrder) {
            $rows = array_values(array_filter($rows, fn($row) => $row['stock']->toOrder() > 0));
        }

        $this->set(compact('rows', 'onlyToOrder'));
    }

    /**
     * Delete an order: the stock is given back, the bill is not changed (an email asks to correct it)
     *
     * @param string|null $id Member Order id.
     * @return \Cake\Http\Response
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $memberOrder = $this->MemberOrders->get($id, contain: MemberOrdersController::CONTAIN);
        $this->Authorization->authorize($memberOrder);
        $this->checkShopSite($memberOrder->shop_item->site_id);

        if ($this->ShopOrder->deleteOrder($memberOrder)) {
            $this->Flash->success($memberOrder->bill === null
                ? __('The member order has been deleted.')
                : __('The member order has been deleted. The bill has not been changed, an email has been sent to correct it.'));
        } else {
            $this->Flash->warning(__('The member order has been deleted, but the email about its bill could not be sent: correct the bill {0} by hand.', $memberOrder->bill->reference));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Change the attribute of an order not given yet
     *
     * @param string|null $id Member Order id.
     * @return \Cake\Http\Response
     */
    public function changeAttribute($id = null)
    {
        $this->request->allowMethod(['post', 'put']);
        $memberOrder = $this->MemberOrders->get($id, contain: ['ShopItems']);
        $this->Authorization->authorize($memberOrder);
        $this->checkShopSite($memberOrder->shop_item->site_id);

        $error = $this->ShopOrder->changeAttribute($memberOrder, (string)$this->request->getData('attribute'));
        if ($error === null) {
            $this->Flash->success(__('The order has been changed.'));
        } else {
            $this->Flash->error($error);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Stock of an item and attribute: ordered from the supplier, received, or counted by hand
     * Posted: shop_item_id, attribute ('' = none), operation (see the STOCK_ constants), quantity
     *
     * @return \Cake\Http\Response
     */
    public function updateStock()
    {
        $this->request->allowMethod(['post', 'put']);
        $items = $this->fetchTable('ShopItems');
        $this->Authorization->authorize($items->newEmptyEntity(), 'manageStock');
        if ($this->shopSiteId() === 0) {
            return $this->redirectChooseSite();
        }

        $item = $items->get((int)$this->request->getData('shop_item_id'));
        $this->checkShopSite($item->site_id);
        $attribute = (string)$this->request->getData('attribute');
        $operation = (string)$this->request->getData('operation');
        // Counted stock can be negative (items owed to the members), the other quantities are added
        $minimum = $operation === self::STOCK_ADJUST ? -100000 : 1;
        $quantity = filter_var($this->request->getData('quantity'), FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimum, 'max_range' => 100000]]);
        $redirect = $this->redirect(['action' => 'summary', '?' => array_filter(['toOrder' => $this->request->getData('toOrder')])]);
        if ($quantity === false) {
            $this->Flash->error(__('The quantity is not valid.'));

            return $redirect;
        }

        // An offered attribute, or one removed since that still has stock or orders (they created its stock)
        $stocks = $this->fetchTable('ShopStocks');
        $offered = $item->hasAttributes() ? $item->getAttributeList() : [''];
        if (!in_array($attribute, $offered, true) && $stocks->getFor($item->id, $attribute)->isNew()) {
            $this->Flash->error(__('This attribute is not offered for this item.'));

            return $redirect;
        }
        $label = $item->labelWith($attribute);
        switch ($operation) {
            case self::STOCK_ORDERED:
                $stocks->markOrdered($item->id, $attribute, $quantity);
                $this->Flash->success(__('{0} x {1} marked as ordered from the supplier.', $quantity, $label));
                break;
            case self::STOCK_RECEIVED:
                $stocks->receive($item->id, $attribute, $quantity);
                $this->Flash->success(__('{0} x {1} received and added to the stock.', $quantity, $label));
                break;
            case self::STOCK_ADJUST:
                $stocks->setQuantity($item->id, $attribute, $quantity);
                $this->Flash->success(__('The stock of {0} is now {1}.', $label, $quantity));
                break;
            default:
                $this->Flash->error(__('The order is not valid. Please try again.'));
        }

        return $redirect;
    }
}
