<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ShopItems Controller
 *
 * @property \App\Model\Table\ShopItemsTable $ShopItems
 * @method \App\Model\Entity\ShopItem[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ShopItemsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->Authorization->authorize($this->ShopItems->newEmptyEntity(), 'viewall');
        
        $shopItems = $this->paginate($this->ShopItems);

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

        $this->set(compact('shopItem'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $shopItem = $this->ShopItems->newEmptyEntity();
        $this->Authorization->authorize($shopItem, 'viewall');
        
        if ($this->request->is('post')) {
            $shopItem = $this->ShopItems->patchEntity($shopItem, $this->request->getData());
            if ($this->ShopItems->save($shopItem)) {
                $this->Flash->success(__('The shop item has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The shop item could not be saved. Please, try again.'));
        }
        
        $categoryOptions = $this->ShopItems->getCategoryOptions();
        $this->set(compact('shopItem', 'categoryOptions'));
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
        
        if ($this->request->is(['patch', 'post', 'put'])) {
            $shopItem = $this->ShopItems->patchEntity($shopItem, $this->request->getData());
            if ($this->ShopItems->save($shopItem)) {
                $this->Flash->success(__('The shop item has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The shop item could not be saved. Please, try again.'));
        }
        
        $categoryOptions = $this->ShopItems->getCategoryOptions();
        $this->set(compact('shopItem', 'categoryOptions'));
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
        
        if ($this->ShopItems->delete($shopItem)) {
            $this->Flash->success(__('The shop item has been deleted.'));
        } else {
            $this->Flash->error(__('The shop item could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
