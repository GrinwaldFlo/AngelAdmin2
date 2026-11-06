<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * MemberOrders Controller: give the ordered items to the members (orders of the chosen city)
 * The orders are deleted and the stock managed on the orders page (ShopOrdersController)
 *
 * @property \App\Model\Table\MemberOrdersTable $MemberOrders
 * @method \App\Model\Entity\MemberOrder[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class MemberOrdersController extends AppController
{
    use ShopSiteTrait;

    // An order keeps its line when its bill is deleted (bill_id set to null): its site must not be required
    public const CONTAIN = ['ShopItems', 'Members', 'Bills' => ['Sites' => ['joinType' => 'LEFT']]];

    public array $paginate = [
        'limit' => 500,
        'maxLimit' => 1000,
        // Columns of the associated tables are only sortable when they are listed
        'sortableFields' => [
            'id', 'quantity', 'delivered', 'created', 'attribute',
            'Members.first_name', 'ShopItems.label', 'ShopItems.category', 'Bills.id',
        ],
    ];

    /**
     * Index method with filtering capabilities
     *
     * @param int|null $categoryFilter Filter by ShopItem category (0 = All, 1 = Other, 2 = Travel, 3 = Equipment)
     * @param int|null $deliveredFilter Filter by delivery status (0 = All, 1 = Delivered, 2 = Not Delivered)
     * @param int|null $paidFilter Filter by bill payment status (0 = All, 1 = Paid, 2 = Not Paid)
     * @param int|null $itemFilter Filter by ShopItem (0 = All)
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index(int|null $categoryFilter = null, int|null $deliveredFilter = null, int|null $paidFilter = null, int|null $itemFilter = null)
    {
        $this->Authorization->authorize($this->MemberOrders->newEmptyEntity(), 'viewall');
        $siteId = $this->shopSiteId();
        if ($siteId === 0) {
            return $this->renderChooseSite();
        }

        // Get filter preferences from session
        $categoryFilter = $this->getPrefSession('memberOrdersCategoryFilter', $categoryFilter, 0);
        $deliveredFilter = $this->getPrefSession('memberOrdersDeliveredFilter', $deliveredFilter, 0);
        $paidFilter = $this->getPrefSession('memberOrdersPaidFilter', $paidFilter, 0);
        $itemOptions = $this->fetchTable('ShopItems')->listInSite($siteId);
        $itemFilter = $this->shopItemFilter('memberOrdersItemFilter', $itemFilter, $itemOptions);

        $query = $this->MemberOrders->find('filtered', siteId: $siteId, category: $categoryFilter, delivered: $deliveredFilter, paid: $paidFilter, item: $itemFilter)
            ->contain(self::CONTAIN);
        $memberOrders = $this->paginate($query);

        $categoryOptions = $this->fetchTable('ShopItems')->getCategoryOptions();

        $this->set(compact('memberOrders', 'categoryFilter', 'deliveredFilter', 'paidFilter', 'itemFilter', 'categoryOptions', 'itemOptions'));
    }

    /**
     * View method
     *
     * @param string|null $id Member Order id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $memberOrder = $this->MemberOrders->get($id, contain: self::CONTAIN);
        $this->Authorization->authorize($memberOrder);
        $this->checkShopSite($memberOrder->shop_item->site_id);

        $this->set(compact('memberOrder'));
    }

    /**
     * Mark order as delivered
     *
     * @param string|null $id Member Order id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     */
    public function markDelivered($id = null)
    {
        return $this->setDelivered($id, true, __('The order has been marked as delivered.'));
    }

    /**
     * Mark order as not delivered
     *
     * @param string|null $id Member Order id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     */
    public function markNotDelivered($id = null)
    {
        return $this->setDelivered($id, false, __('The order has been marked as not delivered.'));
    }

    /**
     * @param string|null $id Member Order id.
     * @return \Cake\Http\Response
     */
    private function setDelivered($id, bool $delivered, string $message)
    {
        $this->request->allowMethod(['post', 'patch']);
        $memberOrder = $this->MemberOrders->get($id, contain: ['ShopItems']);
        $this->Authorization->authorize($memberOrder, $delivered ? 'markDelivered' : 'markNotDelivered');
        $this->checkShopSite($memberOrder->shop_item->site_id);

        $memberOrder->delivered = $delivered;
        if ($this->MemberOrders->save($memberOrder)) {
            $this->Flash->success($message);
        } else {
            $this->Flash->error(__('The order could not be updated. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
