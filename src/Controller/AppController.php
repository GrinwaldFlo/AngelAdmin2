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
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;
use Cake\Http\Cookie\Cookie;

class PrefResult
{
    public int $siteId;
    public int $teamId;
    public int $memberFilter;

    public $members;
    /**
     * @var \app\Model\Entity\Team[]
     */
    public $teams;
    public $site;
    /**
     * @var \app\Model\Entity\Site[]
     */
    public static array $sites;

    /**
     * @param AppController $app
     */
    public function __construct(AppController $app, int|null $siteId = null, int|null $teamId = null, int|null $memberFilter = null)
    {
        if (empty(PrefResult::$sites)) {
            $sitesTable = TableRegistry::getTableLocator()->get('Sites');
            $sites = $sitesTable->find('all')->toArray();
            PrefResult::$sites = [];
            foreach ($sites as $site) {
                PrefResult::$sites[$site->id] = $site;
            }
        }

        $this->siteId = $app->getPrefSession('siteId', $siteId, 0);
        $this->site = $this->siteId > 0 ? PrefResult::$sites[$this->siteId] ?? null : null;

        $this->teamId = $app->getPrefSession('teamId', $teamId, 0);
        $this->teams = $app->getTeamsActiv($this->siteId);
        $this->teamId = $this->validateTeamId($this->teamId, $this->teams);

        $this->memberFilter = $app->getPrefSession('memberFilter', $memberFilter, 1);
    }

    /**
     * Checks if $teamId exists in $teams array, otherwise sets it to null.
     *
     * @param int $teamId
     * @param array $teams
     * @return int
     */
    public static function validateTeamId($teamId, $teams): int
    {
        if (empty($teamId)) {
            return 0;
        }
        // If $teams is associative (id => name), check keys
        if (is_array($teams) && !in_array($teamId, array_keys($teams))) {
            return 0;
        }
        return $teamId;
    }

}

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
         * see https://book.cakephp.org/4/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
        $this->loadConfig();

        $this->Session = $this->getRequest()->getSession();

        $this->set("wide", '');
        $this->set('isMobile', $this->request->is('Mobile'));

        $locale = $this->config['lng'];
        if ($this->Authentication->getIdentity() != NULL) {
            $members = TableRegistry::getTableLocator()->get('Members');
            $contents = TableRegistry::getTableLocator()->get('Contents');
            $this->curUser = $this->Authentication->getIdentity();
            $this->curMember = $members->findById($this->curUser->member_id)->contain('Teams')->firstOrFail();
            $this->curRole = $this->Authentication->getIdentity()->Role;

            $this->set("isLogged", true);
            $this->set("curRole", $this->curRole);
            $this->set("curUser", $this->curUser);

            $locale = $this->curMember->language;

            $externalLinks = $contents->find('all')->where(['location =' => 1, 'team_id IN' => $this->curMember->TeamIds]);
            $this->set("externalLinks", $externalLinks);
        } else
        {
            // Check for hash authentication
            $this->initializeHashAuthentication();

            $this->set("isLogged", false);
            $locale = $this->getPrefSessionStr('lng', null, $locale);
        }

        if ($this->config[$locale] == 0)
            $locale = $this->config['lng'];

        I18n::setLocale($locale);
    }

    /**
     * Check if a string is a valid SHA-1 hash (40 hex characters)
     */
    protected function isValidHashFormat(?string $hash): bool
    {
        return is_string($hash) && preg_match('/^[a-f0-9]{40}$/i', $hash) === 1;
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
            'Lax' // sameSite
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
            // User can access their own data or if they have edit permissions
            return $curUser->member_id == $memberId || $this->curRole->MemberEditAll;
        }

        // If hash authenticated, check if hash belongs to this member
        if ($this->isHashAuthenticated()) {
            return $this->hashAuthMember->id == $memberId;
        }

        return false;
    }

    private function loadConfig()
    {
        $this->config = array();
        $configurations = TableRegistry::getTableLocator()->get('Configurations');

        $config = $configurations->find('all');

        foreach ($config as $value) {
            $this->config[$value->label] = $value->value;
        }

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
            case 1;
                $bills->where(['Bills.paid =' => 0]);
                break;
            case 2; // Late
                $bills->where(['Bills.due_date <' => \Cake\I18n\FrozenDate::today()])->where(['Bills.paid =' => 0]);
                break;
            case 3;
                $bills->where(['Bills.paid =' => 1]);
                break;
            case 4;
                $bills->where(['Bills.printed =' => 0])->where(['Bills.paid =' => 0]);
                break;
        }

        //************************************** Member status **************************************
        switch ($memberStatus) {
            case 1;
                $bills->where(['Members.active =' => 1]);
                break;
            case 2;
                $bills->where(['Members.active =' => 0]);
                break;
        }


        //We never show canceled bills
        $bills->where(['Bills.canceled =' => 0]);

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

    public function getTeamsActiv($siteId = 0): array
    {
        // Use siteId as cache key
        if (isset($this->teamCache[$siteId])) {
            return $this->teamCache[$siteId];
        }
        $teams = TableRegistry::getTableLocator()->get('Teams');
        if ($siteId > 0) {
            $result = $teams->find('list')->where(['active =' => 1, 'site_id =' => $siteId])->orderBy(['name' => 'ASC'])->toArray();
        } else {
            $result = $teams->find('list')->where(['active =' => 1])->orderBy(['name' => 'ASC'])->toArray();
        }
        $this->teamCache[$siteId] = $result;
        return $result;
    }

    /**
     * Get members
     * @param int $teamId Team ID
     * @param int $memberFilter 0: All, 1: Active, 2: Passif, 3: Coti not paid
     * @return SelectQuery
     */
    public function getMembers($teamId, $memberFilter): SelectQuery
    {
        $membersTable = TableRegistry::getTableLocator()->get('Members');
        return $membersTable->find(
            'Members',
            teamId: $teamId,
            memberFilter: $memberFilter
        )->contain('Teams')->orderBy(['first_name' => 'ASC', 'last_name' => 'ASC']);
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
