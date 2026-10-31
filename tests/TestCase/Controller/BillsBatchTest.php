<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Batch pages of the bills (site 1). Members 1 and 100 in team 1.
 * Bills: 10 draft of member 1, 11 late and printed of member 100, 12 paid.
 *
 * @uses \App\Controller\BillsController
 */
class BillsBatchTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.BillTemplates',
    ];

    private const EDITOR = ['BillViewAll', 'BillEditAll', 'MemberViewAll'];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->loadRoutes();
        $this->getTableLocator()->get('Bills')->deleteAll([]);
        $this->getTableLocator()->get('Sites')->updateAll(['reminder_penalty' => 5, 'sender_email' => 'club@example.com'], ['id' => 1]);
        $this->getTableLocator()->get('Members')->updateAll(['email' => 'm1@example.com'], ['id' => 1]);
        $this->addMember(100, [1]);
        $this->addBill(10, 1, ['label' => 'Draft fee', 'due_date' => '2099-01-01']);
        $this->addBill(11, 100, ['label' => 'Late fee', 'due_date' => '2020-01-01', 'printed' => true]);
        $this->addBill(12, 1, ['label' => 'Paid fee', 'paid' => true]);
    }

    protected function tearDown(): void
    {
        foreach ([1, 100] as $memberId) {
            $folder = $this->getTableLocator()->get('Members')->get($memberId)->MemberPath;
            foreach (glob($folder . '*') ?: [] as $file) {
                unlink($file);
            }
            @rmdir($folder);
        }
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function bill(int $id)
    {
        return $this->getTableLocator()->get('Bills')->get($id, contain: ['Members', 'Sites']);
    }

    public function testBatchPagesForbiddenWithoutBillEditAll(): void
    {
        $this->loginWith(['BillViewAll', 'MemberViewAll']);

        foreach (['batch-validation', 'generate-reminder', 'batch-add', 'mail-send', 'mail-print', 'pdf/10'] as $page) {
            $this->get('/bills/' . $page);
            $this->assertResponseCode(403, $page);
        }
        $this->post('/bills/batch-validation', ['10' => 1]);
        $this->assertResponseCode(403);
        $this->assertFalse($this->bill(10)->paid);
    }

    public function testSimple(): void
    {
        $this->loginWith(self::EDITOR);

        $this->get('/bills/simple');

        $this->assertResponseOk();
    }

    public function testBatchValidation(): void
    {
        $this->loginWith(self::EDITOR);

        $this->get('/bills/batch-validation');
        $this->assertResponseOk();
        $this->assertSame([10, 11], $this->viewVariable('bills')->all()->extract('id')->toList());

        $this->post('/bills/batch-validation', ['10' => 1, '11' => 0]);

        $this->assertRedirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
        $this->assertTrue($this->bill(10)->paid);
        $this->assertFalse($this->bill(11)->paid);
    }

    public function testGenerateReminder(): void
    {
        $this->loginWith(self::EDITOR);

        $this->get('/bills/generate-reminder/0');
        $this->assertResponseOk();
        $this->assertSame([11], $this->viewVariable('bills')->all()->extract('id')->toList());

        $this->post('/bills/generate-reminder/0', ['Penalty' => 1, 'BillId' => ['11' => 1]]);

        $this->assertRedirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
        $bill = $this->bill(11);
        $this->assertSame(1, $bill->reminder);
        $this->assertSame(15, $bill->amount);
        $this->assertFalse($bill->printed);
    }

    public function testGenerateReminderWithoutPenalty(): void
    {
        $this->loginWith(self::EDITOR);

        $this->post('/bills/generate-reminder/0', ['BillId' => ['11' => 1]]);

        $this->assertSame(10, $this->bill(11)->amount);
        $this->assertSame(1, $this->bill(11)->reminder);
    }

    public function testBatchAdd(): void
    {
        $this->loginWith(self::EDITOR);

        $this->get('/bills/batch-add');
        $this->assertResponseOk();

        $this->post('/bills/batch-add', [
            'siteId' => 1, 'due_date' => '2026-12-31', 'link_membership_fee' => 0, 'amount' => 25, 'label' => 'Camp',
            'MemberId' => ['1' => 1, '100' => 1],
        ]);

        $this->assertRedirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
        $bills = $this->getTableLocator()->get('Bills')->find()->where(['label' => 'Camp'])->all();
        $this->assertSame([1, 100], $bills->extract('member_id')->toList());
        $this->assertSame([25, 25], $bills->extract('amount')->toList());
    }

    public function testBatchAddNeedsASite(): void
    {
        $this->loginWith(self::EDITOR);

        $this->post('/bills/batch-add', ['siteId' => 9, 'amount' => 25, 'label' => 'Camp', 'MemberId' => ['1' => 1]]);

        $this->assertRedirect(['action' => 'batchAdd']);
        $this->assertFlashElement('flash/error');
        $this->assertFalse($this->getTableLocator()->get('Bills')->exists(['label' => 'Camp']));
    }

    public function testBatchAddReportsAnInvalidBill(): void
    {
        $this->loginWith(self::EDITOR);

        $this->post('/bills/batch-add', ['siteId' => 1, 'due_date' => '2026-12-31', 'link_membership_fee' => 0, 'amount' => 'abc', 'label' => 'Camp', 'MemberId' => ['1' => 1]]);

        $this->assertFlashElement('flash/error');
        $this->assertFalse($this->getTableLocator()->get('Bills')->exists(['label' => 'Camp']));
    }

    public function testMailSendNeedsTheCheckbox(): void
    {
        $this->loginWith(self::EDITOR);

        $this->post('/bills/mail-send', ['BillId' => ['10' => 1]]);

        $this->assertFlashElement('flash/error');
        $this->assertNoMailSent();
    }

    public function testMailSendWithThePdf(): void
    {
        $bill = $this->bill(10);
        $bill->member->CheckFolder();
        file_put_contents($bill->BillPath, '%PDF-1.4');
        $this->loginWith(self::EDITOR);

        $this->post('/bills/mail-send', ['AcceptSend' => 1, 'BillId' => ['10' => 1, '11' => 1]]);

        $this->assertRedirect(['controller' => 'Bills', 'action' => 'index']);
        $this->assertMailCount(1);
        $this->assertMailSentTo('m1@example.com');
        $this->assertMailContainsAttachment(basename($bill->BillPath));
        $this->assertTrue($this->bill(10)->printed);
        // Bill 11 has no PDF
        $this->assertFlashElementAt(0, 'flash/error');
    }

    public function testMailPrintRedirectsToThePdfs(): void
    {
        $this->loginWith(self::EDITOR);

        $this->get('/bills/mail-print');
        $this->assertResponseOk();

        $this->post('/bills/mail-print', ['memberStatus' => 1, 'teamId' => 0, 'siteId' => 1, 'MarkSent' => 0]);
        $this->assertRedirect(['controller' => 'Bills', 'action' => 'pdfs', 1, 0, 1, 0]);
    }

    public function testPdfsNeedASite(): void
    {
        $this->loginWith(self::EDITOR);

        $this->get('/bills/pdfs/1/0/9');

        $this->assertRedirect(['action' => 'mailPrint']);
    }
}
