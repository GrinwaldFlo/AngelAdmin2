<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Filesystem;

/**
 * Complete delete of a member (Members/delete) and of an invoice (Bills/delete): admins only
 * Member 1 (logged, user 1) has the invoice 10, member 100 (team 1) has no invoice, member 101 has the invoice 11
 *
 * @uses \App\Controller\MembersController::delete()
 * @uses \App\Controller\BillsController::delete()
 */
class AdminDeleteTest extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Meetings', 'app.Presences', 'app.Bills', 'app.Fields', 'app.FieldTypes',
        'app.Registrations', 'app.MemberDocs', 'app.ShopItems', 'app.ShopStocks', 'app.MemberOrders',
    ];

    private string $folder;

    private string $folder101;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->getTableLocator()->get('Bills')->deleteAll([]);
        $this->addMember(100, [1]);
        $this->addMember(101, [1]);
        $this->addBill(10, 1);
        $this->addBill(11, 101);
        $this->folder = WWW_ROOT . 'img/members/' . sha1('member100') . '/';
        $this->folder101 = WWW_ROOT . 'img/members/' . sha1('member101') . '/';
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        foreach ([$this->folder, $this->folder101] as $folder) {
            if (is_dir($folder)) {
                (new Filesystem())->deleteDir($folder);
            }
        }
        parent::tearDown();
    }

    private function memberExists(int $id): bool
    {
        return $this->getTableLocator()->get('Members')->exists(['id' => $id]);
    }

    private function countRows(string $table, array $conditions): int
    {
        return $this->getTableLocator()->get($table)->find()->where($conditions)->count();
    }

    /**
     * Member 100 with a presence, a user account, a coach link, a field, a shop order and a folder
     */
    private function addMemberLinks(): void
    {
        $this->getTableLocator()->get('Presences')->insertQuery()
            ->insert(['meeting_id', 'member_id', 'state'])
            ->values(['meeting_id' => 1, 'member_id' => 100, 'state' => 1])
            ->execute();
        $this->addUser(100, 100);
        $this->addCoach(1, 100);
        $this->getTableLocator()->get('Fields')->insertQuery()
            ->insert(['member_id', 'field_type_id', 'value'])
            ->values(['member_id' => 100, 'field_type_id' => 1, 'value' => 'x'])
            ->execute();
        $this->getTableLocator()->get('MemberOrders')->insertQuery()
            ->insert(['shop_item_id', 'member_id', 'quantity', 'delivered'])
            ->values(['shop_item_id' => $this->addShopItem('Shirt'), 'member_id' => 100, 'quantity' => 1, 'delivered' => false])
            ->execute();
        mkdir($this->folder, 0755, true);
        file_put_contents($this->folder . '100_portrait_300.jpg', 'x');
    }

    public function testAdminDeletesMemberWithItsLinks(): void
    {
        $this->addMemberLinks();
        $this->loginWith(['Admin', 'MemberViewAll', 'MemberEditAll']);

        $this->post('/members/delete/100');

        $this->assertRedirect(['controller' => 'Members', 'action' => 'index']);
        $this->assertFlashMessage('The member First100 Last100 has been deleted.');
        $this->assertFalse($this->memberExists(100));
        foreach (['Presences', 'TeamsMembers', 'Users', 'TeamsCoaches', 'Fields', 'MemberOrders'] as $table) {
            $this->assertSame(0, $this->countRows($table, ['member_id' => 100]), $table);
        }
        $this->assertDirectoryDoesNotExist($this->folder);
        // The other members keep their teams
        $this->assertSame(1, $this->countRows('TeamsMembers', ['member_id' => 101]));
    }

    public function testMemberWithInvoicesIsNotDeleted(): void
    {
        $this->loginWith(['Admin', 'MemberViewAll', 'MemberEditAll']);

        $this->post('/members/delete/101');

        $this->assertRedirect(['controller' => 'Members', 'action' => 'view', 101]);
        $this->assertFlashMessage('A member with invoices cannot be deleted.');
        $this->assertTrue($this->memberExists(101));
        $this->assertSame(1, $this->countRows('TeamsMembers', ['member_id' => 101]));
    }

    public function testAdminCantDeleteOwnMember(): void
    {
        $this->getTableLocator()->get('Bills')->deleteAll(['member_id' => 1]);
        $this->loginWith(['Admin', 'MemberViewAll', 'MemberEditAll']);

        $this->post('/members/delete/1');

        $this->assertRedirect(['controller' => 'Members', 'action' => 'view', 1]);
        $this->assertFlashMessage("You can't delete your own member.");
        $this->assertTrue($this->memberExists(1));
    }

    public function testDeleteMemberForbiddenWithoutAdmin(): void
    {
        $this->loginWith(['MemberViewAll', 'MemberEditAll', 'BillViewAll', 'BillEditAll']);

        $this->post('/members/delete/100');

        $this->assertResponseCode(403);
        $this->assertTrue($this->memberExists(100));
    }

    public function testDeleteMemberNeedsPost(): void
    {
        $this->loginWith(['Admin', 'MemberViewAll', 'MemberEditAll']);

        $this->get('/members/delete/100');

        $this->assertResponseCode(405);
        $this->assertTrue($this->memberExists(100));
    }

    public function testDeleteButtonOnlyForAdminAndMemberWithoutInvoice(): void
    {
        $this->loginWith(['Admin', 'MemberViewAll', 'MemberEditAll']);
        $this->get('/members/view/100');
        $this->assertResponseOk();
        $this->assertResponseContains('/members/delete/100');

        $this->get('/members/view/101');
        $this->assertResponseOk();
        $this->assertResponseNotContains('/members/delete/101');

        $this->loginWith(['MemberViewAll', 'MemberEditAll']);
        $this->get('/members/view/100');
        $this->assertResponseOk();
        $this->assertResponseNotContains('/members/delete/100');
    }

    public function testAdminDeletesInvoiceAndKeepsTheOrder(): void
    {
        $itemId = $this->addShopItem('Cap');
        $this->getTableLocator()->get('MemberOrders')->insertQuery()
            ->insert(['id', 'shop_item_id', 'member_id', 'bill_id', 'quantity', 'delivered'])
            ->values(['id' => 1, 'shop_item_id' => $itemId, 'member_id' => 101, 'bill_id' => 11, 'quantity' => 1, 'delivered' => false])
            ->execute();
        $pdf = $this->getTableLocator()->get('Bills')->get(11, contain: ['Members', 'Sites'])->BillPath;
        mkdir(dirname($pdf), 0755, true);
        file_put_contents($pdf, 'x');
        $this->loginWith(['Admin', 'BillViewAll', 'BillEditAll']);

        $this->post('/bills/delete/11');
        $this->assertFileDoesNotExist($pdf);

        $this->assertRedirect(['controller' => 'Members', 'action' => 'view', 101]);
        $this->assertFalse($this->getTableLocator()->get('Bills')->exists(['id' => 11]));
        $order = $this->getTableLocator()->get('MemberOrders')->get(1);
        $this->assertNull($order->bill_id);
        // Without invoice, the member can be deleted
        $this->post('/members/delete/101');
        $this->assertFalse($this->memberExists(101));
    }

    public function testDeleteInvoiceForbiddenWithoutAdmin(): void
    {
        $this->loginWith(['BillViewAll', 'BillEditAll', 'MemberViewAll']);

        $this->post('/bills/delete/11');

        $this->assertResponseCode(403);
        $this->assertTrue($this->getTableLocator()->get('Bills')->exists(['id' => 11]));
    }

    public function testDeleteInvoiceButtonOnlyForAdmin(): void
    {
        $this->loginWith(['Admin', 'BillViewAll']);
        $this->get('/bills/view/11');
        $this->assertResponseOk();
        $this->assertResponseContains('/bills/delete/11');

        $this->loginWith(['BillViewAll', 'BillEditAll']);
        $this->get('/bills/view/11');
        $this->assertResponseOk();
        $this->assertResponseNotContains('/bills/delete/11');
    }
}
