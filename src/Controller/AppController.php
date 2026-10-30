<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use App\Model\Table\MembersTable;
use App\Policy\TeamScope;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;
use Cake\Http\Cookie\Cookie;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    var $Session;
    var $teamCache = [];
    public array $config = [];
    public $curUser;
    public $curMember;
    public $curRole;

    // Add property to track hash authentication
    protected $hashAuthMember = null;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('Authorization.Authorization');
        $this->loadComponent('Mail');
        $this->loadComponent('Get');
        
        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html#formprotection
         */
        //$this->loadComponent('FormProtection');

        $this->loadConfig();

        $this->Session = $this->getRequest()->getSession();

        $this->set("wide", '');
        $this->set('isMobile', $this->request->is('Mobile'));

        $locale = $this->config['lng'] ?? 'fr';
        if ($this->Authentication->getIdentity() != NULL) {
            $members = TableRegistry::getTableLocator()->get('Members');
            $contents = TableRegistry::getTableLocator()->get('Contents');
            $this->curUser = $this->Authentication->getIdentity();
            $this->curMember = $members->findById($this->curUser->member_id)->contain('Teams')->firstOrFail();
            $this->curRole = $this->Authentication->getIdentity()->Role;

            $this->set("isLogged", true);
            $this->set("curRole", $this->curRole);
            $this->set("curUser", $this->curUser);

            $locale = $this->curMember->language ?? $locale;

            $externalLinks = $contents->find('all')->where(['location =' => 1, 'team_id IN' => $this->curMember->TeamIds]);
            $this->set("externalLinks", $externalLinks);
        } else
        {
            // Check for hash authentication
            $this->initializeHashAuthentication();

            $this->set("isLogged", false);
            $locale = $this->getPrefSessionStr('lng', null, $locale);
        }

        if (($this->config[$locale] ?? 0) == 0) {
            $locale = $this->config['lng'] ?? 'fr';
        }

        I18n::setLocale($locale);
    }

    /**
     * Load the site (city) menu, only for the roles seeing it
     */
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        // Coaches: hide the links refused to them
        $this->set('isScoped', TeamScope::isScoped($this->curUser));

        if ($this->curRole !== null && ($this->curRole->MemberViewAll || $this->curRole->BillViewAll)) {
            $this->set("navSites", PrefResult::getSites());
            // Keep the site already used by the page
            if (!$this->viewBuilder()->hasVar('navSiteId')) {
                $this->getSiteId();
            }
        }
    }

    /**
     * Check if a string is a valid SHA-1 hash (40 hex characters)
     */
    protected function isValidHashFormat(?string $hash): bool
    {
        return MembersTable::isValidHash($hash);
    }

    /**
     * Initialize hash-based authentication from cookie or URL parameter
     */
    protected function initializeHashAuthentication(): void
    {
        // First check if hash is provided in URL (for initial access from email)
        $urlHash = $this->request->getParam('pass.0') ?? null;
        if ($urlHash && !$this->isValidHashFormat($urlHash)) {
            $urlHash = null;
        }

        // Then check for existing hash cookie
        $cookieHash = $this->request->getCookie('member_hash');

        $hash = $urlHash ?? $cookieHash;

        if ($hash && $this->validateHashAuthentication($hash)) {
            // Set/refresh the cookie for 24 hours
            $this->setHashCookie($hash);
            $this->hashAuthMember = $this->getHashAuthenticatedMember($hash);
        } else {
            \Cake\Log\Log::info("Hash Auth Failed - Hash validation failed or no hash provided");
        }
    }

    /**
     * Validate if a hash is valid for authentication
     */
    protected function validateHashAuthentication(?string $hash): bool
    {
        if (!$hash) {
            return false;
        }
        
        $membersTable = TableRegistry::getTableLocator()->get('Members');
        $member = $membersTable->find()->where(['hash' => $hash])->first();

        return $member !== null;
    }

    /**
     * Get member by hash
     */
    protected function getHashAuthenticatedMember(string $hash)
    {
        $membersTable = TableRegistry::getTableLocator()->get('Members');
        return $membersTable->find()->where(['hash' => $hash])->contain('Teams')->first();
    }

    /**
     * Set secure cookie for hash authentication
     */
    protected function setHashCookie(string $hash): void
    {
        $cookie = new Cookie(
            'member_hash',
            $hash,
            new \DateTimeImmutable('+24 hours'),
            '/',
            '',
            $this->request->is('https'),
            true, // httpOnly
            'Lax' // Lax is needed so the link is validated by Google or Outlook
        );

        $this->response = $this->response->withCookie($cookie);
    }

    /**
     * Clear hash authentication cookie
     */
    public function clearHashCookie(): void
    {
        $cookie = new Cookie(
            'member_hash',
            '',
            new \DateTimeImmutable('-1 hour'), // Expire in the past
            '/',
            '',
            $this->request->is('https'),
            true,
            'Lax'
        );

        $this->response = $this->response->withCookie($cookie);
    }

    /**
     * Check if current request is hash authenticated
     */
    public function isHashAuthenticated(): bool
    {
        return $this->hashAuthMember !== null;
    }

    /**
     * Get hash authenticated member
     */
    public function getHashAuthenticatedMemberData(): ?object
    {
        return $this->hashAuthMember;
    }

    /**
     * Get current hash from cookie
     */
    public function getCurrentHash(): ?string
    {
        return $this->request->getCookie('member_hash');
    }

    /**
     * Check if current user has permission to access member data
     * Either through normal authentication or hash authentication
     */
    public function canAccessMember(int $memberId): bool
    {
        // If normally authenticated, check if user can access this member
        if ($this->Authentication->getIdentity()) {
            $curUser = $this->Authentication->getIdentity();
            // User can access their own data or if they have edit permissions (coaches: members of their teams)
            return $curUser->member_id == $memberId
                || ($this->curRole->MemberEditAll && TeamScope::canAccessMember($curUser, $memberId));
        }

        // If hash authenticated, check if hash belongs to this member
        if ($this->isHashAuthenticated()) {
            return $this->hashAuthMember->id == $memberId;
        }

        return false;
    }

    private function loadConfig()
    {
        $this->config = [];
        $configurations = TableRegistry::getTableLocator()->get('Configurations');

        $config = $configurations->find('all');

        foreach ($config as $value) {
            $this->config[$value->label] = $value->value;
        }

        $this->config['year'] ??= (int) date('Y');
        $this->config['lng'] ??= 'fr';
        $this->config['firstDaySeason'] ??= '01.09';
        $this->config['clubName'] ??= 'Your club Name';
        $this->config['clubDescription'] ??= 'Your club description';
        $this->config['feeLabel'] ??= 'Membership fee';
        $this->config['domain'] ??= '@example.com';
        $this->config['email'] ??= 'noreply@example.com';
        $this->config['emailName'] ??= 'Club Admin';

        foreach (['fr', 'de', 'en', 'es', 'it'] as $lang) {
            $this->config[$lang] ??= 1;
        }

        $this->config[$this->config['lng']] ??= 1;
        $this->config['dateSeasonStart'] = new \DateTime($this->config['firstDaySeason'] . '.' . $this->config['year']);

        $this->set('config', $this->config);
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        $this->set('refer', $this->referer());
        parent::beforeFilter($event);
        // for all controllers in our application, make index and view
        // actions public, skipping the authentication check
        //$this->Authentication->addUnauthenticatedActions(['index', 'view', 'edit']);
    }

    public function getPrefSession(string $name, int|null $curVal, int $default): int
    {
        if ($curVal === null || $curVal == -999) {
            $curVal = $this->Session->read($name);
            if ($curVal == "")
                $curVal = $default;
        }
        $this->Session->write($name, $curVal);
        return (int) $curVal;
    }

    /**
     * Get the site (city) chosen by the current member in the menu
     * @param bool $required true if the page needs one site: falls back to the first site instead of 0 (all sites)
     * @return int Site id, 0 = all sites
     */
    public function getSiteId(bool $required = false): int
    {
        $sites = PrefResult::getSites();
        $siteId = (int)($this->curMember->pref_site_id ?? 0);
        if (!isset($sites[$siteId])) {
            $siteId = 0;
        }
        if ($siteId == 0 && $required && !empty($sites)) {
            $siteId = array_key_first($sites);
        }
        // The menu shows the site really used by the page
        $this->set('navSiteId', $siteId);
        return $siteId;
    }

    public function getPrefSessionStr(string $name, string|null $curVal, string $default): string
    {
        if ($curVal === null || $curVal == -999) {
            $curVal = $this->Session->read($name);
            if ($curVal == "")
                $curVal = $default;
        }
        $this->Session->write($name, $curVal);
        return $curVal;
    }

    /**
     * Get list of bills
     * @param int $billStatus null or -999 = take last, 0 = Take all, 1 = open, 2 = late, 3 = paid, 4 = draft
     * @param int $memberStatus
     * @param int|null $teamId
     * @return SelectQuery
     */
    public function getBills($billStatus, $memberStatus, $teamId, $siteId): SelectQuery
    {
        //************************************** Team filter **************************************

        if (!empty($teamId) && $teamId != 0) {
            $bills = $this->Bills->find('all')->contain(['Members', 'Members.Teams', 'Sites']) //'Bills', ['billStatus' => $billStatus, 'memberStatus' => $memberStatus, 'teamId' => $teamId]
                ->matching('Members.Teams', function ($q) use ($teamId) {
                    return $q->where(['Teams.id' => $teamId]);
                });
        } else {
            $bills = $this->Bills->find('all')->contain(['Members', 'Members.Teams', 'Sites']);
        }


        //************************************** Bill status **************************************

        switch ($billStatus) {
            case 1:
                $bills->where(['Bills.paid =' => 0]);
                break;
            case 2: // Late
                $bills->where(['Bills.due_date <' => \Cake\I18n\FrozenDate::today()])->where(['Bills.paid =' => 0]);
                break;
            case 3:
                $bills->where(['Bills.paid =' => 1]);
                break;
            case 4:
                $bills->where(['Bills.printed =' => 0])->where(['Bills.paid =' => 0]);
                break;
        }

        //************************************** Member status **************************************
        switch ($memberStatus) {
            case 1:
                $bills->where(['Members.active =' => 1]);
                break;
            case 2:
                $bills->where(['Members.active =' => 0]);
                break;
        }


        //We never show canceled bills
        $bills->where(['Bills.canceled =' => 0]);

        // Coaches: bills of the active members of their teams
        TableRegistry::getTableLocator()->get('Members')->whereActiveInTeams($bills, $this->getCoachedTeamIds(), 'Bills.member_id');

        $bills->where(['Bills.site_id =' => $siteId]);
        return $bills;
    }

    /**
     * Get late bills grouped by member
     * @param int $memberStatus
     * @param int|null $teamId
     * @param int $siteId
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function getLateBillsByMember($memberStatus, $siteId): SelectQuery
    {
        $bills = $this->Bills->find('all')->contain(['Members', 'Members.Teams', 'Sites']);

        // Only late bills: unpaid and due date in the past
        $bills->where([
            'Bills.paid =' => 0,
            'Bills.due_date <' => \Cake\I18n\FrozenDate::today(),
            'Bills.canceled =' => 0,
            'Bills.site_id =' => $siteId
        ]);

        // Member status filter
        switch ($memberStatus) {
            case 1:
                $bills->where(['Members.active =' => 1]);
                break;
            case 2:
                $bills->where(['Members.active =' => 0]);
                break;
        }

        // Group by member
        $bills->select([
            'member_id' => 'Bills.member_id',
            'last_name' => 'Members.last_name',
            'first_name' => 'Members.first_name',
            'email' => 'Members.email',
            'hash' => 'Members.hash',
            'reminder_sent' => 'Members.reminder_sent',
            'count' => $bills->func()->count('Bills.id'),
            'total_amount' => $bills->func()->sum('Bills.amount'),
            'labels' => new \Cake\Database\Expression\QueryExpression('GROUP_CONCAT(Bills.label SEPARATOR " | ")')
        ])
            ->groupBy(['Bills.member_id'])
            ->contain(['Members']);

        return $bills;
    }

    /**
     * Teams the current user can access
     * @return int[]|null null = all teams, [] = none (coach without team)
     */
    public function getCoachedTeamIds(): ?array
    {
        return TeamScope::teamIds($this->curUser);
    }

    /**
     * Active teams
     * @param int $siteId 0 = all sites
     * @param bool $scoped true = only the teams of a coach, false = all teams (e.g. to choose the teams of a member)
     * @return array<int, string> name by id
     */
    public function getTeamsActiv($siteId = 0, bool $scoped = true): array
    {
        $teamIds = $scoped ? $this->getCoachedTeamIds() : null;
        $cacheKey = $siteId . ($teamIds === null ? '' : '-scoped');
        if (isset($this->teamCache[$cacheKey])) {
            return $this->teamCache[$cacheKey];
        }
        $teams = TableRegistry::getTableLocator()->get('Teams');
        $query = $teams->find('list')->where(['active =' => 1])->orderBy(['name' => 'ASC']);
        if ($siteId > 0) {
            $query->where(['site_id =' => $siteId]);
        }
        if ($teamIds !== null) {
            $query->where(['id IN' => $teamIds ?: [0]]);
        }
        $this->teamCache[$cacheKey] = $query->toArray();
        return $this->teamCache[$cacheKey];
    }

    /**
     * Get members
     * @param int $teamId Team ID
     * @param int $memberFilter 0: All, 1: Active, 2: Passif, 3: Coti not paid
     * @param int $siteId Site ID (0 for all sites)
     * @return SelectQuery
     */
    public function getMembers($teamId, $memberFilter, $siteId = 0): SelectQuery
    {
        $membersTable = TableRegistry::getTableLocator()->get('Members');
        return $this->scopeMembers($membersTable->find(
            'Members',
            teamId: $teamId,
            memberFilter: $memberFilter,
            siteId: $siteId
        )->contain('Teams')->orderBy(['first_name' => 'ASC', 'last_name' => 'ASC']));
    }

    /**
     * Coaches: keep only the active members of their teams
     */
    public function scopeMembers(SelectQuery $query): SelectQuery
    {
        return TableRegistry::getTableLocator()->get('Members')->whereActiveInTeams($query, $this->getCoachedTeamIds());
    }

    public function getData($dataType, $param = null)
    {
        $data = TableRegistry::getTableLocator()->get('Data');

        if (empty($param)) {
            $result = $data->find('all')->where(['data_type' => $dataType])->orderBy('param');
        } else {
            $result = $data->find('all')->where(['data_type' => $dataType, 'param' => $param])->first();
            if ($result == null)
                $result = $data->newEmptyEntity();
        }

        return $result->all();
    }

    public function memberIsInSite($member, $siteId)
    {
        foreach ($member->teams as $team) {
            if ($team->site_id == $siteId)
                return true;
        }
        return false;
    }

    public function memberYoungerThan($member, $age)
    {
        if ($age == 0)
            return true;
        if ($member->Age == 0)
            return false;
        return $member->Age < $age;
    }

    public function saveSignature($member_id, $signatureMember, $signatureParent, $year)
    {
        $Registrations = TableRegistry::getTableLocator()->get('Registrations');

        $curReg = $Registrations->find('all')->where(['member_id =' => $member_id, 'year =' => $year])->first();
        if ($curReg == null) {
            $curReg = $Registrations->newEmptyEntity();
        }
        $curReg->member_id = $member_id;
        $curReg->signature_member = $signatureMember;
        $curReg->signature_parent = $signatureParent;
        $curReg->year = $year;
        if (!$Registrations->save($curReg))
            return 0;

        return $curReg->id;
    }


    public function getMembersEvo($teamId = 0, $memberFilter = 0, $siteId = 0)
    {

    }

    /**
     * Generate URL for member actions that works with both authentication methods
     * @param array $url URL array for Router::url()
     * @param bool $includeHash Whether to include hash in URL for sharing
     * @return string
     */
    public function memberUrl(array $url, bool $includeHash = false): string
    {
        // If hash authenticated and we want to include hash for sharing
        if ($includeHash && $this->isHashAuthenticated()) {
            $hash = $this->getCurrentHash();
            if ($hash) {
                $url[] = $hash;
            }
        }

        return Router::url($url, true);
    }

    /**
     * Redirect method that works with hash authentication
     * @param array $url URL array
     * @return \Cake\Http\Response
     */
    public function redirectMember(array $url): \Cake\Http\Response
    {
        if ($this->isHashAuthenticated()) {
            // For hash authenticated users, go to myPage
            return $this->redirect(['controller' => 'Members', 'action' => 'myPage']);
        } else {
            // For normal users, redirect to specified URL
            return $this->redirect($url);
        }
    }

}
