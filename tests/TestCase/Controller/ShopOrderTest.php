<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Model\Entity\ShopItem;
use App\Model\Table\ShopItemsTable;
use App\Test\TestSuite\TestDataTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Orders from the member page: a bill with one order line per item.
 * Member 1 (user 1) is registered, member 100 is another member of team 1.
 *
 * @uses \App\Controller\MembersController::view()
 * @uses \App\Controller\MembersController::myPage()
 */
class ShopOrderTest extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.Meetings', 'app.Presences', 'app.Fields', 'app.FieldTypes',
        'app.MemberDocs', 'app.ShopItems', 'app.MemberOrders', 'app.Registrations', 'app.ShopStocks',
    ];

    private int $shirt;

    private int $oldShirt;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        // The PNG logo needs the GD extension, not always installed with the CLI
        Configure::write('App.logo', 'cheerPlaceholder.jpg');
        $this->getTableLocator()->get('Bills')->deleteAll([]);
        $this->getTableLocator()->get('Members')->updateAll(['registered' => 1, 'hash' => sha1('member1')], ['id' => 1]);
        $this->addMember(100, [1], ['registered' => 1]);
        $items = $this->getTableLocator()->get('ShopItems');
        $this->shirt = $items->saveOrFail($items->newEntity(['label' => 'Shirt', 'price' => 20, 'category' => ShopItemsTable::CATEGORY_EQUIPMENT, 'allow_out_of_stock' => true]))->id;
        $this->oldShirt = $items->saveOrFail($items->newEntity(['label' => 'Old shirt', 'price' => 5, 'category' => ShopItemsTable::CATEGORY_EQUIPMENT, 'active' => false]))->id;
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        foreach ([sha1('member1'), sha1('member100')] as $hash) {
            $folder = WWW_ROOT . 'img/members/' . $hash . '/';
            foreach (glob($folder . '*') ?: [] as $file) {
                unlink($file);
            }
            @rmdir($folder);
        }
        parent::tearDown();
    }

    private function bills(): int
    {
        return $this->getTableLocator()->get('Bills')->find()->count();
    }

    private function orders(): int
    {
        return $this->getTableLocator()->get('MemberOrders')->find()->count();
    }

    /**
     * The order is refused before any bill is created
     */
    public function testInvalidOrdersCreateNothing(): void
    {
        $this->loginWith(['MemberEditOwn']);

        foreach ([[$this->shirt => '2.5'], [$this->shirt => '11'], [$this->shirt => '-1'], [$this->shirt => 'abc'], [$this->oldShirt => '1'], [999 => '1']] as $order) {
            $this->post('/members/view/1', ['shop_order' => $order]);
            $this->assertResponseOk();
        }

        $this->assertFlashElement('flash/error');
        $this->assertSame(0, $this->bills());
        $this->assertSame(0, $this->orders());
    }

    public function testEmptyOrder(): void
    {
        $this->loginWith(['MemberEditOwn']);

        $this->post('/members/view/1', ['shop_order' => [$this->shirt => '', $this->oldShirt => '0']]);

        $this->assertSame(0, $this->bills());
    }

    public function testNoOrderForAnotherMember(): void
    {
        $this->loginWith(['MemberViewAll', 'MemberEditAll']);

        $this->post('/members/view/100', ['shop_order' => [$this->shirt => '1']]);

        $this->assertResponseOk();
        $this->assertSame(0, $this->bills());
    }

    public function testNoOrderWhenNotRegistered(): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['registered' => 0], ['id' => 1]);
        $this->loginWith(['MemberEditOwn']);

        $this->post('/members/view/1', ['shop_order' => [$this->shirt => '1']]);

        $this->assertSame(0, $this->bills());
    }

    /**
     * Without the GD extension the PDF of the bill fails: the order is placed anyway
     */
    public function testOrderOfTheMember(): void
    {
        $this->loginWith(['MemberEditOwn']);

        $this->post('/members/view/1', ['shop_order' => [$this->shirt => '2']]);

        $this->assertRedirect(['controller' => 'Members', 'action' => 'view', 1]);
        $bill = $this->getTableLocator()->get('Bills')->find()->firstOrFail();
        $this->assertEquals(40, $bill->amount);
        $this->assertSame(1, $this->orders());
    }

    /**
     * @param string $attribute attribute of the item, '' = item without attribute
     * @return array<string, int|string> field of the order form
     */
    private function line(int $itemId, int $quantity, string $attribute = ''): array
    {
        return $attribute === '' ? [$itemId => (string)$quantity] : [$itemId => [ShopItem::attributeKey($attribute) => (string)$quantity]];
    }

    /**
     * T-shirt with sizes S, M, L: 2 S in stock, M sold out, no stock for L
     */
    private function addTshirt(array $fields = []): int
    {
        $id = $this->addShopItem('T-shirt', $fields + ['attributes' => "S\nM\nL", 'price' => 25]);
        $this->setShopStock($id, 'S', 2);
        $this->setShopStock($id, 'M', 0);

        return $id;
    }

    /**
     * @return array<string, array<string, int>> offered quantities by item label and attribute ('' = none)
     */
    private function offer(): array
    {
        $this->get('/members/view/1');
        $this->assertResponseOk();
        $offer = [];
        foreach ($this->viewVariable('shopOffer') as $entry) {
            foreach ($entry['options'] as $option) {
                $offer[$entry['item']->label][(string)$option['attribute']] = $option['max'];
            }
        }

        return $offer;
    }

    /**
     * Only the attributes in stock, unless the item can be ordered out of stock
     */
    public function testOfferHidesWhatIsNotAvailable(): void
    {
        $this->addTshirt();
        $this->addShopItem('Cap');
        $this->setShopStock($this->addShopItem('Bag'), '', 3);
        $this->loginWith(['MemberEditOwn']);

        $offer = $this->offer();

        $this->assertSame(['S' => 2], $offer['T-shirt']);
        $this->assertSame(['' => 3], $offer['Bag']);
        $this->assertSame(['' => 10], $offer['Shirt']);
        $this->assertArrayNotHasKey('Cap', $offer);
        $this->assertArrayNotHasKey('Old shirt', $offer);
        $this->assertResponseContains('shop_order[' . $this->shirt . ']');
        $this->assertResponseContains('T-shirt - S');
    }

    public function testOfferOnlyOfTheLocationOfTheMember(): void
    {
        $this->addSite(2, 'Lausanne');
        $this->addShopItem('Lausanne cap', ['site_id' => 2, 'allow_out_of_stock' => true]);
        $this->loginWith(['MemberEditOwn']);

        $this->assertSame(['Shirt'], array_keys($this->offer()));

        // Member of a team of Lausanne
        $this->getTableLocator()->get('Teams')->updateAll(['site_id' => 2], ['id' => 1]);
        $this->assertSame(['Lausanne cap'], array_keys($this->offer()));
    }

    public function testNoShopWithoutTeam(): void
    {
        $this->getTableLocator()->get('TeamsMembers')->deleteAll(['member_id' => 1]);
        $this->loginWith(['MemberEditOwn']);

        $this->assertSame([], $this->offer());
        $this->post('/members/view/1', ['shop_order' => $this->line($this->shirt, 1)]);
        $this->assertSame(0, $this->bills());
    }

    public function testOrderWithAttributeDeductsTheStock(): void
    {
        $tshirt = $this->addTshirt();
        $this->loginWith(['MemberEditOwn']);

        $this->post('/members/view/1', ['shop_order' => $this->line($tshirt, 2, 'S') + $this->line($this->shirt, 1)]);

        $this->assertRedirect(['controller' => 'Members', 'action' => 'view', 1]);
        $bill = $this->getTableLocator()->get('Bills')->find()->firstOrFail();
        $this->assertEquals(70, $bill->amount);
        $this->assertStringContainsString('T-shirt - S (x2)', $bill->label);
        $this->assertSame(1, $bill->site_id);
        $order = $this->getTableLocator()->get('MemberOrders')->find()->where(['shop_item_id' => $tshirt])->firstOrFail();
        $this->assertSame('S', $order->attribute);
        $this->assertSame(2, $order->quantity);
        $this->assertSame(0, $this->shopStock($tshirt, 'S'));
        // An item without attribute has no attribute
        $this->assertNull($this->getTableLocator()->get('MemberOrders')->find()->where(['shop_item_id' => $this->shirt])->firstOrFail()->attribute);
    }

    /**
     * Ordered out of stock: the stock gets negative, it is the quantity to order
     */
    public function testOrderOutOfStock(): void
    {
        $tshirt = $this->addTshirt(['allow_out_of_stock' => true]);
        $this->loginWith(['MemberEditOwn']);

        $this->post('/members/view/1', ['shop_order' => $this->line($tshirt, 3, 'M')]);

        $this->assertRedirect(['controller' => 'Members', 'action' => 'view', 1]);
        $this->assertSame(-3, $this->shopStock($tshirt, 'M'));
    }

    public function testBillOfTheLocationOfTheMember(): void
    {
        $this->addSite(2, 'Lausanne');
        $this->getTableLocator()->get('Teams')->updateAll(['site_id' => 2], ['id' => 1]);
        $cap = $this->addShopItem('Lausanne cap', ['site_id' => 2, 'allow_out_of_stock' => true]);
        $this->loginWith(['MemberEditOwn']);

        $this->post('/members/view/1', ['shop_order' => $this->line($cap, 1)]);

        $this->assertSame(2, $this->getTableLocator()->get('Bills')->find()->firstOrFail()->site_id);
    }

    /**
     * Each refused order: nothing saved, the stock is unchanged
     */
    public function testRefusedOrders(): void
    {
        $tshirt = $this->addTshirt();
        $this->addSite(2, 'Lausanne');
        $other = $this->addShopItem('Lausanne cap', ['site_id' => 2, 'allow_out_of_stock' => true]);
        $this->loginWith(['MemberEditOwn']);

        $orders = [
            'more than the stock' => $this->line($tshirt, 3, 'S'),
            'sold out' => $this->line($tshirt, 1, 'M'),
            'no stock' => $this->line($tshirt, 1, 'L'),
            'removed attribute' => $this->line($tshirt, 1, 'XXL'),
            'attribute to choose' => $this->line($tshirt, 1),
            'item without attribute' => $this->line($this->shirt, 1, 'S'),
            'other location' => $this->line($other, 1),
            'one bad line' => $this->line($tshirt, 1, 'S') + $this->line($other, 1),
        ];
        foreach ($orders as $case => $order) {
            $this->post('/members/view/1', ['shop_order' => $order]);
            $this->assertResponseOk($case);
            $this->assertFlashElement('flash/error');
            $this->assertSame(0, $this->bills(), $case);
        }
        $this->assertSame(2, $this->shopStock($tshirt, 'S'));
        $this->assertSame(0, $this->orders());
    }
}
