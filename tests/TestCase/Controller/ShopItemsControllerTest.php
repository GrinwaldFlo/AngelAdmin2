<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Model\Table\ShopItemsTable;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Controller\ShopItemsController
 */
class ShopItemsControllerTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function addItem(string $label = 'Club shirt'): int
    {
        $items = $this->getTableLocator()->get('ShopItems');

        return $items->saveOrFail($items->newEntity(['label' => $label, 'price' => 25, 'category' => ShopItemsTable::CATEGORY_EQUIPMENT]))->id;
    }

    private function addOrder(int $itemId): void
    {
        $orders = $this->getTableLocator()->get('MemberOrders');
        $orders->saveOrFail($orders->newEntity(['shop_item_id' => $itemId, 'member_id' => 1, 'quantity' => 2]));
    }

    public function testIndexRequiresLogin(): void
    {
        $this->get('/shop-items');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndex(): void
    {
        $this->addItem();
        $this->loginWith(self::MANAGER);

        $this->get('/shop-items');

        $this->assertResponseOk();
        $this->assertResponseContains('Club shirt');
        $this->assertResponseContains('Equipment');
    }

    public function testForbiddenWithoutEditAllOrForCoaches(): void
    {
        $itemId = $this->addItem();
        foreach ([['MemberViewAll', 'MemberEditOwn'], [...self::MANAGER, 'TeamScoped']] as $rights) {
            $this->loginWith($rights);
            foreach (['/shop-items', "/shop-items/view/$itemId", '/shop-items/add', "/shop-items/edit/$itemId"] as $url) {
                $this->get($url);
                $this->assertResponseCode(403, $url);
            }
            $this->post("/shop-items/delete/$itemId");
            $this->assertResponseCode(403);
        }
        $this->assertSame(1, $this->getTableLocator()->get('ShopItems')->find()->count());
    }

    public function testViewListsOrders(): void
    {
        $itemId = $this->addItem();
        $this->addOrder($itemId);
        $this->loginWith(self::MANAGER);

        $this->get("/shop-items/view/$itemId");

        $this->assertResponseOk();
        $this->assertResponseContains('Club shirt');
        $this->assertCount(1, $this->viewVariable('shopItem')->member_orders);
    }

    public function testAdd(): void
    {
        $this->loginWith(self::MANAGER);

        $this->get('/shop-items/add');
        $this->assertResponseOk();
        $this->assertSame(ShopItemsTable::getCategoryOptions(), $this->viewVariable('categoryOptions'));

        $this->post('/shop-items/add', ['label' => 'Bag', 'price' => '30.50', 'category' => ShopItemsTable::CATEGORY_EQUIPMENT, 'active' => 1]);

        $this->assertRedirect(['controller' => 'ShopItems', 'action' => 'index']);
        $item = $this->getTableLocator()->get('ShopItems')->find()->where(['label' => 'Bag'])->firstOrFail();
        $this->assertEquals(30.5, $item->price);
    }

    public function testAddInvalid(): void
    {
        $this->loginWith(self::MANAGER);

        $this->post('/shop-items/add', ['label' => '', 'price' => 0, 'category' => 99]);

        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
        $this->assertSame(0, $this->getTableLocator()->get('ShopItems')->find()->count());
    }

    public function testEdit(): void
    {
        $itemId = $this->addItem();
        $this->loginWith(self::MANAGER);

        $this->get("/shop-items/edit/$itemId");
        $this->assertResponseOk();

        $this->post("/shop-items/edit/$itemId", ['label' => 'New shirt', 'active' => 0]);

        $this->assertRedirect(['controller' => 'ShopItems', 'action' => 'index']);
        $item = $this->getTableLocator()->get('ShopItems')->get($itemId);
        $this->assertSame('New shirt', $item->label);
        $this->assertFalse($item->active);
    }

    public function testDelete(): void
    {
        $itemId = $this->addItem();
        $this->loginWith(self::MANAGER);

        $this->post("/shop-items/delete/$itemId");

        $this->assertRedirect(['controller' => 'ShopItems', 'action' => 'index']);
        $this->assertFlashElement('flash/success');
        $this->assertSame(0, $this->getTableLocator()->get('ShopItems')->find()->count());
    }

    /**
     * The orders keep their item: an ordered item can only be disabled
     */
    public function testDeleteRefusedWhenOrdered(): void
    {
        $itemId = $this->addItem();
        $this->addOrder($itemId);
        $this->loginWith(self::MANAGER);

        $this->post("/shop-items/delete/$itemId");

        $this->assertRedirect(['controller' => 'ShopItems', 'action' => 'index']);
        $this->assertFlashElement('flash/error');
        $this->assertSame(1, $this->getTableLocator()->get('ShopItems')->find()->count());
    }

    public function testDeleteRequiresPost(): void
    {
        $itemId = $this->addItem();
        $this->loginWith(self::MANAGER);

        $this->get("/shop-items/delete/$itemId");

        $this->assertResponseCode(405);
        $this->assertSame(1, $this->getTableLocator()->get('ShopItems')->find()->count());
    }

    /**
     * One attribute per line: saved trimmed, without empty lines nor duplicates
     */
    public function testAddWithAttributesInTheChosenLocation(): void
    {
        $this->addSite(2, 'Lausanne');
        $this->getTableLocator()->get('Members')->updateAll(['site_id' => 2], ['id' => 1]);
        $this->loginWith(self::MANAGER);

        $this->post('/shop-items/add', [
            'label' => 'T-shirt', 'price' => 25, 'category' => ShopItemsTable::CATEGORY_EQUIPMENT, 'active' => 1,
            'attributes' => " S \r\nM\r\n\r\nM\nXL ", 'allow_out_of_stock' => 1, 'site_id' => 1,
        ]);

        $this->assertRedirect(['controller' => 'ShopItems', 'action' => 'index']);
        $item = $this->getTableLocator()->get('ShopItems')->find()->where(['label' => 'T-shirt'])->firstOrFail();
        $this->assertSame("S\nM\nXL", $item->attributes);
        $this->assertSame(['S', 'M', 'XL'], $item->getAttributeList());
        $this->assertTrue($item->allow_out_of_stock);
        // The location is the chosen one, not a posted one
        $this->assertSame(2, $item->site_id);
    }

    public function testEditRemovesTheAttributes(): void
    {
        $itemId = $this->addShopItem('T-shirt', ['attributes' => "S\nM"]);
        $this->loginWith(self::MANAGER);

        $this->get("/shop-items/edit/$itemId");
        $this->assertResponseContains('allow_out_of_stock');
        $this->post("/shop-items/edit/$itemId", ['attributes' => "\n  \n"]);

        $item = $this->getTableLocator()->get('ShopItems')->get($itemId);
        $this->assertNull($item->attributes);
        $this->assertFalse($item->hasAttributes());
    }

    public function testAttributeTooLong(): void
    {
        $this->loginWith(self::MANAGER);

        $this->post('/shop-items/add', ['label' => 'Bag', 'price' => 30, 'category' => ShopItemsTable::CATEGORY_EQUIPMENT, 'attributes' => "S\n" . str_repeat('x', 101)]);

        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
        $this->assertSame(0, $this->getTableLocator()->get('ShopItems')->find()->count());
    }

    public function testOnlyTheItemsOfTheChosenLocation(): void
    {
        $this->addSite(2, 'Lausanne');
        $this->addShopItem('Renens shirt');
        $otherId = $this->addShopItem('Lausanne shirt', ['site_id' => 2]);
        $this->getTableLocator()->get('Members')->updateAll(['site_id' => 1], ['id' => 1]);
        $this->loginWith(self::MANAGER);

        $this->get('/shop-items');
        $this->assertResponseContains('Renens shirt');
        $this->assertResponseNotContains('Lausanne shirt');

        foreach (["/shop-items/view/$otherId", "/shop-items/edit/$otherId"] as $url) {
            $this->get($url);
            $this->assertResponseCode(404, $url);
        }
        $this->post("/shop-items/delete/$otherId");
        $this->assertResponseCode(404);
        $this->assertTrue($this->getTableLocator()->get('ShopItems')->exists(['id' => $otherId]));
    }

    public function testAskToChooseALocation(): void
    {
        // Only an admin chooses "All locations" in the menu
        $this->addSite(2, 'Lausanne');
        $this->loginWith([...self::MANAGER, 'Admin']);

        foreach (['/shop-items', '/shop-items/add'] as $url) {
            $this->get($url);
            $this->assertResponseOk();
            $this->assertTemplate('Shop/choose_site');
        }
        $this->post('/shop-items/add', ['label' => 'Bag', 'price' => 30, 'category' => ShopItemsTable::CATEGORY_EQUIPMENT]);
        $this->assertSame(0, $this->getTableLocator()->get('ShopItems')->find()->count());
        // The location menu is shown to choose one
        $this->assertResponseContains('/members/set-site/2');
    }

    public function testMerchandisingManager(): void
    {
        $this->addSite(2, 'Lausanne');
        $itemId = $this->addItem();
        $this->getTableLocator()->get('Members')->updateAll(['site_id' => 1], ['id' => 1]);
        $this->loginWith(['ShopManager']);

        foreach (['/shop-items', "/shop-items/view/$itemId", '/shop-items/add', "/shop-items/edit/$itemId"] as $url) {
            $this->get($url);
            $this->assertResponseOk($url);
        }
        // The shop menu is shown, not the location menu (only for the admins): the manager works on its city
        $this->assertResponseContains('/shop-orders');
        $this->assertResponseContains('/second-hand-admin');
        $this->assertResponseNotContains('/members/set-site/2');

        $this->post('/members/set-site/2');
        $this->assertSame(0, $this->getTableLocator()->get('Members')->get(1)->pref_site_id);
    }

    /**
     * Without the shop right, nor the shop menu
     */
    public function testNoShopMenuWithoutTheRight(): void
    {
        $this->loginWith(['MemberViewAll', 'MemberEditOwn']);

        $this->get('/');

        $this->assertResponseNotContains('/shop-items');
        $this->assertResponseNotContains('/shop-orders');
    }
}
