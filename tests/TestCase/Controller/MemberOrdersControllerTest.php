<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Model\Table\ShopItemsTable;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Orders: 1 shirt (equipment, bill 10 paid, delivered), 2 trip (travel, bill 11 open), 3 cap (equipment, no bill)
 *
 * @uses \App\Controller\MemberOrdersController
 */
class MemberOrdersControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.ShopItems', 'app.MemberOrders',
    ];

    private const MANAGER = ['MemberViewAll', 'MemberEditAll', 'MemberEditOwn'];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $this->getTableLocator()->get('Bills')->deleteAll([]);
        $this->addBill(10, 1, ['paid' => true]);
        $this->addBill(11, 1);
        $this->addOrder(1, 'Shirt', ShopItemsTable::CATEGORY_EQUIPMENT, 10, true);
        $this->addOrder(2, 'Trip', ShopItemsTable::CATEGORY_TRAVEL, 11);
        $this->addOrder(3, 'Cap', ShopItemsTable::CATEGORY_EQUIPMENT, null);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function addOrder(int $id, string $label, int $category, ?int $billId, bool $delivered = false): void
    {
        $items = $this->getTableLocator()->get('ShopItems');
        $item = $items->saveOrFail($items->newEntity(['label' => $label, 'price' => 10, 'category' => $category]));
        $this->getTableLocator()->get('MemberOrders')->insertQuery()
            ->insert(['id', 'shop_item_id', 'member_id', 'bill_id', 'quantity', 'delivered'])
            ->values(['id' => $id, 'shop_item_id' => $item->id, 'member_id' => 1, 'bill_id' => $billId, 'quantity' => 1, 'delivered' => $delivered])
            ->execute();
    }

    /**
     * @return int[] ids of the listed orders
     */
    private function listedIds(string $url): array
    {
        $this->get($url);
        $this->assertResponseOk();
        $ids = [];
        foreach ($this->viewVariable('memberOrders') as $order) {
            $ids[] = $order->id;
        }
        sort($ids);

        return $ids;
    }

    private function delivered(int $id): bool
    {
        return $this->getTableLocator()->get('MemberOrders')->get($id)->delivered;
    }

    public function testIndexRequiresLogin(): void
    {
        $this->get('/member-orders');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexAll(): void
    {
        $this->loginWith(self::MANAGER);

        $this->assertSame([1, 2, 3], $this->listedIds('/member-orders'));
        $this->assertResponseContains('Shirt');
        $this->assertSame(ShopItemsTable::getCategoryOptions(), $this->viewVariable('categoryOptions'));
    }

    public function testIndexFilters(): void
    {
        $this->loginWith(self::MANAGER);
        $category = ShopItemsTable::CATEGORY_EQUIPMENT;

        $this->assertSame([1, 3], $this->listedIds("/member-orders/index/$category/0/0"));
        $this->assertSame([1], $this->listedIds('/member-orders/index/0/1/0'));
        $this->assertSame([2, 3], $this->listedIds('/member-orders/index/0/2/0'));
        $this->assertSame([1], $this->listedIds('/member-orders/index/0/0/1'));
        // Orders without bill are not paid either
        $this->assertSame([2, 3], $this->listedIds('/member-orders/index/0/0/2'));
        $this->assertSame([3], $this->listedIds("/member-orders/index/$category/2/2"));
    }

    public function testIndexKeepsFiltersInSession(): void
    {
        $this->loginWith(self::MANAGER);
        $this->get('/member-orders/index/0/2/0');
        $this->assertSession(2, 'memberOrdersDeliveredFilter');

        $this->loginWith(self::MANAGER, ['memberOrdersDeliveredFilter' => 2]);
        $this->assertSame([2, 3], $this->listedIds('/member-orders'));
        $this->assertSame(2, $this->viewVariable('deliveredFilter'));
    }

    public function testForbiddenWithoutEditAllOrForCoaches(): void
    {
        foreach ([['MemberViewAll', 'MemberEditOwn'], [...self::MANAGER, 'TeamScoped']] as $rights) {
            $this->loginWith($rights);
            foreach (['/member-orders', '/member-orders/view/1'] as $url) {
                $this->get($url);
                $this->assertResponseCode(403, $url);
            }
            foreach (['/member-orders/mark-delivered/2', '/member-orders/mark-not-delivered/1', '/member-orders/delete/1'] as $url) {
                $this->post($url);
                $this->assertResponseCode(403, $url);
            }
        }
        $this->assertTrue($this->delivered(1));
        $this->assertFalse($this->delivered(2));
    }

    public function testView(): void
    {
        $this->loginWith(self::MANAGER);

        $this->get('/member-orders/view/2');

        $this->assertResponseOk();
        $this->assertResponseContains('Trip');
        $this->assertSame(11, $this->viewVariable('memberOrder')->bill->id);
    }

    public function testMarkDeliveredAndNotDelivered(): void
    {
        $this->loginWith(self::MANAGER);

        $this->post('/member-orders/mark-delivered/2');
        $this->assertRedirect(['controller' => 'MemberOrders', 'action' => 'index']);
        $this->assertFlashElement('flash/success');
        $this->assertTrue($this->delivered(2));

        $this->post('/member-orders/mark-not-delivered/2');
        $this->assertRedirect(['controller' => 'MemberOrders', 'action' => 'index']);
        $this->assertFalse($this->delivered(2));
    }

    public function testDelete(): void
    {
        $this->loginWith(self::MANAGER);

        $this->post('/member-orders/delete/3');

        $this->assertRedirect(['controller' => 'MemberOrders', 'action' => 'index']);
        $this->assertFlashElement('flash/success');
        $this->assertFalse($this->getTableLocator()->get('MemberOrders')->exists(['id' => 3]));
        // The item stays
        $this->assertSame(3, $this->getTableLocator()->get('ShopItems')->find()->count());
    }

    public function testChangesRequirePost(): void
    {
        $this->loginWith(self::MANAGER);

        foreach (['/member-orders/mark-delivered/2', '/member-orders/mark-not-delivered/1', '/member-orders/delete/1'] as $url) {
            $this->get($url);
            $this->assertResponseCode(405, $url);
        }
        $this->assertTrue($this->delivered(1));
        $this->assertFalse($this->delivered(2));
    }
}
