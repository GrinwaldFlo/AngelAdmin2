<?php

declare(strict_types=1);

namespace App\Controller;

use App\Policy\TeamScope;
use App\Controller\Component\SecondHandComponent;
use App\Model\Entity\SecondHandLog;
use Cake\Event\EventInterface;

/**
 * Admin of the second-hand items: settings, categories, log of the items
 * Available when the module is disabled, to enable it
 *
 * @property \App\Model\Table\SecondHandCategoriesTable $SecondHandCategories
 * @property \App\Controller\Component\SecondHandComponent $SecondHand
 */
class SecondHandAdminController extends AppController
{
    protected ?string $defaultTable = 'SecondHandCategories';

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('SecondHand');

        $this->Authorization->skipAuthorization();
    }

    /**
     * Only for the admin and merchandising manager roles (not logged users are redirected to the login by the authentication)
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if ($this->curRole !== null && !TeamScope::canManageSecondHand($this->curUser)) {
            $event->setResult($this->redirect('/'));
        }
    }

    /**
     * Settings and categories
     */
    public function index()
    {
        if ($this->request->is(['post', 'put'])) {
            $months = (int)$this->request->getData('expiry_months');
            if ($months < 1 || $months > 120) {
                $this->Flash->error(__('The duration must be between 1 and 120 months.'));
            } else {
                $this->SecondHand->saveSetting(SecondHandComponent::ENABLED, $this->request->getData('enabled') ? '1' : '0');
                $this->SecondHand->saveSetting(SecondHandComponent::EXPIRY_MONTHS, (string)$months);
                $this->Flash->success(__('The settings have been saved.'));

                return $this->redirect(['action' => 'index']);
            }
        }

        $categories = $this->SecondHandCategories->find()
            ->select(['id', 'name', 'items' => $this->SecondHandCategories->find()->func()->count('SecondHandItems.id')])
            ->leftJoinWith('SecondHandItems')
            ->groupBy(['SecondHandCategories.id', 'SecondHandCategories.name'])
            ->orderBy(['SecondHandCategories.name' => 'ASC'])
            ->all();
        $enabled = $this->SecondHand->isEnabled();
        $months = $this->SecondHand->expiryMonths();
        $category = $this->SecondHandCategories->newEmptyEntity();

        $this->set(compact('categories', 'enabled', 'months', 'category'));
    }

    public function addCategory()
    {
        $this->request->allowMethod(['post']);
        $category = $this->SecondHandCategories->newEntity($this->request->getData());
        if ($this->SecondHandCategories->save($category)) {
            $this->Flash->success(__('The category has been saved.'));
        } else {
            $this->Flash->error(__('The category could not be saved.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function editCategory(int $id)
    {
        $category = $this->SecondHandCategories->get($id);
        if ($this->request->is(['post', 'put'])) {
            $category = $this->SecondHandCategories->patchEntity($category, $this->request->getData());
            if ($this->SecondHandCategories->save($category)) {
                $this->Flash->success(__('The category has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The category could not be saved.'));
        }

        $this->set(compact('category'));
    }

    /**
     * Only a category without items can be deleted
     */
    public function deleteCategory(int $id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $category = $this->SecondHandCategories->get($id);
        if ($this->SecondHandCategories->isUsed($category->id)) {
            $this->Flash->error(__('This category still has items, it cannot be deleted.'));
        } elseif ($this->SecondHandCategories->delete($category)) {
            $this->Flash->success(__('The category has been deleted.'));
        } else {
            $this->Flash->error(__('The category could not be deleted.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Log of all the items, including the sold, removed and expired ones
     */
    public function itemsLog()
    {
        $logs = $this->paginate(
            $this->fetchTable('SecondHandLogs')->find()->orderBy(['created' => 'DESC', 'id' => 'DESC']),
            ['limit' => 100]
        );
        $itemIds = array_map(fn ($log) => $log->item_id, $logs->toArray());
        // Items not deleted yet: the admin can delete them
        $existingIds = empty($itemIds) ? [] : $this->fetchTable('SecondHandItems')->find('list', valueField: 'id')
            ->where(['id IN' => $itemIds])
            ->toArray();
        $months = $this->SecondHand->expiryMonths();

        $this->set(compact('logs', 'existingIds', 'months'));
    }

    /**
     * Delete any item with its picture
     */
    public function deleteItem(int $id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $items = $this->fetchTable('SecondHandItems');
        $items->deleteOrFail($items->get($id));
        $this->fetchTable('SecondHandLogs')->close($id, SecondHandLog::DELETED);
        $this->Flash->success(__('The item has been deleted.'));

        return $this->redirect(['action' => 'itemsLog']);
    }
}
