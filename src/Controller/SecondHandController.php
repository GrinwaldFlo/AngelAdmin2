<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\SecondHandItem;
use App\Model\Entity\SecondHandLog;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\DateTime;

/**
 * Second-hand items between members (and past members), logged in or with their member link (hash)
 * Everything belongs to the member: its profile holds the accepted terms and the seller details
 *
 * @property \App\Model\Table\SecondHandItemsTable $SecondHandItems
 * @property \App\Controller\Component\SecondHandComponent $SecondHand
 */
class SecondHandController extends AppController
{
    protected ?string $defaultTable = 'SecondHandItems';

    // Actions that do not need the terms to be accepted
    private const WITHOUT_TERMS = ['terms', 'confirmEmail'];

    /**
     * Member using the page, logged in or with its link
     * @var \App\Model\Entity\Member|null
     */
    protected $member = null;

    /**
     * @var \App\Model\Entity\SecondHandProfile|null
     */
    protected $profile = null;

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('SecondHand');
        $this->Authentication->addUnauthenticatedActions(['index', 'view', 'terms', 'profile', 'myItems', 'add', 'sold', 'delete', 'confirmEmail']);
        // Rights are checked here: the member of the item, members with a link have no identity
        $this->Authorization->skipAuthorization();
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!$this->SecondHand->isEnabled()) {
            throw new NotFoundException();
        }
        $action = $this->request->getParam('action');
        if ($action === 'confirmEmail') {
            return;
        }

        $this->member = $this->curMember ?? $this->getHashAuthenticatedMemberData();
        if ($this->member === null) {
            $this->Flash->error(__('Please log in or use the link of your personal page.'));
            $event->setResult($this->redirect(['controller' => 'Users', 'action' => 'login']));

            return;
        }
        $this->profile = $this->fetchTable('SecondHandProfiles')->forMember($this->member->id);

        if ($this->profile->terms_accepted === null && !in_array($action, self::WITHOUT_TERMS, true)) {
            $event->setResult($this->redirect(['action' => 'terms']));

            return;
        }

        $this->set('isHashUser', $this->curMember === null);
        $this->set('profile', $this->profile);
    }

    /**
     * Items for sale, filtered by category
     */
    public function index(int $categoryId = 0)
    {
        $query = $this->SecondHandItems->find('forSale', months: $this->SecondHand->expiryMonths());
        if ($categoryId > 0) {
            $query->where(['SecondHandItems.category_id' => $categoryId]);
        }
        $items = $this->paginate($query, ['limit' => 60]);
        $categories = $this->fetchTable('SecondHandCategories')->listByName();

        $this->set(compact('items', 'categories', 'categoryId'));
    }

    public function view(int $id)
    {
        $item = $this->SecondHandItems->get($id, contain: ['Profiles', 'Categories']);
        $isOwn = $item->profile_id == $this->profile->id;
        $isAdmin = (bool)($this->curRole->Admin ?? false);
        $forSale = $item->sold === null && !$item->isExpired($this->SecondHand->expiryMonths()) && $item->profile->email_verified;
        if (!$forSale && !$isOwn && !$isAdmin) {
            throw new NotFoundException(__('This item is not for sale anymore.'));
        }

        // Category filter of the list the buyer comes from, to go back to it and browse it
        $categoryId = (int)$this->request->getQuery('category', 0);
        $neighbours = $this->SecondHandItems->neighbours($item, $this->SecondHand->expiryMonths(), $categoryId);

        $this->set(compact('item', 'isOwn', 'isAdmin', 'forSale', 'categoryId', 'neighbours'));
    }

    /**
     * Terms accepted once by each member, before buying or selling
     */
    public function terms()
    {
        if ($this->request->is('post')) {
            if (!$this->request->getData('accept')) {
                $this->Flash->error(__('Please accept the rules to continue.'));
            } elseif ($this->fetchTable('SecondHandProfiles')->acceptTerms($this->profile)) {
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The rules could not be saved. Please try again.'));
            }
        }
    }

    /**
     * Seller details shown to the buyers
     */
    public function profile()
    {
        if (empty($this->profile->email) && !empty($this->member->email)) {
            $this->profile->email = $this->member->email;
        }

        if ($this->request->is(['post', 'put'])) {
            $profiles = $this->fetchTable('SecondHandProfiles');
            if ($profiles->saveSeller($this->profile, $this->request->getData(), $this->member->email)) {
                if ($this->profile->email_verified) {
                    $this->Flash->success(__('Your seller details have been saved.'));
                } elseif ($this->SecondHand->sendEmailConfirmation($this->profile)) {
                    $this->Flash->warning(__('We sent you an email. Click the link in it to confirm your email address.'));
                }

                return $this->redirect(['action' => 'myItems']);
            }
            $this->Flash->error(__('Your seller details could not be saved. Please check the form.'));
        }
    }

    /**
     * Link of the email sent to confirm the email of a seller
     */
    public function confirmEmail(string $token = '')
    {
        if ($this->fetchTable('SecondHandProfiles')->confirmEmail($token) === null) {
            $this->Flash->error(__('This link is not valid or was already used.'));
        } else {
            $this->Flash->success(__('Your email address is confirmed. You can now sell items.'));
        }

        return $this->redirect(['action' => 'myItems']);
    }

    /**
     * Items of the seller
     */
    public function myItems()
    {
        if (!$this->profile->isSeller()) {
            return $this->redirect(['action' => 'profile']);
        }
        $items = $this->SecondHandItems->find('ofProfile', profileId: $this->profile->id)->all();
        $months = $this->SecondHand->expiryMonths();

        $this->set(compact('items', 'months'));
    }

    public function add()
    {
        if (!$this->profile->canSell()) {
            $this->Flash->error(__('Please fill in your seller details and confirm your email address first.'));

            return $this->redirect(['action' => 'profile']);
        }

        $item = $this->SecondHandItems->newEmptyEntity();
        $categories = $this->fetchTable('SecondHandCategories')->listByName();

        if ($this->request->is('post')) {
            $item = $this->SecondHandItems->newEntity($this->request->getData());
            $item->profile_id = $this->profile->id;
            if ($item->hasErrors()) {
                $this->Flash->error(__('The item could not be saved. Please check the form.'));
            } elseif ($this->SecondHand->savePicture($item, $this->request->getData('picture'))) {
                // The picture first: no item without its picture
                if ($this->SecondHandItems->save($item)) {
                    $item->profile = $this->profile;
                    $item->category = $this->SecondHandItems->Categories->get($item->category_id);
                    $this->fetchTable('SecondHandLogs')->record($item);
                    $this->Flash->success(__('Your item is now for sale.'));

                    return $this->redirect(['action' => 'myItems']);
                }
                $item->deleteFolder();
                $this->Flash->error(__('The item could not be saved. Please check the form.'));
            }
        }

        $accept = $this->SecondHand->Image->getAcceptedMimeTypes();
        $formats = $this->SecondHand->Image->getSupportedFormatsMessage();

        $this->set(compact('item', 'categories', 'accept', 'formats'));
    }

    /**
     * The item is not shown to the buyers anymore
     */
    public function sold(int $id)
    {
        $this->request->allowMethod(['post']);
        $item = $this->ownItem($id);
        $item->sold = DateTime::now();
        $this->SecondHandItems->saveOrFail($item);
        $this->fetchTable('SecondHandLogs')->close($item->id, SecondHandLog::SOLD);
        $this->Flash->success(__('The item is marked as sold.'));

        return $this->redirect(['action' => 'myItems']);
    }

    /**
     * The item and its picture are deleted
     */
    public function delete(int $id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $item = $this->ownItem($id);
        $this->SecondHandItems->deleteOrFail($item);
        $this->fetchTable('SecondHandLogs')->close($item->id, SecondHandLog::REMOVED);
        $this->Flash->success(__('The item has been removed.'));

        return $this->redirect(['action' => 'myItems']);
    }

    /**
     * @throws \Cake\Http\Exception\ForbiddenException item of another seller
     */
    private function ownItem(int $id): SecondHandItem
    {
        $item = $this->SecondHandItems->get($id);
        if ($item->profile_id != $this->profile->id) {
            throw new ForbiddenException(__('This item is not yours.'));
        }

        return $item;
    }
}
