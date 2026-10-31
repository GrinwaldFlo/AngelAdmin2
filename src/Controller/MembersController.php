<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Security;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use App\Model\Entity\Role;
use App\Policy\TeamScope;
use Cake\Http\Exception\ForbiddenException;

/**
 * Members Controller
 *
 * @property \App\Model\Table\MembersTable $Members
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \App\Controller\Component\ImageComponent $Image
 * @property \App\Controller\Component\MemberManagementComponent $MemberManagement
 * @property \App\Controller\Component\MemberListComponent $MemberList
 * @property \App\Controller\Component\MemberDocumentComponent $MemberDocument
 * @property \App\Controller\Component\MemberRegistrationComponent $MemberRegistration
 * @property \App\Controller\Component\MemberDataComponent $MemberData
 *
 * @method \App\Model\Entity\Member[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class MembersController extends AppController
{
    /**
     * Maximum quantity of an item in a shop order (as in the form)
     */
    private const SHOP_MAX_QUANTITY = 10;

    public $MemberDocs = null;

    public array $paginate = [
        'limit' => 1000,
        'maxLimit' => 1000,
        'order' => ['Members.first_name' => 'asc'],
        'finder' => 'withTeams'
    ];

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Image');
        $this->loadComponent('MemberManagement');
        $this->loadComponent('MemberList');
        $this->loadComponent('MemberDocument');
        $this->loadComponent('MemberRegistration');
        $this->loadComponent('MemberData');
        $this->Authentication->addUnauthenticatedActions(['myPage', 'register', 'checked', 'agreement', 'agreementPdf', 'active', 'cancelRegistration', 'edit', 'addPhoto', 'addPhotoId']);
    }

    /**
     *
     * @param int | null $teamId
     * @param int | null $memberFilter 0 = All, 1= Active, 2=Passif, 3=Membership not paid, ...
     */
    public function index(int|null $teamId = null, int|null $memberFilter = null)
    {
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'viewall');

        $pref = new PrefResult($this, $teamId, $memberFilter);
        $members = $this->MemberList->getMembersList($pref, finder: 'MembersExtended');
        // Coaches of the selected team, not shown for all teams
        $teamCoaches = $pref->teamId > 0 ? $this->fetchTable('TeamsCoaches')->coachesOf($pref->teamId) : [];

        $this->set(compact('members', 'pref', 'teamCoaches'));
    }

    public function batchRegister(int|null $teamId = null, int|null $memberFilter = null)
    {
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'viewall');

        $pref = new PrefResult($this, $teamId, $memberFilter);
        $members = $this->MemberList->getBatchRegisterList($pref);

        $this->set(compact('members', 'pref'));

        if ($this->request->is('post')) {
            $newData = $this->request->getData();
            $this->Authorization->authorize($this->Members->get((int)$newData['memberId']), 'edit');
            $result = $this->MemberDocument->processBatchRegister($newData['memberId'], $newData, $this->config['year']);

            if ($result) {
                $members = $this->MemberList->getBatchRegisterList($pref);
                $this->set(compact('members', 'pref'));
            }
        }
    }

    public function birthdays(int|null $teamId = null, int|null $memberFilter = null)
    {
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'viewall');

        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $memberFilter = $this->getPrefSession('memberFilter', $memberFilter, 1);

        $members = $this->MemberList->getBirthdaysList($teamId, $memberFilter);
        $teams = $this->getTeamsActiv();

        $this->set(compact('members', 'teamId', 'memberFilter', 'teams'));
    }

    public function newMembers()
    {
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'viewNew');
        $members = $this->MemberList->getNewMembersList();
        $this->set(compact('members'));
    }

    public function list(int|null $teamId = null, int|null $memberFilter = null)
    {
        $this->set("wide", '100');
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'viewall');

        $pref = new PrefResult($this, $teamId, $memberFilter);
        $members = $this->MemberList->getMembersList($pref);
        $teamId = $pref->teamId;
        $memberFilter = $pref->memberFilter;
        $teams = $pref->teams;

        $this->set(compact('members', 'teamId', 'memberFilter', 'teams'));
    }

    public function listEmail(int|null $teamId = null, int|null $memberFilter = null)
    {
        $this->set("wide", '100');
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'viewall');

        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $memberFilter = $this->getPrefSession('memberFilter', $memberFilter, 1);

        $members = $this->MemberList->getEmailList($teamId, $memberFilter);
        $teams = $this->getTeamsActiv();

        $this->set(compact('members', 'teamId', 'memberFilter', 'teams'));
    }

    public function familyReduction(int|null $teamId = null, int|null $memberFilter = null)
    {
        $this->set("wide", '100');
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'admin');

        $members2 = $this->MemberList->getFamilyReductionList();
        $teams = $this->getTeamsActiv();

        $this->set(compact('members2', 'teamId', 'memberFilter', 'teams'));
    }

    public function pictures(int|null $teamId = null, int|null $memberFilter = null)
    {
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'viewall');

        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $memberFilter = $this->getPrefSession('memberFilter', $memberFilter, 1);

        $members = $this->MemberList->getPicturesList($teamId, $memberFilter);
        $teams = $this->getTeamsActiv();

        $this->set(compact('members', 'teamId', 'memberFilter', 'teams'));
    }

    private function getHashCurRole()
    {
        $curRole = new Role();
        $curRole->MemberEditOwn = true;
        $curRole->MemberEditAll = false;
        $curRole->MemberViewAll = false;
        return $curRole;
    }

    private function getHashCurUser($id)
    {
        $curUser = (object) [];
        $curUser->member_id = $id;
        return $curUser;
    }

    /**
     * Access with a member link (hash): only to the member of the link
     * @return bool true for a member link, false for a logged user (normal authorization)
     * @throws \Cake\Http\Exception\ForbiddenException link of another member
     */
    private function hashAccess(int|null $id): bool
    {
        if (!$this->isHashAuthenticated()) {
            return false;
        }
        if (!$this->canAccessMember((int)$id)) {
            throw new ForbiddenException(__('This link does not give access to this member.'));
        }
        $this->Authorization->skipAuthorization();

        return true;
    }

    /**
     * Handle shop order functionality for any member
     * 
     * @param object $member The member entity
     * @param string $redirectAction The action to redirect to after successful order
     * @return \Cake\Http\Response|null Returns redirect response if order processed, null otherwise
     */
    private function handleShopOrder($member, string $redirectAction = 'view')
    {
        // Handle shop order submission: as on the page, only a registered member orders for itself
        $isOwn = $redirectAction === 'myPage' || ($this->curUser !== null && $this->curUser->member_id == $member->id);
        if ($this->request->is('post') && $isOwn && $member->registered) {
            $orderData = $this->request->getData('shop_order');
            if (!empty($orderData)) {
                $orderSuccess = $this->processShopOrder($member, $orderData);
                if ($orderSuccess) {
                    // Redirect based on the specified action
                    if ($redirectAction === 'myPage') {
                        return $this->redirect(['action' => 'myPage']);
                    } else {
                        return $this->redirect(['action' => 'view', $member->id]);
                    }
                }
                // If order failed, continue to show the page with error message
            }
        }

        // Load shop items for the shopping section
        $shopItemsTable = TableRegistry::getTableLocator()->get('ShopItems');
        $shopItems = $shopItemsTable->find('active')->orderByAsc('label')->all();
        
        return $shopItems;
    }

    public function myPage($hash = null)
    {
        $this->Authorization->skipAuthorization();

        // If hash is provided in URL, it will be handled by AppController initialization
        // Otherwise, try to get from cookie
        $workingHash = $hash ?? $this->getCurrentHash();

        if (!$workingHash) {
            $this->Flash->error(__('Invalid access link.'));
            return $this->redirect(['controller' => 'Pages', 'action' => 'Home']);
        }

        $pageData = $this->MemberData->prepareMyPageData($workingHash, $this->config);

        if (isset($pageData['error'])) {
            if ($pageData['error'] === 'empty_hash') {
                $this->MemberRegistration->logSecurityEvent('Invalid hash access attempt', null, 'Empty hash provided', $this->request);
                $this->clearHashCookie();
                $this->Flash->error(__('Invalid access link.'));
                return $this->redirect(['controller' => 'Pages', 'action' => 'Home']);
            } elseif ($pageData['error'] === 'hash_not_found') {
                $this->MemberRegistration->logSecurityEvent('Failed hash access attempt', $workingHash, 'Hash not found in database', $this->request);
                $this->clearHashCookie();
                $this->set($pageData);
                $this->render('view');
                return;
            }
        } else {
            $pageData['curRole'] = $this->getHashCurRole();
            $pageData['curUser'] = $this->getHashCurUser($pageData['member']->id);
        }

        // Handle shop orders if no errors
        if (!isset($pageData['error'])) {
            $shopOrderResult = $this->handleShopOrder($pageData['member'], 'myPage');
            
            // If handleShopOrder returns a redirect response, return it
            if ($shopOrderResult instanceof \Cake\Http\Response) {
                return $shopOrderResult;
            }
            
            // Otherwise, $shopOrderResult contains the shop items
            $pageData['shopItems'] = $shopOrderResult;
        }

        $this->set($pageData);
        $this->render('view');
    }

    /**
     * Process shop order for member
     */
    private function processShopOrder($member, $orderData)
    {
        $memberOrdersTable = TableRegistry::getTableLocator()->get('MemberOrders');
        $shopItemsTable = TableRegistry::getTableLocator()->get('ShopItems');
        $billsTable = TableRegistry::getTableLocator()->get('Bills');

        // Collect items for the order
        $orderItems = [];
        $orders = [];
        $totalAmount = 0;

        foreach ((array)$orderData as $shopItemId => $quantity) {
            // Same limits as the form: whole numbers up to 10, only the items still for sale
            $quantity = $quantity === "" ? 0 : filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => self::SHOP_MAX_QUANTITY]]);
            $shopItem = $quantity ? $shopItemsTable->find('active')->where(['ShopItems.id' => (int)$shopItemId])->first() : null;
            if ($quantity === false || ($quantity > 0 && $shopItem === null)) {
                $this->Flash->error(__('The order is not valid. Please try again.'));
                return false;
            }
            if ($quantity > 0) {
                $itemTotal = $shopItem->price * $quantity;
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'id' => $shopItemId,
                    'label' => $shopItem->label,
                    'price' => $shopItem->price,
                    'quantity' => $quantity
                ];

                $orders[] = $memberOrdersTable->newEntity([
                    'shop_item_id' => $shopItemId,
                    'member_id' => $member->id,
                    'quantity' => $quantity,
                    'delivered' => false,
                ]);
            }
        }

        if (empty($orderItems)) {
            $this->Flash->error(__('No items were selected for the order.'));
            return false;
        }

        // Create bill for the entire order
        $bill = $billsTable->CreateShopBill($member, $orderItems, 1);

        if ($bill) {
            // Update all orders with the bill_id
            foreach ($orders as $order) {
                $order->bill_id = $bill->id;
                if (!$memberOrdersTable->save($order)) {
                    $this->Flash->error(__('There was an error processing your order. Please try again.'));
                    return false;
                }
            }

            // Reload the bill with Members and Sites relationships for PDF generation
            $billForPdf = $billsTable->get($bill->id, ['contain' => ['Members', 'Sites']]);

            // Generate PDF for the shop bill
            $this->generateShopBillPdf($billForPdf);

            // Format total amount with CHF currency
            $formattedTotal = 'CHF ' . number_format($totalAmount, 2);
            $this->Flash->success(__('Your order has been placed successfully! Total amount: {0}', $formattedTotal));
            return true;
        } else {
            $this->Flash->error(__('There was an error creating the invoice for your order. Please try again.'));
            return false;
        }
    }

    /**
     * Generate PDF for shop bill without redirecting
     */
    private function generateShopBillPdf($bill)
    {
        try {
            // Ensure member folder exists
            $bill->member->CheckFolder();

            // Set PDF generation parameters
            $this->set("outputType", 'F');
            $this->set("outputPath", $bill->BillPath);
            $this->set("bill", $bill);

            // Use PDF layout and render
            $this->viewBuilder()->setLayout('pdf');
            $this->render('/Bills/pdf');

            // Log the PDF generation
            Log::info("PDF Generated for shop bill " . $bill->id);

        } catch (\Exception $e) {
            Log::error("Failed to generate PDF for shop bill " . $bill->id . ": " . $e->getMessage());
            // Don't throw the exception, just log it so the order process continues
        }
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $member = $this->MemberManagement->createMember($this->request->getData());

        if ($member && !is_string($member)) {
            $teams = $this->getTeamsActiv(scoped: false);
            $this->set(compact('member', 'teams'));

            if ($this->request->is('post') && $member->id) {
                return $this->redirect(['action' => 'index']);
            }
        } elseif (is_string($member)) {
            return $this->redirect(['action' => 'index']);
        }
    }

    public function addPhoto(int|null $id = null)
    {
        $workingHash = $this->getCurrentHash();
        $member = $this->Members->get($id);

        if ($this->isHashAuthenticated()) {
            $this->Authorization->skipAuthorization();
            $curRole = $this->getHashCurRole();
            $curUser = $this->getHashCurUser($id);
            $this->set(compact('curRole', 'curUser'));
        } else {
            $this->Authorization->authorize($member, 'edit');
        }

        // Check if user has access to this member (for regular authenticated users)
        if (!$this->canAccessMember($id)) {
            $this->Authorization->authorize($member, 'edit');
        }

        if ($this->request->is('post')) {
            $result = $this->MemberDocument->addPhoto($id, $this->request->getData(), $this->isHashAuthenticated());
            if ($result) {
                if ($this->isHashAuthenticated()) {
                    return $this->redirect(['action' => 'myPage']);
                } else {
                    return $this->redirect(['action' => 'view', $id]);
                }
            }
        }
    }

    public function addPhotoId(int|null $id = null)
    {
        $workingHash = $this->getCurrentHash();
        $member = $this->Members->get($id);

        if ($this->isHashAuthenticated()) {
            $this->Authorization->skipAuthorization();
            $curRole = $this->getHashCurRole();
            $curUser = $this->getHashCurUser($id);
            $this->set(compact('curRole', 'curUser'));
        }
        else
        {
            $this->Authorization->authorize($member, 'edit');
        }

        // Check if user has access to this member (for regular authenticated users)
        if (!$this->canAccessMember($id)) {
            $this->Authorization->authorize($member, 'edit');
        }

        if ($this->request->is('post')) {
            $result = $this->MemberDocument->addPhotoId($id, $this->request->getData(), $this->isHashAuthenticated());
            if ($result) {
                if ($this->isHashAuthenticated()) {
                    return $this->redirect(['action' => 'myPage']);
                } else {
                    return $this->redirect(['action' => 'view', $id]);
                }
            }
        }
    }

    /**
     * View method
     *
     * @param string|null $id Member id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(int|null $id = null)
    {
        // If hash authenticated, redirect to myPage unless it's their own data
        if ($this->isHashAuthenticated()) {
            $hashMember = $this->getHashAuthenticatedMemberData();
            if ($hashMember && $hashMember->id != $id) {
                $this->Flash->error(__('You can only view your own information.'));
                return $this->redirect(['action' => 'myPage']);
            } elseif ($hashMember && $hashMember->id == $id) {
                // Redirect to myPage for hash-authenticated users viewing their own data
                return $this->redirect(['action' => 'myPage']);
            }
        }

        $member = $this->Members->get($id, contain: ['Teams', 'Bills', 'Presences', 'Users', 'Presences.Meetings', 'Bills.Members', 'Fields' => ['FieldTypes'], 'Bills.Sites']);
        $this->Authorization->authorize($member);

        $viewData = $this->MemberData->prepareMemberViewData($member, $this->config);

        // Handle shop orders
        $shopOrderResult = $this->handleShopOrder($member, 'view');
        
        // If handleShopOrder returns a redirect response, return it
        if ($shopOrderResult instanceof \Cake\Http\Response) {
            return $shopOrderResult;
        }
        
        // Otherwise, add shop items to view data
        $viewData['shopItems'] = $shopOrderResult;

        $this->set($viewData);
    }

    /**
     * Edit method
     *
     * @param string|null $id Member id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(int|null $id = null)
    {
        $this->Authorization->skipAuthorization();

        $workingHash = $this->getCurrentHash();

        if (!$this->Authentication->getIdentity() && !$this->isHashAuthenticated()) {
            $this->Flash->error(__('Please log in to access this member.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        if ($this->hashAccess($id)) {
            $curRole = $this->getHashCurRole();
            $curUser = $this->getHashCurUser($id);
            $this->set(compact('curRole', 'curUser'));
        }

        $member = $this->MemberManagement->updateMember($id, $this->request->getData(), $this->isHashAuthenticated());

        // Handle special return values from component
        if (is_string($member) && $member === 'redirect_mypage') {
            return $this->redirect(['action' => 'myPage']);
        }

        // Only show teams for normally authenticated users, all teams: a coach can move a member to any team
        $teams = $this->getTeamsActiv(scoped: false);
        $this->set(compact('member', 'teams'));

        if ($this->request->is(['patch', 'post', 'put']) && $member && $member->id) {
            if($this->isHashAuthenticated())
            {
                return $this->redirect(['action' => 'my-page']);
            }
            return $this->redirect(['action' => 'view', $id]);
        }
    }

    public function checked(int|null $id = null)
    {
        $this->request->allowMethod(['post']);
        $isHashValid = $this->hashAccess($id);

        $this->MemberManagement->setMemberChecked($id, $isHashValid);
        if ($isHashValid)
            return $this->redirect(['action' => 'myPage']);
        return $this->redirect(['action' => 'view', $id]);
    }

    public function active(int|null $id = null)
    {
        $this->request->allowMethod(['post']);
        $isHashValid = $this->hashAccess($id);

        $this->MemberManagement->setMemberActive($id, $isHashValid);
        if ($isHashValid)
            return $this->redirect(['action' => 'myPage']);
        return $this->redirect('/');
    }

    public function register()
    {
        $this->Authorization->skipAuthorization();

        $member = $this->MemberRegistration->registerMember($this->request->getData(), $this->config);

        if (is_string($member)) {
            switch ($member) {
                case 'redirect_index':
                    return $this->redirect(['action' => 'index']);
                case 'redirect_home':
                    return $this->redirect(['controller' => 'Pages', 'action' => 'Home']);
                case 'contact_info_needed':
                    $this->set('ContactInfo', true);
                    return;
            }
        }

        $this->set(compact('member'));
    }

    public function agreement(int|null $id = null)
    {
        $this->Authorization->skipAuthorization();

        $haskOk = false;
        $workingHash = $this->getCurrentHash();

        if ($workingHash != null) {
            $pageData = $this->MemberData->prepareMyPageData($workingHash, $this->config);

            if (!isset($pageData['error'])) {
                $member = $pageData['member'];
                $this->Authorization->skipAuthorization();
                $haskOk = true;
            }
            else
            {
                $this->Flash->error(__('There where an issue with the connection. Please try again or ask Florian. Cookies has to be allowed.'));
                return $this->redirect('/');
            }
        } else {
            $curUser = $this->Authentication->getIdentity();
            
            // Check if user is authenticated and has a member_id
            if (!$curUser || !$curUser->member_id) {
                $this->Flash->error(__('There where an issue with the connection. Please try again or ask Florian. Cookies has to be allowed.'));
                return $this->redirect('/');
            }
            
            $member = $this->Members->findById($curUser->member_id)->contain('Teams')->firstOrFail();
        }

        $agreements = $this->getData('agreement');
        $this->set(compact('member', 'agreements'));

        if ($this->request->is(['patch', 'post', 'put'])) {
            $regId = $this->MemberRegistration->processAgreement($member, $this->request->getData(), $this->config['year']);
            if ($regId) {
                if ($haskOk) {
                    return $this->redirect(['action' => 'agreementPdf', $member->id, $regId]);
                } else {
                    return $this->redirect(['action' => 'agreementPdf', $member->id, $regId, $workingHash]);
                }
            }
        }
    }

    public function agreementPdf(int|null $member_id = 0, int|null $reg_id = 0)
    {
        $registrations = TableRegistry::getTableLocator()->get('Registrations');

        $member = $this->Members->findById($member_id)->contain('Teams')->first();
        // The member itself (link or account) or the roles editing it
        if (!$this->hashAccess($member_id)) {
            $this->Authorization->authorize($member ?? $this->Members->newEmptyEntity(), 'edit');
        }
        $agreements = $this->getData('agreement');
        $reg = $registrations->findById($reg_id)->first();

        if (empty($member) || empty($reg) || $member->id != $reg->member_id) {
            $this->Flash->error(__('The PDF cant\'t be created'));
            return $this->redirect('/');
        }

        $member->CheckFolder();

        $this->Set("outputType", 'F');
        $this->Set("outputPath", $member->GetRegPath($reg->year));
        $this->viewBuilder()->setLayout('pdf');
        $this->set(compact('member', 'agreements', 'reg'));
        $this->render();

        $workingHash = $this->getCurrentHash();
        if ($workingHash != null) {
            return $this->redirect(['action' => 'myPage']);
        }
        return $this->redirect(['action' => 'view', $member->id]);
    }

    public function cancelRegistration(int|null $id = null)
    {
        $isHashValid = $this->hashAccess($id);

        $result = $this->MemberManagement->cancelMemberRegistration($id, $this->request->getData(), $isHashValid);

        if (is_bool($result) && $result) {
            if ($isHashValid) {
                return $this->redirect(['action' => 'myPage']);
            }
            return $this->redirect(['action' => 'view', $id]);
        } elseif (is_array($result)) {
            $this->set($result);
        }
    }

    public function signatures()
    {
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'editall');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $registration = $this->MemberRegistration->validateSignatures($this->request->getData(), $this->config);
        } else {
            $registrations = TableRegistry::getTableLocator()->get('Registrations');
            $registration = $registrations->find('all')
                ->where(['year =' => $this->config['year'], 'validation_id =' => 0])
                ->contain('Members');
        }

        $this->set(compact('registration'));
    }

    public function invite(int|null $teamId = null, int|null $memberFilter = null)
    {
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'viewall');

        $pref = new PrefResult($this, $teamId, $memberFilter);
        $members = $this->MemberList->getMembersList($pref);

        $this->set(compact('members', 'pref'));

        if ($this->request->is('post')) {
            // Coaches only invite the members of their teams
            $memberIds = array_filter(
                (array)$this->request->getData('MemberId'),
                fn ($value, $memberId) => TeamScope::canAccessMember($this->curUser, (int)$memberId),
                ARRAY_FILTER_USE_BOTH
            );
            $this->MemberRegistration->sendInvitations($memberIds, $this->config);
        }
    }

    /**
     * Save the site (city) chosen in the menu as preference of the current member
     * @param int $siteId 0 = all sites
     */
    public function setSite(int $siteId = 0)
    {
        $this->request->allowMethod(['post']);
        $this->Authorization->authorize($this->Members->newEmptyEntity(), 'setSite');
        $this->MemberManagement->setMemberSite($siteId);
        return $this->redirect($this->referer());
    }
}
