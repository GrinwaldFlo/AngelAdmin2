<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Bill;
use App\Model\Entity\Member;
use App\Model\Entity\Site;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\Bill
 */
class BillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        I18n::setLocale('en');
    }

    protected function tearDown(): void
    {
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    private function bill(array $fields = []): Bill
    {
        return new Bill($fields + [
            'id' => 5, 'paid' => false, 'canceled' => false, 'printed' => false, 'due_date' => Date::today(), 'state_id' => 0,
            'site' => new Site(['add_invoice_num' => 1000]),
            'member' => new Member(['hash' => str_repeat('a', 40)]),
        ], ['guard' => false]);
    }

    public function testStatus(): void
    {
        $cases = [
            [['canceled' => true, 'paid' => true], 'Canceled', 'dark'],
            [['paid' => true, 'printed' => true], 'Paid', 'success'],
            [['printed' => true, 'due_date' => Date::yesterday()], 'Late', 'danger'],
            [['printed' => true, 'due_date' => Date::today()], 'Sent', 'info'],
            [['due_date' => Date::yesterday()], 'Draft', 'warning'],
        ];
        foreach ($cases as [$fields, $string, $html]) {
            $bill = $this->bill($fields);
            $this->assertSame($string, $bill->StatusString);
            $this->assertSame($html, $bill->StatusHtml);
        }
    }

    public function testReferenceAndPaths(): void
    {
        $bill = $this->bill();

        $this->assertSame(1005, $bill->Reference);
        $this->assertSame(WWW_ROOT . 'img/members/' . str_repeat('a', 40) . '/Invoice_1005.pdf', $bill->BillPath);
        $this->assertSame('/img/members/' . str_repeat('a', 40) . '/Invoice_1005.pdf', $bill->BillUrl);
        $this->assertStringEndsWith('/QR_1005.svg', $bill->QRPath);
        $this->assertFalse($bill->BillExists);
    }

    public function testDeleteBillPdf(): void
    {
        $bill = $this->bill(['member' => new Member(['hash' => 'billtest00000000000000000000000000000000'])]);
        $bill->member->CheckFolder();
        file_put_contents($bill->BillPath, 'pdf');
        $this->assertTrue($bill->BillExists);

        $bill->DeleteBillPdf();
        $this->assertFalse($bill->BillExists);
        // Already deleted: nothing to do
        $bill->DeleteBillPdf();
        rmdir($bill->member->MemberPath);
        $this->assertFalse($bill->BillExists);
    }

    public function testState(): void
    {
        $this->assertSame([0 => 'Draft', 1 => 'Sent', 2 => 'Read'], $this->bill()->StateList);
        $this->assertSame('Sent', $this->bill(['state_id' => 1])->StateStr);
        $this->assertSame('', $this->bill(['state_id' => 9])->StateStr);
    }
}
