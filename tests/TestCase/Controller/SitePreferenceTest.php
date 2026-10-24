<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AppController;
use App\Controller\Component\MemberManagementComponent;
use App\Controller\MembersController;
use App\Controller\PrefResult;
use App\Model\Entity\Member;
use App\Model\Entity\Role;
use App\Policy\MemberPolicy;
use ArrayObject;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Site (city) preference chosen in the menu
 *
 * @uses \App\Controller\AppController::getSiteId()
 * @uses \App\Controller\Component\MemberManagementComponent::setMemberSite()
 * @uses \App\Controller\PrefResult
 * @uses \App\Policy\MemberPolicy::canSetSite()
 */
class SitePreferenceTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Members',
        'app.Sites',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->getTableLocator()->get('Sites')->insertQuery()
            ->insert(['id', 'city', 'feeMax', 'sender_email', 'sender'])
            ->values([
                'id' => 2,
                'city' => 'Second city',
                'feeMax' => 1,
                'sender_email' => 'second@example.com',
                'sender' => 'Second',
            ])
            ->execute();
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    /**
     * Controller without the authentication initialization, with the given member logged
     */
    private function controllerWithMember(?Member $member): AppController
    {
        $controller = new class (new ServerRequest()) extends MembersController {
            public function initialize(): void
            {
                $this->loadComponent('Flash');
            }
        };
        $controller->curMember = $member;

        return $controller;
    }

    public function testGetSitesIndexedById(): void
    {
        $this->assertSame([1, 2], array_keys(PrefResult::getSites()));
    }

    public function testGetSitesCachesEmptyResult(): void
    {
        $this->getTableLocator()->get('Sites')->deleteAll([]);

        $this->assertSame([], PrefResult::getSites());
        $this->assertSame([], PrefResult::$sites);
    }

    public function testGetSite(): void
    {
        $this->assertSame('Second city', PrefResult::getSite(2)->city);
        $this->assertNull(PrefResult::getSite(0));
        $this->assertNull(PrefResult::getSite(999));
    }

    public function testValidSiteId(): void
    {
        $this->assertSame(2, PrefResult::validSiteId(2));
        $this->assertSame(0, PrefResult::validSiteId(999));
        $this->assertSame(0, PrefResult::validSiteId(0));
        $this->assertSame(0, PrefResult::validSiteId(null));
    }

    public function testGetSiteIdWithoutPreferenceIsAll(): void
    {
        $controller = $this->controllerWithMember(new Member(['pref_site_id' => 0]));

        $this->assertSame(0, $controller->getSiteId());
        $this->assertSame(0, $controller->viewBuilder()->getVar('navSiteId'));
    }

    public function testGetSiteIdRequiredFallsBackToFirstSite(): void
    {
        $controller = $this->controllerWithMember(new Member(['pref_site_id' => 0]));

        $this->assertSame(1, $controller->getSiteId(true));
        $this->assertSame(1, $controller->viewBuilder()->getVar('navSiteId'));
    }

    public function testGetSiteIdReturnsPreference(): void
    {
        $controller = $this->controllerWithMember(new Member(['pref_site_id' => 2]));

        $this->assertSame(2, $controller->getSiteId());
        $this->assertSame(2, $controller->getSiteId(true));
    }

    public function testGetSiteIdIgnoresUnknownSite(): void
    {
        $controller = $this->controllerWithMember(new Member(['pref_site_id' => 999]));

        $this->assertSame(0, $controller->getSiteId());
        $this->assertSame(1, $controller->getSiteId(true));
    }

    public function testGetSiteIdWithoutMember(): void
    {
        $controller = $this->controllerWithMember(null);

        $this->assertSame(0, $controller->getSiteId());
    }

    public function testSetMemberSiteSavesPreference(): void
    {
        $members = $this->getTableLocator()->get('Members');
        $controller = $this->controllerWithMember($members->get(1));
        $component = new MemberManagementComponent($controller->components());

        $this->assertTrue($component->setMemberSite(2));
        $this->assertSame(2, $controller->curMember->pref_site_id);
        $this->assertSame(2, $members->get(1)->pref_site_id);

        $this->assertTrue($component->setMemberSite(0));
        $this->assertSame(0, $members->get(1)->pref_site_id);
    }

    public function testSetMemberSiteRejectsUnknownSite(): void
    {
        $members = $this->getTableLocator()->get('Members');
        $controller = $this->controllerWithMember($members->get(1));
        $component = new MemberManagementComponent($controller->components());

        $this->assertFalse($component->setMemberSite(999));
        $this->assertSame(0, $members->get(1)->pref_site_id);
    }

    /**
     * Identity with the given role rights
     */
    private function identityWithRole(bool $memberViewAll, bool $billViewAll): IdentityInterface
    {
        $user = new ArrayObject(
            ['Role' => new Role(['MemberViewAll' => $memberViewAll, 'BillViewAll' => $billViewAll])],
            ArrayObject::ARRAY_AS_PROPS
        );

        return new IdentityDecorator($this->createStub(AuthorizationServiceInterface::class), $user);
    }

    public function testCanSetSite(): void
    {
        $policy = new MemberPolicy();
        $member = new Member();

        $this->assertTrue($policy->canSetSite($this->identityWithRole(true, false), $member));
        $this->assertTrue($policy->canSetSite($this->identityWithRole(false, true), $member));
        $this->assertFalse($policy->canSetSite($this->identityWithRole(false, false), $member));
        $this->assertFalse($policy->canSetSite(null, $member));
    }

    public function testSetSiteRequiresLogin(): void
    {
        $this->enableCsrfToken();
        $this->post('/members/set-site/2');
        $this->assertResponseCode(302);
        $this->assertSame(0, $this->getTableLocator()->get('Members')->get(1)->pref_site_id);
    }
}
