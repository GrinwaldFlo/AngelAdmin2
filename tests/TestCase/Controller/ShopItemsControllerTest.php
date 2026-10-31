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
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.ShopItems', 'app.MemberOrders',
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
}
