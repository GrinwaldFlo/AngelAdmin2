<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\AppController;
use App\Controller\Component\MailComponent;
use App\Controller\PrefResult;
use App\Model\Entity\Mailing;
use App\Model\Entity\MailingItem;
use App\Test\TestSuite\FailingTransport;
use App\Test\TestSuite\TestDataTrait;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;

/**
 * Emails are captured (EmailTrait), never sent. Members 100 and 101 receive the mailing.
 *
 * @uses \App\Controller\Component\MailComponent
 */
class MailComponentTest extends TestCase
{
    use EmailTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Mailings', 'app.MailingItems',
    ];

    private MailComponent $Mail;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->loadRoutes();
        $this->getTableLocator()->get('Configurations')->insertQuery()->insert(['label', 'value'])
            ->values(['label' => 'email', 'value' => 'club@example.com'])
            ->values(['label' => 'emailName', 'value' => 'Club admin'])
            ->values(['label' => 'clubName', 'value' => 'My club'])
            ->execute();
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'en'], ['id' => 1]);
        $this->addTeam(2, 'Juniors');
        $this->addMember(100, [2], ['email' => 'a@example.com']);
        $this->addMember(101, [2], ['email' => 'b@example.com']);
        $this->Mail = new MailComponent($this->controller(AppController::class, $this->identity(['Admin']))->components());
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function mailing(string $title = 'Hello {first_name}'): Mailing
    {
        $mailings = $this->getTableLocator()->get('Mailings');
        $mailing = $mailings->newForTeams(['title' => $title, 'content' => 'Dear {full_name}'], [2]);

        return $mailings->saveOrFail($mailing);
    }

    public function testMailNotifToTheClub(): void
    {
        $this->Mail->mailNotif('Wrong password', 'Details', null);

        $this->assertMailCount(1);
        $this->assertMailSentTo('club@example.com');
        $this->assertMailSentFrom('club@example.com');
        $this->assertMailSubjectContains('Wrong password');
    }

    public function testMailNotifAboutAMember(): void
    {
        $this->Mail->mailNotif('New member', 'Details', $this->getTableLocator()->get('Members')->get(100));

        $this->assertMailSubjectContains('New member - First100 Last100');
        $this->assertMailContains('/members/view/100');
    }

    public function testMailToMember(): void
    {
        $this->Mail->mailToMember('Signature approved', 'Your signature has been approved', $this->getTableLocator()->get('Members')->get(100));

        $this->assertMailSentTo('a@example.com');
        $this->assertMailSubjectContains('My club - Signature approved');
        $this->assertMailContainsText('Dear First100 Last100');

        $this->Mail->mailToMember('Nobody', 'x', null);
        $this->assertMailCount(1);
    }

    public function testSendMailingByChunks(): void
    {
        $mailing = $this->mailing();

        $result = $this->Mail->sendMailing($mailing, 1);
        $this->assertSame(['total' => 2, 'pending' => 1, 'sent' => 1, 'invalid' => 0, 'done' => false, 'error' => null], $result);
        $this->assertMailSubjectContainsAt(0, 'My club - Hello First100');

        $result = $this->Mail->sendMailing($mailing, 10);
        $this->assertTrue($result['done']);
        $this->assertSame(Mailing::DONE, $this->getTableLocator()->get('Mailings')->get($mailing->id)->status);
        $this->assertMailCount(2);
    }

    public function testSendMailingSkipsInvalidAddress(): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['email' => 'wrong'], ['id' => 101]);
        $mailing = $this->mailing();

        $result = $this->Mail->sendMailing($mailing, 10);

        $this->assertSame(1, $result['sent']);
        $this->assertSame(1, $result['invalid']);
        $this->assertTrue($result['done']);
        $this->assertMailCount(1);
    }

    public function testSendMailingKeepsFailedEmailPending(): void
    {
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', ['className' => FailingTransport::class]);
        $mailing = $this->mailing();

        $result = $this->Mail->sendMailing($mailing, 10);

        $this->assertSame(FailingTransport::ERROR, $result['error']);
        $this->assertSame(2, $result['pending']);
        $this->assertFalse($result['done']);
        $item = $this->getTableLocator()->get('MailingItems')->find()->where(['mailing_id' => $mailing->id, 'status' => MailingItem::PENDING])->orderBy(['attempts' => 'DESC'])->firstOrFail();
        $this->assertSame(1, $item->attempts);
    }

    /**
     * The name is typed by the member, the content may contain links
     */
    public function testMailToMemberEscapesTheName(): void
    {
        $member = $this->getTableLocator()->get('Members')->get(100);
        $member->first_name = '<b>Ana</b> &';

        $this->Mail->mailToMember('Hello', 'See <a href="https://example.com">your page</a>', $member);

        $this->assertMailContainsHtml('&lt;b&gt;Ana&lt;/b&gt; &amp;');
        $this->assertMailContainsHtml('<a href="https://example.com">your page</a>');
        $this->assertMailContainsText('Dear <b>Ana</b> & Last100');
    }
}
