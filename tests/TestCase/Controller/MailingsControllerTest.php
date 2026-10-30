<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\MailingsController;
use App\Controller\PrefResult;
use App\Model\Entity\Mailing;
use App\Model\Entity\MailingItem;
use App\Test\TestSuite\FailingTransport;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestEmailTransport;

/**
 * App\Controller\MailingsController Test Case
 *
 * @uses \App\Controller\MailingsController
 */
class MailingsControllerTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Members',
        'app.Users',
        'app.Roles',
        'app.Sites',
        'app.Teams',
        'app.TeamsMembers',
        'app.Configurations',
        'app.Mailings',
        'app.MailingItems',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $configurations = $this->getTableLocator()->get('Configurations');
        $configurations->insertQuery()
            ->insert(['label', 'value'])
            ->values(['label' => 'clubName', 'value' => 'My club'])
            ->values(['label' => 'email', 'value' => 'club@example.com'])
            ->execute();
    }

    private function login(): void
    {
        // Texts are checked in English, whatever translations exist
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'en'], ['id' => 1]);
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)]);
    }

    private function addTeam(int $id, string $name, bool $active = true): void
    {
        $this->getTableLocator()->get('Teams')->insertQuery()
            ->insert(['id', 'name', 'membership_fee', 'active', 'site_id'])
            ->values(['id' => $id, 'name' => $name, 'membership_fee' => 1, 'active' => $active, 'site_id' => 1])
            ->execute();
    }

    /**
     * @param array $teamIds teams of the member
     */
    private function addMember(int $id, array $teamIds, string $email, bool $active = true): void
    {
        $this->getTableLocator()->get('Members')->insertQuery()
            ->insert(['id', 'first_name', 'last_name', 'gender_id', 'email', 'email_valid', 'date_arrival', 'hash', 'active', 'language'])
            ->values([
                'id' => $id, 'first_name' => 'First' . $id, 'last_name' => 'Last' . $id, 'gender_id' => 1, 'email' => $email,
                'email_valid' => 0, 'date_arrival' => '2026-01-01', 'hash' => sha1('member' . $id), 'active' => $active, 'language' => 'fr',
            ])
            ->execute();
        foreach ($teamIds as $teamId) {
            $this->getTableLocator()->get('TeamsMembers')->insertQuery()
                ->insert(['team_id', 'member_id'])
                ->values(['team_id' => $teamId, 'member_id' => $id])
                ->execute();
        }
    }

    /**
     * Mailing to team 2 with a member per given email
     * @return int mailing id
     */
    private function addMailing(array $emails, string $title = 'Hello {first_name}', string $content = "Dear {full_name}\nYour page: {my_page}"): int
    {
        $this->addTeam(2, 'Juniors');
        foreach (array_values($emails) as $i => $email) {
            $this->addMember(100 + $i, [2], $email);
        }
        $mailings = $this->getTableLocator()->get('Mailings');
        $mailing = $mailings->newForTeams(['title' => $title, 'content' => $content], [2]);
        $mailings->saveOrFail($mailing);

        return $mailing->id;
    }

    private function items(int $mailingId): array
    {
        return $this->getTableLocator()->get('MailingItems')->find()
            ->where(['mailing_id' => $mailingId])
            ->orderBy(['id' => 'ASC'])
            ->all()
            ->toArray();
    }

    private function sendNext(int $mailingId): array
    {
        $this->post('/mailings/send-next/' . $mailingId);
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        return json_decode((string)$this->_response->getBody(), true);
    }

    public function testIndexRequiresLogin(): void
    {
        $this->get('/mailings');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexForbiddenWithoutAdminOrEditAll(): void
    {
        $this->getTableLocator()->get('Roles')->updateAll(['Admin' => 0, 'MemberEditAll' => 0], ['id' => 1]);
        $this->login();
        $this->get('/mailings');

        $this->assertResponseCode(403);
    }

    public function testSendNextForbiddenWithoutAdminOrEditAll(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        $this->getTableLocator()->get('Roles')->updateAll(['Admin' => 0, 'MemberEditAll' => 0], ['id' => 1]);
        $this->login();
        $this->post('/mailings/send-next/' . $mailingId);

        $this->assertResponseCode(403);
        $this->assertNoMailSent();
    }

    public function testIndexShowsRecap(): void
    {
        $mailingId = $this->addMailing(['a@example.com', 'b@example.com']);
        $this->login();
        $this->get('/mailings');

        $this->assertResponseOk();
        $this->assertResponseContains('Hello {first_name}');
        $this->assertResponseContains('Juniors');
        $this->assertResponseContains('0 / 2');
        $this->assertResponseContains('2 pending');
        $this->assertSame(['total' => 2, 'pending' => 2, 'sent' => 0, 'invalid' => 0], $this->viewVariable('counts')[$mailingId]);
    }

    public function testAddListsOnlyActiveTeams(): void
    {
        $this->addTeam(2, 'Juniors');
        $this->addTeam(3, 'Old team', false);
        $this->login();
        $this->get('/mailings/add');

        $this->assertResponseOk();
        $this->assertSame([2 => 'Juniors', 1 => 'Lorem ipsum dolor sit amet'], $this->viewVariable('teams'));
        $this->assertResponseNotContains('Old team');
        $this->assertResponseContains('{first_name}');
    }

    public function testAddQueuesActiveMembersOnce(): void
    {
        $this->addTeam(2, 'Juniors');
        $this->addTeam(3, 'Seniors');
        $this->addTeam(4, 'Old team', false);
        $this->addMember(10, [2, 3], 'both@example.com');
        $this->addMember(11, [3], 'senior@example.com');
        $this->addMember(12, [2], 'inactive@example.com', false);
        $this->addMember(13, [2], '');
        $this->addMember(14, [4], 'old@example.com');
        $this->login();
        $this->post('/mailings/add', ['title' => 'Training', 'content' => 'No training', 'team_ids' => [2, 3, 4]]);

        $mailing = $this->getTableLocator()->get('Mailings')->find()->firstOrFail();
        $this->assertRedirect(['controller' => 'Mailings', 'action' => 'send', $mailing->id]);
        $this->assertSame('Training', $mailing->title);
        $this->assertSame('Juniors, Seniors', $mailing->teams);
        $this->assertSame('2,3', $mailing->team_id_list);
        $this->assertSame(Mailing::IN_PROGRESS, $mailing->status);
        $items = $this->items($mailing->id);
        $this->assertSame([10, 11], array_map(fn ($item) => $item->member_id, $items));
        $this->assertSame(['both@example.com', 'senior@example.com'], array_map(fn ($item) => $item->email, $items));
        $this->assertSame([MailingItem::PENDING, MailingItem::PENDING], array_map(fn ($item) => $item->status, $items));
        $this->assertNoMailSent();
    }

    public function testAddFromSourceCopiesSubjectContentAndTeams(): void
    {
        $this->addTeam(3, 'Seniors');
        $mailingId = $this->addMailing(['a@example.com']);
        $this->getTableLocator()->get('Mailings')->updateAll(['team_id_list' => '2,3'], ['id' => $mailingId]);
        $this->login();
        $this->get('/mailings');
        $this->assertResponseContains('href="/mailings/add/' . $mailingId . '"');

        $this->get('/mailings/add/' . $mailingId);

        $this->assertResponseOk();
        $this->assertSame('Hello {first_name}', $this->viewVariable('mailing')->title);
        $this->assertSame("Dear {full_name}\nYour page: {my_page}", $this->viewVariable('mailing')->content);
        $this->assertSame([2, 3], $this->viewVariable('teamIds'));
        $this->assertResponseContains('value="Hello {first_name}"');
        $this->assertResponseContains('value="3" checked="checked"');
        $this->assertSame(1, $this->getTableLocator()->get('Mailings')->find()->count());
    }

    public function testAddFromOldSourceFindsTeamsByName(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        $this->getTableLocator()->get('Mailings')->updateAll(['team_id_list' => ''], ['id' => $mailingId]);
        $this->login();
        $this->get('/mailings/add/' . $mailingId);

        $this->assertResponseOk();
        $this->assertSame([2], $this->viewVariable('teamIds'));
    }

    public function testAddFromSourcePostCreatesANewMailing(): void
    {
        $sourceId = $this->addMailing(['a@example.com']);
        $this->login();
        $this->post('/mailings/add/' . $sourceId, ['title' => 'Copy', 'content' => 'Again', 'team_ids' => [2]]);

        $mailing = $this->getTableLocator()->get('Mailings')->find()->where(['id !=' => $sourceId])->firstOrFail();
        $this->assertRedirect(['controller' => 'Mailings', 'action' => 'send', $mailing->id]);
        $this->assertSame('Copy', $mailing->title);
        $this->assertSame('Hello {first_name}', $this->getTableLocator()->get('Mailings')->get($sourceId)->title);
    }

    public function testAddRequiresATeam(): void
    {
        $this->login();
        $this->post('/mailings/add', ['title' => 'Training', 'content' => 'No training']);

        $this->assertResponseOk();
        $this->assertResponseContains('Select at least one team');
        $this->assertSame(0, $this->getTableLocator()->get('Mailings')->find()->count());
    }

    public function testAddRequiresRecipients(): void
    {
        $this->addTeam(2, 'Empty team');
        $this->login();
        $this->post('/mailings/add', ['title' => 'Training', 'content' => 'No training', 'team_ids' => [2]]);

        $this->assertResponseOk();
        $this->assertResponseContains('There is no active member with an email in these teams');
        $this->assertSame(0, $this->getTableLocator()->get('Mailings')->find()->count());
    }

    public function testAddRequiresSubjectAndContent(): void
    {
        $this->addTeam(2, 'Juniors');
        $this->addMember(10, [2], 'a@example.com');
        $this->login();
        $this->post('/mailings/add', ['title' => '', 'content' => '', 'team_ids' => [2]]);

        $this->assertResponseOk();
        $this->assertSame(0, $this->getTableLocator()->get('Mailings')->find()->count());
        $this->assertNotEmpty($this->viewVariable('mailing')->getError('title'));
        $this->assertNotEmpty($this->viewVariable('mailing')->getError('content'));
    }

    public function testAddRejectsInvalidPlaceholders(): void
    {
        $this->addTeam(2, 'Juniors');
        $this->addMember(10, [2], 'a@example.com');
        $this->login();
        $this->post('/mailings/add', [
            'title' => 'Hello {firstname}',
            'content' => "Dear {first_name}\nSee {my_page and {last_name}} {full_name}",
            'team_ids' => [2],
        ]);

        $this->assertResponseOk();
        $this->assertSame(0, $this->getTableLocator()->get('Mailings')->find()->count());
        $errors = $this->viewVariable('mailing')->getErrors();
        $this->assertSame(['placeholders' => 'Invalid placeholders: {firstname}. Available placeholders: {first_name}, {last_name}, {full_name}, {my_page}'], $errors['title']);
        $this->assertStringStartsWith('Invalid placeholders: {my_page, }. ', $errors['content']['placeholders']);
        $this->assertResponseContains('Invalid placeholders: {firstname}');
    }

    public function testAddAcceptsValidPlaceholders(): void
    {
        $this->addTeam(2, 'Juniors');
        $this->addMember(10, [2], 'a@example.com');
        $this->login();
        $this->post('/mailings/add', [
            'title' => '{first_name} {last_name}',
            'content' => "Dear {full_name},\nYour page: {my_page}",
            'team_ids' => [2],
        ]);

        $this->assertSame(1, $this->getTableLocator()->get('Mailings')->find()->count());
    }

    public function testDeleteUnsentMailing(): void
    {
        $mailingId = $this->addMailing(['a@example.com', 'b@example.com']);
        $this->login();
        $this->get('/mailings/send/' . $mailingId);
        $this->assertResponseContains('/mailings/delete/' . $mailingId);

        $this->post('/mailings/delete/' . $mailingId);

        $this->assertRedirect(['controller' => 'Mailings', 'action' => 'index']);
        $this->assertFlashMessage('The mailing has been deleted.');
        $this->assertSame(0, $this->getTableLocator()->get('Mailings')->find()->count());
        $this->assertSame(0, $this->getTableLocator()->get('MailingItems')->find()->count());
    }

    public function testDeleteAfterFailedAttempt(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', ['className' => FailingTransport::class]);
        $this->login();
        $this->sendNext($mailingId);

        $this->post('/mailings/delete/' . $mailingId);

        $this->assertRedirect(['controller' => 'Mailings', 'action' => 'index']);
        $this->assertSame(0, $this->getTableLocator()->get('Mailings')->find()->count());
    }

    public function testDeleteRefusedOnceAnEmailIsSent(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        $this->login();
        $this->sendNext($mailingId);
        $this->get('/mailings/send/' . $mailingId);
        $this->assertResponseNotContains('/mailings/delete/' . $mailingId);

        $this->post('/mailings/delete/' . $mailingId);

        $this->assertRedirect(['controller' => 'Mailings', 'action' => 'send', $mailingId]);
        $this->assertFlashMessage('Emails of this mailing have already been sent, it cannot be deleted.');
        $this->assertSame(1, $this->getTableLocator()->get('Mailings')->find()->count());
        $this->assertSame(1, $this->getTableLocator()->get('MailingItems')->find()->count());
    }

    public function testDeleteForbiddenWithoutAdminOrEditAll(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        $this->getTableLocator()->get('Roles')->updateAll(['Admin' => 0, 'MemberEditAll' => 0], ['id' => 1]);
        $this->login();
        $this->post('/mailings/delete/' . $mailingId);

        $this->assertResponseCode(403);
        $this->assertSame(1, $this->getTableLocator()->get('Mailings')->find()->count());
    }

    public function testDeleteRequiresPost(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        $this->login();
        $this->get('/mailings/delete/' . $mailingId);

        $this->assertResponseCode(405);
        $this->assertSame(1, $this->getTableLocator()->get('Mailings')->find()->count());
    }

    public function testSendNextReplacesPlaceholders(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        $this->login();
        $result = $this->sendNext($mailingId);

        $this->assertSame(['total' => 1, 'pending' => 0, 'sent' => 1, 'invalid' => 0, 'done' => true, 'error' => null], $result);
        $this->assertMailCount(1);
        $this->assertMailSentTo('a@example.com');
        $this->assertMailSentFrom('club@example.com');
        $this->assertMailSubjectContains('My club - Hello First100');
        $this->assertMailContainsText('Dear First100 Last100');
        $this->assertMailContainsText('/members/my-page/' . sha1('member100'));
        $this->assertMailContainsHtml('Dear First100 Last100<br>');
        $this->assertMailContainsHtml('Your page: <a href="http://localhost/members/my-page/' . sha1('member100') . '">My page</a>');
    }

    public function testSendNextCreatesMissingHash(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        $members = $this->getTableLocator()->get('Members');
        $members->updateAll(['hash' => ''], ['id' => 100]);
        $this->login();
        $this->sendNext($mailingId);

        $hash = $members->get(100)->hash;
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $hash);
        $this->assertMailContainsHtml('<a href="http://localhost/members/my-page/' . $hash . '">My page</a>');
    }

    public function testSendNextKeepsHtmlContent(): void
    {
        $mailingId = $this->addMailing(['a@example.com'], 'Hi', "<p>Hello <b>{first_name}</b></p>\n<ul>\n<li>One</li>\n<li>Two</li>\n</ul>\nLine 1\nLine 2");
        $this->login();
        $this->sendNext($mailingId);

        $this->assertMailContainsHtml("<p>Hello <b>First100</b></p>\n<ul>\n<li>One</li>\n<li>Two</li>\n</ul>\nLine 1<br>\nLine 2");
        $this->assertMailContainsText("Hello First100\n\n- One\n- Two\n\nLine 1\nLine 2");
    }

    public function testSendNextSendsByChunksUntilDone(): void
    {
        $emails = array_map(fn ($i) => "member$i@example.com", range(1, MailingsController::CHUNK_SIZE + 2));
        $mailingId = $this->addMailing($emails);
        $this->login();

        $result = $this->sendNext($mailingId);
        $this->assertSame(MailingsController::CHUNK_SIZE, $result['sent']);
        $this->assertSame(2, $result['pending']);
        $this->assertFalse($result['done']);
        $this->assertMailCount(MailingsController::CHUNK_SIZE);
        $mailing = $this->getTableLocator()->get('Mailings')->get($mailingId);
        $this->assertSame(Mailing::IN_PROGRESS, $mailing->status);
        $this->assertNull($mailing->sentDate);

        $result = $this->sendNext($mailingId);
        $this->assertSame(MailingsController::CHUNK_SIZE + 2, $result['sent']);
        $this->assertSame(0, $result['pending']);
        $this->assertTrue($result['done']);
        $this->assertMailCount(MailingsController::CHUNK_SIZE + 2);
        $mailing = $this->getTableLocator()->get('Mailings')->get($mailingId);
        $this->assertSame(Mailing::DONE, $mailing->status);
        $this->assertNotNull($mailing->sentDate);

        // Nothing more is sent once done
        $result = $this->sendNext($mailingId);
        $this->assertTrue($result['done']);
        $this->assertMailCount(MailingsController::CHUNK_SIZE + 2);
    }

    public function testSendNextSkipsInvalidAddress(): void
    {
        $mailingId = $this->addMailing(['not an email', 'b@example.com']);
        $this->login();
        $result = $this->sendNext($mailingId);

        $this->assertSame(['total' => 2, 'pending' => 0, 'sent' => 1, 'invalid' => 1, 'done' => true, 'error' => null], $result);
        $this->assertMailCount(1);
        $this->assertMailSentTo('b@example.com');
        $items = $this->items($mailingId);
        $this->assertSame(MailingItem::INVALID, $items[0]->status);
        $this->assertSame('Invalid email address', $items[0]->error);

        $this->get('/mailings/send/' . $mailingId);
        $this->assertResponseOk();
        $this->assertResponseContains('not an email');
        $this->assertResponseContains('Invalid address');
    }

    public function testSendNextKeepsFailedEmailPendingAndResumes(): void
    {
        $mailingId = $this->addMailing(['a@example.com', 'b@example.com']);
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', ['className' => FailingTransport::class]);
        $this->login();

        $result = $this->sendNext($mailingId);
        $this->assertSame(FailingTransport::ERROR, $result['error']);
        $this->assertSame(0, $result['sent']);
        $this->assertSame(2, $result['pending']);
        $this->assertFalse($result['done']);
        // The chunk stops at the first error
        $items = $this->items($mailingId);
        $this->assertSame([1, 0], array_map(fn ($item) => $item->attempts, $items));
        $this->assertSame(FailingTransport::ERROR, $items[0]->error);
        $this->assertSame(Mailing::IN_PROGRESS, $this->getTableLocator()->get('Mailings')->get($mailingId)->status);

        $this->get('/mailings/send/' . $mailingId);
        $this->assertResponseContains('Pending (1 attempts)');
        $this->assertResponseContains('Resume sending');

        // Later, the server accepts the emails again: the failed one is sent after the others
        TestEmailTransport::replaceAllTransports();
        $result = $this->sendNext($mailingId);
        $this->assertSame(['total' => 2, 'pending' => 0, 'sent' => 2, 'invalid' => 0, 'done' => true, 'error' => null], $result);
        $this->assertMailSentToAt(0, 'b@example.com');
        $this->assertMailSentToAt(1, 'a@example.com');
        $this->assertNull($this->items($mailingId)[0]->error);
    }

    public function testSendPageListsRecipients(): void
    {
        $mailingId = $this->addMailing(['a@example.com', 'not an email', 'c@example.com']);
        $this->getTableLocator()->get('Members')->updateAll(['last_name' => 'Zed'], ['id' => 100]);
        $this->login();
        $this->sendNext($mailingId);
        $this->getTableLocator()->get('MailingItems')->updateAll(['status' => MailingItem::PENDING, 'sent' => null], ['member_id' => 102]);

        $this->get('/mailings/send/' . $mailingId);

        $this->assertResponseOk();
        $this->assertResponseContains('Recipients (3)');
        $this->assertSame([101, 102, 100], $this->viewVariable('items')->extract('member_id')->toList());
        $this->assertSame(['Invalid address', 'Pending', 'Sent'], $this->viewVariable('items')->map(fn ($item) => $item->statusLabel())->toList());
        $this->assertResponseContains('First100 Zed');
        $this->assertResponseContains('a@example.com');
        $this->assertResponseContains('c@example.com');
        $this->assertResponseContains('/members/view/102');
        // Only the invalid address is in the errors
        $this->assertCount(1, $this->viewVariable('failedItems')->toList());
    }

    public function testSendNextRequiresPost(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        $this->login();
        $this->get('/mailings/send-next/' . $mailingId);

        $this->assertResponseCode(405);
        $this->assertNoMailSent();
    }

    public function testSendPageShowsStartButton(): void
    {
        $mailingId = $this->addMailing(['a@example.com']);
        $this->login();
        $this->get('/mailings/send/' . $mailingId);

        $this->assertResponseOk();
        $this->assertResponseContains('Start sending');
        // The content is previewed with its line returns, isolated from the page
        $this->assertResponseContains('<iframe class="ml-preview" sandbox srcdoc="Dear {full_name}&lt;br&gt;');
        $this->assertResponseContains('data-url="/mailings/send-next/' . $mailingId . '"');
        $this->assertNoMailSent();
    }
}
