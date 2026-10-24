<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AppController;
use App\Controller\Component\MemberManagementComponent;
use App\Controller\MembersController;
use App\Controller\PrefResult;
use App\Model\Entity\Member;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Site (city) preference chosen in the menu
 *
 * @uses \App\Controller\AppController::getSiteId()
 * @uses \App\Controller\Component\MemberManagementComponent::setMemberSite()
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
        PrefResult::$sites = [];
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
        PrefResult::$sites = [];
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

    public function testSetSiteRequiresLogin(): void
    {
        $this->get('/members/set-site/2');
        $this->assertResponseCode(302);
        $this->assertSame(0, $this->getTableLocator()->get('Members')->get(1)->pref_site_id);
    }
}
