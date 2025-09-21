<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * MemberOrders Controller
 *
 * @property \App\Model\Table\MemberOrdersTable $MemberOrders
 * @method \App\Model\Entity\MemberOrder[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class MemberOrdersController extends AppController
{
    public array $paginate = [
        'limit' => 500,
        'maxLimit' => 1000
        ];


    /**
     * Index method with filtering capabilities
     *
     * @param int|null $categoryFilter Filter by ShopItem category (0 = All, 1 = Other, 2 = Travel, 3 = Equipment)
     * @param int|null $deliveredFilter Filter by delivery status (0 = All, 1 = Delivered, 2 = Not Delivered)
     * @param int|null $paidFilter Filter by bill payment status (0 = All, 1 = Paid, 2 = Not Paid)
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index(int|null $categoryFilter = null, int|null $deliveredFilter = null, int|null $paidFilter = null)
    {
        $this->Authorization->authorize($this->MemberOrders->newEmptyEntity(), 'viewall');
        
        // Get filter preferences from session
        $categoryFilter = $this->getPrefSession('memberOrdersCategoryFilter', $categoryFilter, 0);
        $deliveredFilter = $this->getPrefSession('memberOrdersDeliveredFilter', $deliveredFilter, 0);
        $paidFilter = $this->getPrefSession('memberOrdersPaidFilter', $paidFilter, 0);
        
        // Build the query with contains
        $query = $this->MemberOrders->find()->contain(['ShopItems', 'Members', 'Bills' => ['Sites']]);
        
        // Apply category filter
        if ($categoryFilter > 0) {
            $query->where(['ShopItems.category' => $categoryFilter]);
        }
        
        // Apply delivery status filter
        if ($deliveredFilter == 1) {
            $query->where(['MemberOrders.delivered' => true]);
        } elseif ($deliveredFilter == 2) {
            $query->where(['MemberOrders.delivered' => false]);
        }
        
        // Apply payment status filter
        if ($paidFilter == 1) {
            $query->where(['Bills.paid' => true]);
        } elseif ($paidFilter == 2) {
            $query->where(['Bills.paid' => false]);
        }

        $memberOrders = $this->paginate($query);

        // Get category options for the view
        $shopItemsTable = \Cake\ORM\TableRegistry::getTableLocator()->get('ShopItems');
        $categoryOptions = $shopItemsTable->getCategoryOptions();

        $this->set(compact('memberOrders', 'categoryFilter', 'deliveredFilter', 'paidFilter', 'categoryOptions'));
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
        $memberOrder = $this->MemberOrders->get($id, contain: ['ShopItems', 'Members', 'Bills' => ['Sites']]);
        $this->Authorization->authorize($memberOrder);

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
        $this->request->allowMethod(['post', 'patch']);
        $memberOrder = $this->MemberOrders->get($id);
        $this->Authorization->authorize($memberOrder);
        
        $memberOrder->delivered = true;
        if ($this->MemberOrders->save($memberOrder)) {
            $this->Flash->success(__('The order has been marked as delivered.'));
        } else {
            $this->Flash->error(__('The order could not be updated. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Mark order as not delivered
     *
     * @param string|null $id Member Order id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     */
    public function markNotDelivered($id = null)
    {
        $this->request->allowMethod(['post', 'patch']);
        $memberOrder = $this->MemberOrders->get($id);
        $this->Authorization->authorize($memberOrder);
        
        $memberOrder->delivered = false;
        if ($this->MemberOrders->save($memberOrder)) {
            $this->Flash->success(__('The order has been marked as not delivered.'));
        } else {
            $this->Flash->error(__('The order could not be updated. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Member Order id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $memberOrder = $this->MemberOrders->get($id);
        $this->Authorization->authorize($memberOrder);
        
        if ($this->MemberOrders->delete($memberOrder)) {
            $this->Flash->success(__('The member order has been deleted.'));
        } else {
            $this->Flash->error(__('The member order could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
