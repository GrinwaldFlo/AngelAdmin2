<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
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
        'app.MemberDocs', 'app.ShopItems', 'app.MemberOrders', 'app.Registrations',
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
        $this->shirt = $items->saveOrFail($items->newEntity(['label' => 'Shirt', 'price' => 20, 'category' => ShopItemsTable::CATEGORY_EQUIPMENT]))->id;
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

    public function testOrderOfTheMember(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is needed for the QR code of the bill');
        }
        $this->loginWith(['MemberEditOwn']);

        $this->post('/members/view/1', ['shop_order' => [$this->shirt => '2']]);

        $this->assertRedirect(['controller' => 'Members', 'action' => 'view', 1]);
        $bill = $this->getTableLocator()->get('Bills')->find()->firstOrFail();
        $this->assertEquals(40, $bill->amount);
        $this->assertSame(1, $this->orders());
    }
}
