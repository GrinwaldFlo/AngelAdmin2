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
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.ShopItems', 'app.MemberOrders', 'app.ShopStocks',
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
        $ids = $this->orderedIds($url);
        sort($ids);

        return $ids;
    }

    /**
     * @return int[] ids of the listed orders, in the listed order
     */
    private function orderedIds(string $url): array
    {
        $this->get($url);
        $this->assertResponseOk();
        $ids = [];
        foreach ($this->viewVariable('memberOrders') as $order) {
            $ids[] = $order->id;
        }

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

    public function testIndexSortsOnEveryColumn(): void
    {
        $this->loginWith(self::MANAGER);
        $orders = $this->getTableLocator()->get('MemberOrders');
        $orders->updateAll(['quantity' => 5], ['id' => 2]);
        $orders->updateAll(['created' => '2026-03-01 10:00:00'], ['id' => 3]);
        $orders->updateAll(['created' => '2026-01-01 10:00:00'], ['id !=' => 3]);

        $expected = [
            'id' => [3, 2, 1],
            'ShopItems.label' => [2, 1, 3],
            'Bills.id' => [2, 1, 3],
        ];
        foreach ($expected as $field => $ids) {
            $this->assertSame($ids, $this->orderedIds("/member-orders/index/0/0/0?sort=$field&direction=desc"), $field);
        }

        // Several orders share these values: only the first one is decisive
        $expectedFirst = [
            'ShopItems.category' => [2, 'asc'],
            'quantity' => [2, 'desc'],
            'delivered' => [1, 'desc'],
            'created' => [3, 'desc'],
        ];
        foreach ($expectedFirst as $field => [$id, $direction]) {
            $this->assertSame($id, $this->orderedIds("/member-orders/index/0/0/0?sort=$field&direction=$direction")[0], $field);
        }
    }

    public function testIndexSortsOnMember(): void
    {
        $this->loginWith(self::MANAGER);
        $this->getTableLocator()->get('Members')->updateAll(['first_name' => 'Adam'], ['id' => 1]);
        $this->addMember(5, [], ['first_name' => 'Zoe']);
        $this->getTableLocator()->get('MemberOrders')->updateAll(['member_id' => 5], ['id' => 2]);

        $this->assertSame(2, $this->orderedIds('/member-orders/index/0/0/0?sort=Members.first_name&direction=desc')[0]);
    }

    public function testFiltersKeepTheSort(): void
    {
        $this->loginWith(self::MANAGER);

        $this->get('/member-orders/index/0/0/0?sort=ShopItems.label&direction=desc');

        $this->assertResponseContains('/member-orders/index/0/2/0/0?sort=ShopItems.label&amp;direction=desc');
    }

    public function testIndexShowsTheTotalOfAllPages(): void
    {
        $this->loginWith(self::MANAGER);

        $this->get('/member-orders/index/0/0/0?limit=2');

        $this->assertCount(2, $this->viewVariable('memberOrders'));
        $this->assertResponseContains('<span class="mo-count">(3)</span>');
    }

    public function testForbiddenWithoutEditAllOrForCoaches(): void
    {
        foreach ([['MemberViewAll', 'MemberEditOwn'], [...self::MANAGER, 'TeamScoped']] as $rights) {
            $this->loginWith($rights);
            foreach (['/member-orders', '/member-orders/view/1'] as $url) {
                $this->get($url);
                $this->assertResponseCode(403, $url);
            }
            foreach (['/member-orders/mark-delivered/2', '/member-orders/mark-not-delivered/1'] as $url) {
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

    /**
     * The orders are deleted on the orders page only (ShopOrdersController)
     */
    public function testNoDeleteOnThisPage(): void
    {
        $this->loginWith(self::MANAGER);

        $this->get('/member-orders');
        $this->assertResponseNotContains('/member-orders/delete/');
        $this->assertResponseNotContains('/shop-orders/delete/');
        $this->post('/member-orders/delete/3');

        $this->assertResponseCode(404);
        $this->assertTrue($this->getTableLocator()->get('MemberOrders')->exists(['id' => 3]));
    }

    public function testIndexShowsTheAttributeAndFiltersByItem(): void
    {
        $orders = $this->getTableLocator()->get('MemberOrders');
        $orders->updateAll(['attribute' => 'XL'], ['id' => 1]);
        $shirtId = $orders->get(1)->shop_item_id;
        $this->loginWith(self::MANAGER);

        $this->assertSame([1], $this->listedIds("/member-orders/index/0/0/0/$shirtId"));
        $this->assertResponseContains('XL');
        $this->assertSession($shirtId, 'memberOrdersItemFilter');
        $this->assertSame([1, 2, 3], $this->listedIds('/member-orders/index/0/0/0/0'));
    }

    public function testOnlyTheOrdersOfTheChosenLocation(): void
    {
        $this->addSite(2, 'Lausanne');
        $itemId = $this->addShopItem('Lausanne cap', ['site_id' => 2]);
        $this->getTableLocator()->get('MemberOrders')->insertQuery()
            ->insert(['id', 'shop_item_id', 'member_id', 'quantity', 'delivered'])
            ->values(['id' => 4, 'shop_item_id' => $itemId, 'member_id' => 1, 'quantity' => 1, 'delivered' => false])
            ->execute();
        $this->loginWith(self::MANAGER);
        $members = $this->getTableLocator()->get('Members');

        $members->updateAll(['pref_site_id' => 2], ['id' => 1]);
        $this->assertSame([4], $this->listedIds('/member-orders/index/0/0/0/0'));
        $this->assertResponseContains('Lausanne');

        $members->updateAll(['pref_site_id' => 1], ['id' => 1]);
        $this->assertSame([1, 2, 3], $this->listedIds('/member-orders/index/0/0/0/0'));
        // The order of another location can't be changed
        $this->post('/member-orders/mark-delivered/4');
        $this->assertResponseCode(404);
        $this->assertFalse($this->delivered(4));
    }

    /**
     * With several locations, "All locations" must be replaced by one location
     */
    public function testAskToChooseALocation(): void
    {
        $this->addSite(2, 'Lausanne');
        $this->loginWith(self::MANAGER);

        $this->get('/member-orders');

        $this->assertResponseOk();
        $this->assertTemplate('Shop/choose_site');
        $this->assertResponseContains('Choose a location');
    }

    public function testMerchandisingManager(): void
    {
        $this->addMember(5, [], ['first_name' => 'Zoe']);
        $this->getTableLocator()->get('MemberOrders')->updateAll(['member_id' => 5], []);
        $this->loginWith(['ShopManager']);

        $this->assertSame([1, 2, 3], $this->listedIds('/member-orders'));
        // Without the rights on the members and the bills, their names are not links
        $this->assertResponseContains('Zoe');
        $this->assertResponseNotContains('/members/view/5"');
        $this->assertResponseNotContains('/bills/view/10"');
        $this->post('/member-orders/mark-delivered/2');
        $this->assertTrue($this->delivered(2));
    }

    public function testChangesRequirePost(): void
    {
        $this->loginWith(self::MANAGER);

        foreach (['/member-orders/mark-delivered/2', '/member-orders/mark-not-delivered/1'] as $url) {
            $this->get($url);
            $this->assertResponseCode(405, $url);
        }
        $this->assertTrue($this->delivered(1));
        $this->assertFalse($this->delivered(2));
    }
}
