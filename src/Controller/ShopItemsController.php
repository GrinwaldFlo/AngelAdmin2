<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * ShopItems Controller: create and edit the items of the chosen city
 *
 * @property \App\Model\Table\ShopItemsTable $ShopItems
 * @method \App\Model\Entity\ShopItem[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ShopItemsController extends AppController
{
    use ShopSiteTrait;

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->Authorization->authorize($this->ShopItems->newEmptyEntity(), 'viewall');
        $siteId = $this->shopSiteId();
        if ($siteId === 0) {
            return $this->renderChooseSite();
        }

        $shopItems = $this->paginate($this->ShopItems->find('inSite', siteId: $siteId));

        $this->set(compact('shopItems'));
    }

    /**
     * View method
     *
     * @param string|null $id Shop Item id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $shopItem = $this->ShopItems->get($id, contain: ['MemberOrders', 'MemberOrders.Members']);
        $this->Authorization->authorize($shopItem);
        $this->checkShopSite($shopItem->site_id);

        $this->set(compact('shopItem'));
    }

    /**
     * Add method: the item belongs to the chosen city
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $shopItem = $this->ShopItems->newEmptyEntity();
        $this->Authorization->authorize($shopItem, 'viewall');
        $siteId = $this->shopSiteId();
        if ($siteId === 0) {
            return $this->renderChooseSite();
        }

        if ($this->request->is('post')) {
            $shopItem = $this->ShopItems->patchEntity($shopItem, $this->request->getData());
            $shopItem->site_id = $siteId;

            return $this->saveItem($shopItem);
        }

        $this->setFormData($shopItem);
    }

    /**
     * Edit method
     *
     * @param string|null $id Shop Item id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $shopItem = $this->ShopItems->get($id);
        $this->Authorization->authorize($shopItem);
        $this->checkShopSite($shopItem->site_id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $shopItem = $this->ShopItems->patchEntity($shopItem, $this->request->getData());

            return $this->saveItem($shopItem);
        }

        $this->setFormData($shopItem);
    }

    /**
     * Delete method
     *
     * @param string|null $id Shop Item id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $shopItem = $this->ShopItems->get($id);
        $this->Authorization->authorize($shopItem);
        $this->checkShopSite($shopItem->site_id);

        if ($this->ShopItems->delete($shopItem)) {
            $this->Flash->success(__('The shop item has been deleted.'));
        } else {
            $error = current($shopItem->getError('member_orders') ?: []);
            $this->Flash->error($error ?: __('The shop item could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Save the posted item (add and edit), back to the form with the errors otherwise
     *
     * @param \App\Model\Entity\ShopItem $shopItem
     * @return \Cake\Http\Response|null
     */
    private function saveItem($shopItem)
    {
        if ($this->ShopItems->save($shopItem)) {
            $this->Flash->success(__('The shop item has been saved.'));

            return $this->redirect(['action' => 'index']);
        }
        $this->Flash->error(__('The shop item could not be saved. Please, try again.'));
        $this->setFormData($shopItem);

        return null;
    }

    /**
     * @param \App\Model\Entity\ShopItem $shopItem
     */
    private function setFormData($shopItem): void
    {
        $categoryOptions = $this->ShopItems->getCategoryOptions();
        $this->set(compact('shopItem', 'categoryOptions'));
    }
}
