<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Members without account open their page with a link (hash), kept in the member_hash cookie.
 * The link only gives access to its own member: here member 100, member 1 is another one.
 *
 * @uses \App\Controller\MembersController
 * @uses \App\Controller\Component\MemberManagementComponent
 */
class MemberHashAccessTest extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Meetings', 'app.Presences', 'app.Bills', 'app.Fields', 'app.FieldTypes',
        'app.Registrations', 'app.MemberDocs', 'app.ShopItems', 'app.MemberOrders',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->addMember(100, [1], ['checked' => 0, 'registered' => 1]);
        $this->getTableLocator()->get('Members')->updateAll(['checked' => 0], ['id' => 1]);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function asMember100(): void
    {
        $this->cookie('member_hash', sha1('member100'));
    }

    private function member(int $id)
    {
        return $this->getTableLocator()->get('Members')->get($id);
    }

    public function testEditOwnMember(): void
    {
        $this->asMember100();

        $this->get('/members/edit/100');
        $this->assertResponseOk();

        $this->post('/members/edit/100', ['first_name' => 'Changed']);
        $this->assertRedirect(['controller' => 'Members', 'action' => 'my-page']);
        $this->assertSame('Changed', $this->member(100)->first_name);
    }

    public function testEditOtherMemberRefused(): void
    {
        $this->asMember100();

        $this->get('/members/edit/1');
        $this->assertResponseCode(403);

        $this->post('/members/edit/1', ['first_name' => 'Hacked']);
        $this->assertResponseCode(403);
        $this->assertNotSame('Hacked', $this->member(1)->first_name);
    }

    public function testCheckedOwnMember(): void
    {
        $this->asMember100();

        $this->post('/members/checked/100');

        $this->assertRedirect(['controller' => 'Members', 'action' => 'myPage']);
        $this->assertTrue($this->member(100)->checked);
    }

    public function testCheckedOtherMemberRefused(): void
    {
        $this->asMember100();

        $this->post('/members/checked/1');

        $this->assertResponseCode(403);
        $this->assertFalse($this->member(1)->checked);
    }

    public function testActiveOtherMemberRefused(): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['active' => false], ['id' => 1]);
        $this->asMember100();

        $this->post('/members/active/1');

        $this->assertResponseCode(403);
        $this->assertFalse($this->member(1)->active);
    }

    public function testCancelOwnRegistration(): void
    {
        $this->asMember100();

        $this->post('/members/cancel-registration/100', ['comment' => 'Moving']);

        $this->assertRedirect(['controller' => 'Members', 'action' => 'myPage']);
        $this->assertFalse($this->member(100)->active);
    }

    public function testCancelOtherRegistrationRefused(): void
    {
        $this->asMember100();

        $this->post('/members/cancel-registration/1', ['comment' => 'Moving']);

        $this->assertResponseCode(403);
        $this->assertTrue($this->member(1)->active);
    }

    /**
     * A logged user keeping an old member link cookie is checked with its own rights
     */
    public function testLoggedUserWithOldCookieUsesItsRights(): void
    {
        $this->asMember100();
        $this->loginWith(['MemberEditOwn']);

        $this->post('/members/checked/100');

        $this->assertResponseCode(403);
        $this->assertFalse($this->member(100)->checked);
    }

    public function testWithoutLinkNorLogin(): void
    {
        $this->post('/members/checked/100');
        $this->assertResponseCode(403);

        $this->get('/members/edit/100');
        $this->assertRedirectContains('/users/login');
        $this->assertFalse($this->member(100)->checked);
    }

    public function testEditByLinkIgnoresStaffFields(): void
    {
        $this->asMember100();

        $this->post('/members/edit/100', ['first_name' => 'Changed', 'membership_fee_paid' => 1, 'discount' => 50, 'teams' => ['_ids' => [1]]]);

        $member = $this->getTableLocator()->get('Members')->get(100, contain: ['Teams']);
        $this->assertSame('Changed', $member->first_name);
        $this->assertSame(0, $member->membership_fee_paid);
        $this->assertSame(0, $member->discount);
        $this->assertCount(1, $member->teams);
    }

    /**
     * A link or a picture of another site must not change the member (GET requests)
     */
    public function testChangesNeedAPost(): void
    {
        $this->asMember100();

        $this->get('/members/checked/100');
        $this->assertResponseCode(405);
        $this->get('/members/active/100');
        $this->assertResponseCode(405);
        $this->assertFalse($this->member(100)->checked);
    }

    public function testMyPageShowsTheEnabledLanguages(): void
    {
        $this->disableLanguages(['it']);

        $this->get('/members/my-page/' . sha1('member100'));

        $this->assertResponseOk();
        $this->assertResponseContains('href="/users/set-language/de"');
        $this->assertResponseNotContains('href="/users/set-language/it"');
    }

    public function testMyPageHidesTheLanguagesWithOnlyOneEnabled(): void
    {
        $this->disableLanguages(['de', 'en', 'es', 'it']);

        $this->get('/members/my-page/' . sha1('member100'));

        $this->assertResponseOk();
        $this->assertResponseNotContains('/users/set-language/');
    }

    public function testMyPageKeepsTheChosenLanguage(): void
    {
        $locale = I18n::getLocale();
        $page = '/members/my-page/' . sha1('member100');

        $this->configRequest(['headers' => ['Referer' => 'http://localhost' . $page]]);
        $this->get('/users/set-language/de');
        $this->assertRedirect($page);
        $this->assertSession('de', 'lng');

        $this->session(['lng' => 'de']);
        $this->get($page);
        $this->assertResponseOk();
        $this->assertSame('de', I18n::getLocale());

        I18n::setLocale($locale);
    }

    public function testMyPageLoggedUserUsesTheMenu(): void
    {
        $this->loginWith();

        $this->get('/members/my-page/' . $this->member(1)->hash);

        $this->assertResponseOk();
        $this->assertSessionHasKey('Auth');
        $this->assertResponseContains('href="/members/set-language/de"');
        $this->assertResponseNotContains('/users/set-language/');
    }

    /**
     * The link of another member replaces the logged account: no mix of both
     */
    public function testMyPageLinkOfAnotherMemberLogsOut(): void
    {
        $page = '/members/my-page/' . sha1('member100');
        $this->loginWith();

        $this->get($page);

        $this->assertRedirect($page);
        $this->assertSessionNotHasKey('Auth');

        // Next request of the browser, without the account
        $this->_session = [];
        $this->get($page);
        $this->assertResponseOk();
        $this->assertCookie(sha1('member100'), 'member_hash');
        $this->assertResponseContains('href="/members/edit/100"');
        $this->assertResponseContains('/users/set-language/');
        $this->assertResponseNotContains('/members/set-language/');

        $this->asMember100();
        $this->get('/members/edit/100');
        $this->assertResponseOk();
    }

    public function testMyPageLoggedUserIgnoresOldCookie(): void
    {
        $this->asMember100();
        $this->loginWith();

        $this->get('/members/my-page');

        $this->assertRedirect(['controller' => 'Pages', 'action' => 'Home']);
        $this->assertSessionHasKey('Auth');
    }

    public function testMyPageUnknownLinkKeepsTheAccount(): void
    {
        $this->loginWith();

        $this->get('/members/my-page/' . str_repeat('f', 40));

        $this->assertResponseOk();
        $this->assertSessionHasKey('Auth');
    }

    private function disableLanguages(array $languages): void
    {
        foreach ($languages as $language) {
            $this->getTableLocator()->get('Configurations')->insertQuery()
                ->insert(['label', 'value'])
                ->values(['label' => $language, 'value' => '0'])
                ->execute();
        }
    }

    public function testAgreementPdfOfAnotherMemberRefused(): void
    {
        $this->getTableLocator()->get('Registrations')->insertQuery()
            ->insert(['id', 'member_id', 'year'])
            ->values(['id' => 50, 'member_id' => 1, 'year' => 2026])
            ->execute();

        $this->get('/members/agreement-pdf/1/50');
        $this->assertResponseCode(403);

        $this->asMember100();
        $this->get('/members/agreement-pdf/1/50');
        $this->assertResponseCode(403);
    }
}
