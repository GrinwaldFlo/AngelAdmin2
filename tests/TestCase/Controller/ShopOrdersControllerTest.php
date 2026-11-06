<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Controller\ShopOrdersController;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * T-shirt with sizes S, M, L and a cap without attribute (location 1)
 * Orders: 1 T-shirt M x2 (bill 10 open), 2 T-shirt S x1 (bill 11 paid, delivered), 3 cap x1 (no bill)
 * Stock (orders deducted): M -2, S 0, cap -1
 *
 * @uses \App\Controller\ShopOrdersController
 */
class ShopOrdersControllerTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.ShopItems', 'app.MemberOrders', 'app.ShopStocks',
    ];

    private const MANAGER = ['MemberViewAll', 'MemberEditAll', 'MemberEditOwn', 'BillViewAll'];

    private int $tshirt;

    private int $cap;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $this->getTableLocator()->get('Sites')->updateAll(['sender_email' => 'shop@example.com', 'sender' => 'Shop'], ['id' => 1]);
        $this->getTableLocator()->get('Bills')->deleteAll([]);
        $this->addBill(10, 1, ['amount' => 50]);
        $this->addBill(11, 1, ['paid' => true, 'amount' => 25]);
        $this->tshirt = $this->addShopItem('T-shirt', ['attributes' => "S\nM\nL", 'price' => 25]);
        $this->cap = $this->addShopItem('Cap');
        $this->addOrder(1, $this->tshirt, 'M', 2, 10);
        $this->addOrder(2, $this->tshirt, 'S', 1, 11, true);
        $this->addOrder(3, $this->cap, null, 1, null);
        $this->setShopStock($this->tshirt, 'M', -2);
        $this->setShopStock($this->tshirt, 'S', 0);
        $this->setShopStock($this->cap, '', -1);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function addOrder(int $id, int $itemId, ?string $attribute, int $quantity, ?int $billId, bool $delivered = false): void
    {
        $this->getTableLocator()->get('MemberOrders')->insertQuery()
            ->insert(['id', 'shop_item_id', 'member_id', 'bill_id', 'attribute', 'quantity', 'delivered'])
            ->values(['id' => $id, 'shop_item_id' => $itemId, 'member_id' => 1, 'bill_id' => $billId, 'attribute' => $attribute, 'quantity' => $quantity, 'delivered' => $delivered])
            ->execute();
    }

    private function order(int $id): ?object
    {
        return $this->getTableLocator()->get('MemberOrders')->find()->where(['id' => $id])->first();
    }

    /**
     * @return array<string, array{int, int, int, int}> [not given, stock, on order, to order] by "item/attribute"
     */
    private function summary(string $url = '/shop-orders/summary'): array
    {
        $this->get($url);
        $this->assertResponseOk();
        $summary = [];
        foreach ($this->viewVariable('rows') as $row) {
            $stock = $row['stock'];
            $summary[$row['item']->label . '/' . $row['attribute']] = [$row['pending'], $stock->quantity, $stock->on_order, $stock->toOrder()];
        }

        return $summary;
    }

    private function postStock(string $operation, $quantity, string $attribute = 'M', ?int $itemId = null): void
    {
        $this->post('/shop-orders/update-stock', ['shop_item_id' => $itemId ?? $this->tshirt, 'attribute' => $attribute, 'operation' => $operation, 'quantity' => $quantity]);
    }

    public function testIndexListsTheOrders(): void
    {
        $this->loginWith(self::MANAGER);

        $this->get('/shop-orders');

        $this->assertResponseOk();
        $this->assertCount(3, $this->viewVariable('memberOrders'));
        $this->assertResponseContains('/shop-orders/delete/1');
        // The attribute of an order not given can be changed, not the one of a given order
        $this->assertResponseContains('/shop-orders/change-attribute/1');
        $this->assertResponseNotContains('/shop-orders/change-attribute/2');
        $this->assertResponseNotContains('/shop-orders/change-attribute/3');
        // Style guide (/admin/typography) button classes
        $this->assertResponseContains('btn btn-sm btn-filter pressed');
        $this->assertResponseContains('btn btn-sm btn-save');
        $this->assertResponseContains('class="icon-btn icon-btn-delete"');
        $this->assertResponseNotContains('btn-outline-');

        $this->get("/shop-orders/index/{$this->cap}/0");
        $this->assertCount(1, $this->viewVariable('memberOrders'));
        $this->get('/shop-orders/index/0/1');
        $this->assertCount(1, $this->viewVariable('memberOrders'));
    }

    public function testSummary(): void
    {
        $this->loginWith(self::MANAGER);

        // Sorted by item, then in the order of the attributes
        $this->assertSame([
            'Cap/' => [1, -1, 0, 1],
            'T-shirt/S' => [0, 0, 0, 0],
            'T-shirt/M' => [2, -2, 0, 2],
            'T-shirt/L' => [0, 0, 0, 0],
        ], $this->summary());
        // Style guide (/admin/typography) button classes
        $this->assertResponseContains('btn btn-sm btn-filter pressed');
        $this->assertResponseContains('btn btn-sm btn-save');
        $this->assertResponseContains('btn btn-sm btn-add');
        $this->assertResponseContains('btn btn-sm btn-edit');
        $this->assertResponseNotContains('btn-outline-');

        $this->assertSame(['Cap/', 'T-shirt/M'], array_keys($this->summary('/shop-orders/summary?toOrder=1')));
    }

    /**
     * A removed attribute stays while it has orders to give or stock, an inactive item too
     */
    public function testSummaryKeepsTheRemovedAttributes(): void
    {
        $items = $this->getTableLocator()->get('ShopItems');
        $items->updateAll(['attributes' => "S\nXL"], ['id' => $this->tshirt]);
        $items->updateAll(['active' => false], ['id' => $this->cap]);
        $this->loginWith(self::MANAGER);

        $summary = $this->summary();

        $this->assertSame(['Cap/', 'T-shirt/S', 'T-shirt/XL', 'T-shirt/M'], array_keys($summary));
        $offered = array_column($this->viewVariable('rows'), 'offered');
        $this->assertSame([false, true, true, false], $offered);
        $this->assertResponseContains('No longer offered');
    }

    /**
     * The stock is given back, the bill is not changed: the sender of the location gets an email
     */
    public function testDeleteGivesBackTheStockAndTellsAboutTheBill(): void
    {
        $this->loginWith(self::MANAGER);

        $this->post('/shop-orders/delete/1');

        $this->assertRedirect(['controller' => 'ShopOrders', 'action' => 'index']);
        $this->assertFlashElement('flash/success');
        $this->assertNull($this->order(1));
        $this->assertSame(0, $this->shopStock($this->tshirt, 'M'));
        $bill = $this->getTableLocator()->get('Bills')->get(10);
        $this->assertEquals(50, $bill->amount);
        $this->assertFalse($bill->canceled);
        $this->assertMailCount(1);
        $this->assertMailSentTo('shop@example.com');
        $this->assertMailContains('T-shirt - M');
        $this->assertMailContains((string)$bill->reference);
    }

    public function testDeleteGivenOrderKeepsTheStock(): void
    {
        $this->loginWith(self::MANAGER);

        $this->post('/shop-orders/delete/2');

        $this->assertNull($this->order(2));
        $this->assertSame(0, $this->shopStock($this->tshirt, 'S'));
        $this->assertMailCount(1);
    }

    public function testDeleteWithoutBillSendsNoEmail(): void
    {
        $this->loginWith(self::MANAGER);

        $this->post('/shop-orders/delete/3');

        $this->assertNull($this->order(3));
        $this->assertSame(0, $this->shopStock($this->cap));
        $this->assertNoMailSent();
    }

    /**
     * Without a valid email of the location, the club email is used
     */
    public function testDeleteEmailsTheClubWithoutSenderOfTheLocation(): void
    {
        $this->getTableLocator()->get('Sites')->updateAll(['sender_email' => ''], ['id' => 1]);
        $configurations = $this->getTableLocator()->get('Configurations');
        $configurations->deleteAll(['label' => 'email']);
        $configurations->saveOrFail($configurations->newEntity(['label' => 'email', 'value' => 'club@example.com']));
        $this->loginWith(self::MANAGER);

        $this->post('/shop-orders/delete/1');

        $this->assertNull($this->order(1));
        $this->assertMailSentTo('club@example.com');
    }

    public function testChangeAttributeMovesTheStock(): void
    {
        $this->setShopStock($this->tshirt, 'L', 5);
        $this->loginWith(self::MANAGER);

        $this->post('/shop-orders/change-attribute/1', ['attribute' => 'L']);

        $this->assertRedirect(['controller' => 'ShopOrders', 'action' => 'index']);
        $this->assertFlashElement('flash/success');
        $this->assertSame('L', $this->order(1)->attribute);
        $this->assertSame(0, $this->shopStock($this->tshirt, 'M'));
        $this->assertSame(3, $this->shopStock($this->tshirt, 'L'));
    }

    /**
     * Not enough stock (and not orderable out of stock), not offered, or already given: nothing changes
     */
    public function testChangeAttributeRefused(): void
    {
        $this->setShopStock($this->tshirt, 'L', 1);
        $this->loginWith(self::MANAGER);

        foreach ([[1, 'L'], [1, 'XXL'], [2, 'L']] as [$id, $attribute]) {
            $before = $this->order($id)->attribute;
            $this->post("/shop-orders/change-attribute/$id", ['attribute' => $attribute]);
            $this->assertFlashElement('flash/error');
            $this->assertSame($before, $this->order($id)->attribute, "$id $attribute");
        }
        $this->assertSame(1, $this->shopStock($this->tshirt, 'L'));
        $this->assertSame(-2, $this->shopStock($this->tshirt, 'M'));
    }

    public function testChangeAttributeOutOfStockWhenAllowed(): void
    {
        $this->getTableLocator()->get('ShopItems')->updateAll(['allow_out_of_stock' => true], ['id' => $this->tshirt]);
        $this->loginWith(self::MANAGER);

        $this->post('/shop-orders/change-attribute/1', ['attribute' => 'L']);

        $this->assertSame('L', $this->order(1)->attribute);
        $this->assertSame(-2, $this->shopStock($this->tshirt, 'L'));
    }

    /**
     * Ordered from the supplier, received, then counted by hand
     */
    public function testUpdateStock(): void
    {
        $this->loginWith(self::MANAGER);

        $this->postStock(ShopOrdersController::STOCK_ORDERED, 2);
        $this->assertRedirect(['controller' => 'ShopOrders', 'action' => 'summary']);
        $this->assertSame([2, -2, 2, 0], $this->summary()['T-shirt/M']);

        $this->postStock(ShopOrdersController::STOCK_RECEIVED, 2);
        $this->assertSame([2, 0, 0, 0], $this->summary()['T-shirt/M']);

        $this->postStock(ShopOrdersController::STOCK_ADJUST, 7);
        $this->assertSame([2, 7, 0, 0], $this->summary()['T-shirt/M']);

        $this->postStock(ShopOrdersController::STOCK_ADJUST, -1, '', $this->cap);
        $this->assertSame(-1, $this->shopStock($this->cap));
    }

    public function testUpdateStockRefused(): void
    {
        $this->loginWith(self::MANAGER);

        $cases = [
            [ShopOrdersController::STOCK_ORDERED, 0, 'M'],
            [ShopOrdersController::STOCK_RECEIVED, 'abc', 'M'],
            ['steal', 2, 'M'],
            [ShopOrdersController::STOCK_ADJUST, 5, 'XXL'],
        ];
        foreach ($cases as [$operation, $quantity, $attribute]) {
            $this->postStock($operation, $quantity, $attribute);
            $this->assertFlashElement('flash/error');
        }
        $this->assertSame([2, -2, 0, 2], $this->summary()['T-shirt/M']);
        $this->assertTrue($this->getTableLocator()->get('ShopStocks')->getFor($this->tshirt, 'XXL')->isNew());
    }

    public function testOnlyTheLocationChosen(): void
    {
        $this->addSite(2, 'Lausanne');
        $other = $this->addShopItem('Lausanne cap', ['site_id' => 2]);
        $this->addOrder(4, $other, null, 1, null);
        $this->getTableLocator()->get('Members')->updateAll(['pref_site_id' => 1], ['id' => 1]);
        $this->loginWith(self::MANAGER);

        $this->get('/shop-orders');
        $this->assertCount(3, $this->viewVariable('memberOrders'));
        $this->assertArrayNotHasKey('Lausanne cap/', $this->summary());

        $this->post('/shop-orders/delete/4');
        $this->assertResponseCode(404);
        $this->postStock(ShopOrdersController::STOCK_ADJUST, 5, '', $other);
        $this->assertResponseCode(404);
        $this->assertNotNull($this->order(4));
    }

    public function testAskToChooseALocation(): void
    {
        $this->addSite(2, 'Lausanne');
        $this->loginWith(self::MANAGER);

        foreach (['/shop-orders', '/shop-orders/summary'] as $url) {
            $this->get($url);
            $this->assertResponseOk();
            $this->assertTemplate('Shop/choose_site');
        }
        $this->postStock(ShopOrdersController::STOCK_ADJUST, 5);
        $this->assertFlashElement('flash/error');
        // Back to the summary, where the stock form is
        $this->assertRedirect(['controller' => 'ShopOrders', 'action' => 'summary']);
        $this->assertSame(-2, $this->shopStock($this->tshirt, 'M'));
    }

    /**
     * The item filter of another location (changed in the menu) is reset to all items
     */
    public function testItemFilterOfAnotherLocationIsReset(): void
    {
        $this->addSite(2, 'Lausanne');
        $other = $this->addShopItem('Lausanne cap', ['site_id' => 2]);
        $this->addOrder(4, $other, null, 1, null);
        $members = $this->getTableLocator()->get('Members');
        $this->loginWith(self::MANAGER);

        $members->updateAll(['pref_site_id' => 2], ['id' => 1]);
        $this->get("/shop-orders/index/$other");
        $this->assertCount(1, $this->viewVariable('memberOrders'));

        $members->updateAll(['pref_site_id' => 1], ['id' => 1]);
        $this->get('/shop-orders');
        $this->assertSame(0, $this->viewVariable('itemFilter'));
        $this->assertCount(3, $this->viewVariable('memberOrders'));
        $this->assertSession(0, 'shopOrdersItemFilter');
    }

    /**
     * Received from the supplier: never more on order than ordered
     */
    public function testReceiveMoreThanOrdered(): void
    {
        $this->loginWith(self::MANAGER);

        $this->postStock(ShopOrdersController::STOCK_ORDERED, 1);
        $this->postStock(ShopOrdersController::STOCK_RECEIVED, 3);

        $this->assertSame([2, 1, 0, 0], $this->summary()['T-shirt/M']);
    }

    /**
     * Stock of an offered attribute without stock yet: created
     */
    public function testUpdateStockCreatesTheStock(): void
    {
        $this->loginWith(self::MANAGER);

        $this->postStock(ShopOrdersController::STOCK_ORDERED, 4, 'L');

        $this->assertSame([0, 0, 4, 0], $this->summary()['T-shirt/L']);
    }

    public function testMerchandisingManager(): void
    {
        $this->loginWith(['ShopManager']);

        $this->get('/shop-orders');
        $this->assertResponseOk();
        $this->summary();
        $this->post('/shop-orders/delete/3');
        $this->assertNull($this->order(3));
    }

    public function testForbiddenWithoutTheShopRightOrForCoaches(): void
    {
        foreach ([['MemberViewAll', 'MemberEditOwn', 'BillViewAll'], [...self::MANAGER, 'TeamScoped']] as $rights) {
            $this->loginWith($rights);
            foreach (['/shop-orders', '/shop-orders/summary'] as $url) {
                $this->get($url);
                $this->assertResponseCode(403, $url);
            }
            $this->post('/shop-orders/delete/1');
            $this->assertResponseCode(403);
            $this->post('/shop-orders/change-attribute/1', ['attribute' => 'S']);
            $this->assertResponseCode(403);
            $this->postStock(ShopOrdersController::STOCK_ADJUST, 5);
            $this->assertResponseCode(403);
        }
        $this->assertNotNull($this->order(1));
        $this->assertSame('M', $this->order(1)->attribute);
        $this->assertSame(-2, $this->shopStock($this->tshirt, 'M'));
    }

    public function testChangesRequirePost(): void
    {
        $this->loginWith(self::MANAGER);

        foreach (['/shop-orders/delete/1', '/shop-orders/change-attribute/1', '/shop-orders/update-stock'] as $url) {
            $this->get($url);
            $this->assertResponseCode(405, $url);
        }
        $this->assertNotNull($this->order(1));
    }
}
