<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\MemberRegistrationComponent;
use App\Controller\MembersController;
use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\Http\ServerRequest;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;

/**
 * Emails are captured (EmailTrait), never sent
 *
 * @uses \App\Controller\Component\MemberRegistrationComponent
 */
class MemberRegistrationComponentTest extends TestCase
{
    use EmailTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Fields', 'app.FieldTypes', 'app.Registrations',
    ];

    private MembersController $Controller;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->loadRoutes();
        $this->getTableLocator()->get('Configurations')->insertQuery()->insert(['label', 'value'])
            ->values(['label' => 'email', 'value' => 'club@example.com'])
            ->values(['label' => 'clubName', 'value' => 'My club'])
            ->execute();
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'en', 'email' => 'm1@example.com'], ['id' => 1]);
        $this->getTableLocator()->get('Registrations')->deleteAll([]);
        $this->addMember(100, [1], ['registered' => 0]);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    /**
     * @param bool $logged false = registration form of a visitor
     */
    private function registration(bool $logged = true): MemberRegistrationComponent
    {
        $this->Controller = $this->controller(MembersController::class, $logged ? $this->identity(['MemberEditAll']) : null);

        return $this->Controller->MemberRegistration;
    }

    private function flashes(): array
    {
        return array_column($this->Controller->getRequest()->getSession()->read('Flash.flash') ?? [], 'element');
    }

    public function testRegisterMemberForm(): void
    {
        $member = $this->registration(false)->registerMember([], []);

        $this->assertTrue($member->isNew());
        $this->assertSame(1, $member->communication_method_id);
        $this->assertNoMailSent();
    }

    public function testRegisterMember(): void
    {
        $result = $this->registration(false)->registerMember($this->memberForm(['active' => 0, 'email' => 'new@example.com']), []);

        $this->assertSame('redirect_home', $result);
        $member = $this->getTableLocator()->get('Members')->find()->where(['first_name' => 'New'])->firstOrFail();
        $this->assertTrue($member->active);
        $this->assertTrue($member->checked);
        $this->assertEquals(Date::today(), $member->date_arrival);
        // Personal page to the member, notification to the club
        $this->assertMailCount(2);
        $this->assertMailSentTo('new@example.com');
        $this->assertMailContains('/members/my-page/' . $member->hash);
        $this->assertMailSentTo('club@example.com');
    }

    public function testRegisterKnownMemberResendsItsPage(): void
    {
        $result = $this->registration(false)->registerMember($this->memberForm(['first_name' => 'First100', 'last_name' => 'Last100', 'email' => 'm100@example.com']), []);

        $this->assertSame('redirect_index', $result);
        $this->assertMailCount(1);
        $this->assertMailSentTo('m100@example.com');
        $this->assertSame(1, $this->getTableLocator()->get('Members')->find()->where(['first_name' => 'First100'])->count());
    }

    public function testRegisterKnownNameWithAnotherEmail(): void
    {
        $result = $this->registration(false)->registerMember($this->memberForm(['first_name' => 'First100', 'last_name' => 'Last100', 'email' => 'other@example.com']), []);

        $this->assertSame('contact_info_needed', $result);
        $this->assertNoMailSent();
    }

    public function testRegisterInvalid(): void
    {
        $member = $this->registration(false)->registerMember($this->memberForm(['email' => 'wrong']), []);

        $this->assertArrayHasKey('email', $member->getErrors());
        $this->assertSame(['flash/error'], $this->flashes());
        $this->assertNoMailSent();
    }

    public function testProcessAgreementNeedsSignatures(): void
    {
        $registration = $this->registration();
        $child = $this->getTableLocator()->get('Members')->get(100);
        $child->date_birth = Date::today()->subYears(10);

        $this->assertFalse($registration->processAgreement($child, ['signatureMember' => ''], 2026));
        $this->assertFalse($registration->processAgreement($child, ['signatureMember' => 'sig'], 2026));
        $this->assertSame(0, $this->getTableLocator()->get('Registrations')->find()->count());
    }

    public function testProcessAgreementOfAnAdultWithoutParent(): void
    {
        $adult = $this->getTableLocator()->get('Members')->get(100);
        $adult->date_birth = Date::today()->subYears(30);

        $regId = $this->registration()->processAgreement($adult, ['signatureMember' => 'sig'], 2026);

        $this->assertGreaterThan(0, $regId);
        $this->assertSame(100, $this->getTableLocator()->get('Registrations')->get($regId)->member_id);
        $this->assertMailSentTo('club@example.com');
    }

    public function testValidateSignatures(): void
    {
        $this->Controller = $this->controller(MembersController::class, $this->identity(['MemberEditAll']));
        $this->Controller->saveSignature(100, 'sig', 'parent', 2026);
        $this->Controller->saveSignature(1, 'sig', 'parent', 2026);

        $pending = $this->Controller->MemberRegistration->validateSignatures(['member_id' => 100], ['year' => 2026]);

        $this->assertSame([1], $pending->all()->extract('member_id')->toList());
        $this->assertTrue($this->getTableLocator()->get('Members')->get(100)->registered);
        $this->assertSame(1, $this->getTableLocator()->get('Registrations')->find()->where(['member_id' => 100])->firstOrFail()->validation_id);
        $this->assertMailSentTo('m100@example.com');
    }

    public function testRefuseSignature(): void
    {
        $this->Controller = $this->controller(MembersController::class, $this->identity(['MemberEditAll']));
        $this->Controller->saveSignature(100, 'sig', 'parent', 2026);

        $pending = $this->Controller->MemberRegistration->validateSignatures(['member_id' => 100, 'Delete' => 1], ['year' => 2026]);

        $this->assertSame(0, $pending->count());
        $this->assertFalse($this->getTableLocator()->get('Members')->get(100)->registered);
        $this->assertMailSentTo('m100@example.com');
    }

    /**
     * Sent twice (double click, two tabs): the signature is already validated or deleted
     */
    public function testValidateSignatureAlreadyHandled(): void
    {
        $registration = $this->registration();

        $pending = $registration->validateSignatures(['member_id' => 100], ['year' => 2026]);

        $this->assertSame(0, $pending->count());
        $this->assertFalse($this->getTableLocator()->get('Members')->get(100)->registered);
        $this->assertNoMailSent();
    }

    public function testSendInvitations(): void
    {
        $count = $this->registration()->sendInvitations([100 => 1, 1 => 0], []);

        $this->assertSame(1, $count);
        $this->assertMailCount(1);
        $this->assertMailSentTo('m100@example.com');
    }

    public function testSendMyPageTitleDependsOnTheStatus(): void
    {
        $registration = $this->registration();
        $members = $this->getTableLocator()->get('Members');

        $registration->sendMyPageToMember($members->newEntity(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com', 'hash' => 'x', 'registered' => true, 'active' => true], ['validate' => false]));
        $registration->sendMyPageToMember($members->newEntity(['first_name' => 'A', 'last_name' => 'B', 'email' => 'b@example.com', 'hash' => 'x', 'registered' => false, 'active' => true], ['validate' => false]));
        $registration->sendMyPageToMember($members->newEntity(['first_name' => 'A', 'last_name' => 'B', 'email' => 'c@example.com', 'hash' => 'x', 'registered' => false, 'active' => false], ['validate' => false]));

        $this->assertMailSubjectContainsAt(0, 'Your personal page');
        $this->assertMailSubjectContainsAt(1, 'Action needed');
        $this->assertMailSubjectContainsAt(2, 'Want to come back');
    }

    public function testLogSecurityEvent(): void
    {
        Log::setConfig('registrationTest', ['className' => 'Array', 'scopes' => ['security'], 'levels' => ['warning']]);

        $this->registration()->logSecurityEvent('Bad hash', 'abc', 'Not found', new ServerRequest(['environment' => ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'Test']]));

        $logs = Log::engine('registrationTest')->read();
        Log::drop('registrationTest');
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('Bad hash', $logs[0]);
        $this->assertStringContainsString('"ip":"10.0.0.1"', $logs[0]);
        $this->assertStringContainsString('"hash":"abc"', $logs[0]);
    }
}
