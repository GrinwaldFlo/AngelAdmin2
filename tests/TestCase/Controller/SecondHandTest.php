<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\Component\SecondHandComponent;
use App\Controller\PrefResult;
use App\Model\Entity\SecondHandLog;
use App\Test\TestSuite\TestDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

/**
 * Second-hand items: member 1 is logged in (admin), member 100 uses its link (hash), member 101 sells items
 *
 * @uses \App\Controller\SecondHandController
 * @uses \App\Controller\SecondHandAdminController
 * @uses \App\Controller\Component\SecondHandComponent
 */
class SecondHandTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Meetings', 'app.Presences', 'app.Bills', 'app.Fields', 'app.FieldTypes',
        'app.Registrations', 'app.MemberDocs', 'app.ShopItems', 'app.MemberOrders',
        'app.SecondHandCategories', 'app.SecondHandProfiles', 'app.SecondHandItems', 'app.SecondHandLogs',
    ];

    private string $upload;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->upload = TMP . 'second_hand_upload';
        $this->getTableLocator()->get('Configurations')->insertQuery()
            ->insert(['label', 'value'])
            ->values(['label' => 'clubName', 'value' => 'My club'])
            ->values(['label' => 'email', 'value' => 'club@example.com'])
            ->values(['label' => SecondHandComponent::ENABLED, 'value' => '1'])
            ->execute();
        $this->addMember(100, [1]);
        $this->addMember(101, [1], ['active' => false]);
        $this->addCategory(1, 'Uniforms');
        $this->addCategory(2, 'Shoes');
    }

    protected function tearDown(): void
    {
        foreach ($this->getTableLocator()->get('SecondHandItems')->find() as $item) {
            foreach (glob($item->folderPath() . '*') ?: [] as $file) {
                unlink($file);
            }
            @rmdir($item->folderPath());
        }
        @unlink($this->upload);
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function addCategory(int $id, string $name): void
    {
        $this->getTableLocator()->get('SecondHandCategories')->insertQuery()
            ->insert(['id', 'name'])
            ->values(['id' => $id, 'name' => $name])
            ->execute();
    }

    /**
     * Profile with the terms accepted
     * @param array $fields columns overriding the default seller values
     */
    private function addProfile(int $id, int $memberId, array $fields = []): void
    {
        $values = $fields + [
            'id' => $id, 'member_id' => $memberId, 'terms_accepted' => '2026-01-01 10:00:00', 'name' => 'Seller' . $memberId,
            'phone' => '079 123 45 67', 'whatsapp' => false, 'email' => "m$memberId@example.com", 'email_verified' => true, 'city' => 'Renens',
        ];
        $this->getTableLocator()->get('SecondHandProfiles')->insertQuery()
            ->insert(array_keys($values))
            ->values($values)
            ->execute();
    }

    private function addItem(int $id, int $profileId, array $fields = []): void
    {
        $values = $fields + [
            'id' => $id, 'profile_id' => $profileId, 'category_id' => 1, 'description' => 'Item' . $id, 'size' => 'M',
            'price' => 20, 'hash' => sha1('item' . $id), 'created' => DateTime::now()->subDays(1)->format('Y-m-d H:i:s'),
        ];
        $this->getTableLocator()->get('SecondHandItems')->insertQuery()
            ->insert(array_keys($values))
            ->values($values)
            ->execute();
    }

    private function asMember100(): void
    {
        $this->cookie('member_hash', sha1('member100'));
    }

    private function profileOf(int $memberId)
    {
        return $this->getTableLocator()->get('SecondHandProfiles')->find()->where(['member_id' => $memberId])->first();
    }

    private function setting(string $label, string $value): void
    {
        $this->getTableLocator()->get('Configurations')->updateAll(['value' => $value], ['label' => $label]);
    }

    public function testDisabledModuleIsHidden(): void
    {
        $this->setting(SecondHandComponent::ENABLED, '0');
        $this->asMember100();

        $this->get('/second-hand');
        $this->assertResponseCode(404);

        $this->loginWith();
        $this->get('/');
        $this->assertResponseNotContains('/second-hand"');
    }

    public function testMenuLinkWhenEnabled(): void
    {
        $this->loginWith();
        $this->get('/members/view/1');
        $this->assertResponseContains('href="/second-hand"');
    }

    public function testMemberLinkPageShowsTheButton(): void
    {
        $this->get('/members/my-page/' . sha1('member100'));
        $this->assertResponseOk();
        $this->assertResponseContains('href="/second-hand"');
    }

    public function testNotIdentifiedGoesToLogin(): void
    {
        $this->get('/second-hand');
        $this->assertRedirectContains('/users/login');
    }

    public function testTermsAreAcceptedOnce(): void
    {
        $this->asMember100();

        $this->get('/second-hand');
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'terms']);

        $this->post('/second-hand/terms', ['accept' => 0]);
        $this->assertResponseOk();
        $this->assertNull($this->profileOf(100));

        $this->post('/second-hand/terms', ['accept' => 1]);
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'index']);
        $this->assertNotNull($this->profileOf(100)->terms_accepted);

        $this->get('/second-hand');
        $this->assertResponseOk();
    }

    public function testBuyersSeeOnlyItemsForSale(): void
    {
        $this->addProfile(1, 101);
        $this->addProfile(2, 1, ['email' => 'other@example.com', 'email_verified' => false]);
        $this->addItem(1, 1, ['description' => 'ForSale']);
        $this->addItem(2, 1, ['sold' => '2026-02-01 10:00:00']);
        $this->addItem(3, 1, ['created' => DateTime::now()->subMonths(7)->format('Y-m-d H:i:s')]);
        $this->addItem(4, 2);
        $this->addItem(5, 1, ['category_id' => 2]);
        $this->addProfile(3, 100);
        $this->asMember100();

        $this->get('/second-hand');
        $this->assertResponseOk();
        $this->assertResponseContains('/second-hand/view/1"');
        $this->assertResponseContains('/second-hand/view/5"');
        foreach ([2, 3, 4] as $id) {
            $this->assertResponseNotContains("/second-hand/view/$id\"");
        }

        $this->get('/second-hand/index/2');
        $this->assertResponseContains('/second-hand/view/5"');
        $this->assertResponseNotContains('/second-hand/view/1"');

        $this->get('/second-hand/view/2');
        $this->assertResponseCode(404);
    }

    public function testExpirationDurationIsSetByTheAdmin(): void
    {
        $this->getTableLocator()->get('Configurations')->insertQuery()
            ->insert(['label', 'value'])
            ->values(['label' => SecondHandComponent::EXPIRY_MONTHS, 'value' => '12'])
            ->execute();
        $this->addProfile(1, 101);
        $this->addItem(3, 1, ['created' => DateTime::now()->subMonths(7)->format('Y-m-d H:i:s')]);
        $this->addProfile(3, 100);
        $this->asMember100();

        $this->get('/second-hand');
        $this->assertResponseContains('/second-hand/view/3"');
    }

    public function testDetailShowsTheContactOfTheSeller(): void
    {
        $this->addProfile(1, 101, ['name' => 'Parent of Junior', 'whatsapp' => true]);
        $this->addItem(1, 1);
        $this->addProfile(3, 100);
        $this->asMember100();

        $this->get('/second-hand/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Parent of Junior');
        $this->assertResponseNotContains('First101');
        $this->assertResponseContains('href="tel:0791234567"');
        $this->assertResponseContains('href="https://wa.me/41791234567"');
        $this->assertResponseContains('href="mailto:m101@example.com?subject=');
        $this->assertResponseContains('/img/secondhand/' . sha1('item1') . '/picture.jpg');
    }

    public function testNoWhatsappLinkWhenNotChosen(): void
    {
        $this->addProfile(1, 101);
        $this->addItem(1, 1);
        $this->addProfile(3, 100);
        $this->asMember100();

        $this->get('/second-hand/view/1');
        $this->assertResponseNotContains('wa.me');
    }

    public function testSellerDetailsWithTheMemberEmailAreVerified(): void
    {
        $this->addProfile(3, 100, ['name' => '', 'phone' => '', 'email' => '', 'city' => '', 'email_verified' => false]);
        $this->asMember100();

        $this->get('/second-hand/my-items');
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'profile']);

        $this->get('/second-hand/profile');
        $this->assertResponseContains('value="m100@example.com"');

        $this->post('/second-hand/profile', ['name' => 'Mum', 'phone' => '079 111 22 33', 'whatsapp' => 1, 'email' => 'M100@example.com', 'city' => 'Lausanne']);
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'myItems']);
        $profile = $this->profileOf(100);
        $this->assertTrue($profile->email_verified);
        $this->assertTrue($profile->whatsapp);
        $this->assertNoMailSent();
    }

    public function testOtherEmailNeedsTheConfirmationLink(): void
    {
        $this->addProfile(3, 100);
        $this->asMember100();

        $this->post('/second-hand/profile', ['name' => 'Mum', 'phone' => '079 111 22 33', 'email' => 'mum@example.com', 'city' => 'Lausanne']);
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'myItems']);
        $profile = $this->profileOf(100);
        $this->assertFalse($profile->email_verified);
        $this->assertMailSentTo('mum@example.com');
        $this->assertMailContains('/second-hand/confirm-email/' . $profile->email_token);

        // Not shown to buyers, no new item until confirmed
        $this->get('/second-hand/add');
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'profile']);

        $this->get('/second-hand/confirm-email/' . $profile->email_token);
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'myItems']);
        $profile = $this->profileOf(100);
        $this->assertTrue($profile->email_verified);
        $this->assertNull($profile->email_token);

        $this->get('/second-hand/add');
        $this->assertResponseOk();
    }

    public function testWrongConfirmationLink(): void
    {
        $this->addProfile(3, 100, ['email' => 'mum@example.com', 'email_verified' => false, 'email_token' => str_repeat('a', 32)]);

        $this->get('/second-hand/confirm-email/' . str_repeat('b', 32));
        $this->assertFlashMessage('This link is not valid or was already used.');
        $this->assertFalse($this->profileOf(100)->email_verified);
    }

    public function testInvalidSellerDetails(): void
    {
        $this->addProfile(3, 100);
        $this->asMember100();

        $this->post('/second-hand/profile', ['name' => '', 'phone' => 'abc', 'email' => 'not an email', 'city' => 'Lausanne']);
        $this->assertResponseOk();
        $this->assertSame('Seller100', $this->profileOf(100)->name);
    }

    public function testAddItemNeedsAPicture(): void
    {
        $this->addProfile(3, 100);
        $this->asMember100();

        $this->post('/second-hand/add', ['category_id' => 1, 'description' => 'Top', 'size' => 'S', 'price' => '15']);
        $this->assertResponseOk();
        $this->assertFlashMessage('Please add a picture.');
        $this->assertSame(0, $this->getTableLocator()->get('SecondHandItems')->find()->count());
        $this->assertSame(0, $this->getTableLocator()->get('SecondHandLogs')->find()->count());
    }

    public function testAddItemWithMissingFields(): void
    {
        $this->addProfile(3, 100);
        $this->asMember100();

        $this->post('/second-hand/add', ['category_id' => 1, 'description' => '', 'size' => '', 'price' => '']);
        $this->assertResponseOk();
        $this->assertSame(0, $this->getTableLocator()->get('SecondHandItems')->find()->count());
    }

    public function testAddItemWithItsPicture(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is needed to process the pictures');
        }
        $this->addProfile(3, 100, ['name' => 'Mum']);
        $this->asMember100();
        file_put_contents($this->upload, file_get_contents(WWW_ROOT . 'img' . DS . 'cheerPlaceholder.jpg'));
        $picture = new UploadedFile($this->upload, filesize($this->upload), UPLOAD_ERR_OK, 'picture.jpg', 'image/jpeg');

        $this->post('/second-hand/add', ['category_id' => 2, 'description' => 'Shoes', 'size' => '38', 'price' => '25.50', 'picture' => $picture]);
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'myItems']);

        $item = $this->getTableLocator()->get('SecondHandItems')->find()->firstOrFail();
        $this->assertFileExists($item->picturePath());
        $this->assertFileDoesNotExist($item->folderPath() . 'original.jpg');
        [$width, $height] = getimagesize($item->picturePath());
        $this->assertLessThanOrEqual(SecondHandComponent::PICTURE_SIZE, max($width, $height));

        $log = $this->getTableLocator()->get('SecondHandLogs')->find()->firstOrFail();
        $this->assertSame($item->id, $log->item_id);
        $this->assertSame('Mum', $log->seller_name);
        $this->assertSame('Shoes', $log->category);
        $this->assertSame(SecondHandLog::FOR_SALE, $log->status);
    }

    public function testMarkAsSold(): void
    {
        $this->addProfile(3, 100);
        $this->addItem(1, 3);
        $this->getTableLocator()->get('SecondHandLogs')->insertQuery()
            ->insert(['item_id', 'member_id', 'seller_name', 'category', 'description', 'size', 'price', 'status'])
            ->values(['item_id' => 1, 'member_id' => 100, 'seller_name' => 'S', 'category' => 'C', 'description' => 'D', 'size' => 'M', 'price' => 20, 'status' => 0])
            ->execute();
        $this->asMember100();

        $this->post('/second-hand/sold/1');
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'myItems']);
        $this->assertNotNull($this->getTableLocator()->get('SecondHandItems')->get(1)->sold);
        $log = $this->getTableLocator()->get('SecondHandLogs')->find()->firstOrFail();
        $this->assertSame(SecondHandLog::SOLD, $log->status);
        $this->assertNotNull($log->closed);
    }

    public function testRemoveDeletesThePicture(): void
    {
        $this->addProfile(3, 100);
        $this->addItem(1, 3);
        $item = $this->getTableLocator()->get('SecondHandItems')->get(1);
        mkdir($item->folderPath(), 0755, true);
        touch($item->picturePath());
        $this->asMember100();

        $this->post('/second-hand/delete/1');
        $this->assertRedirect(['controller' => 'SecondHand', 'action' => 'myItems']);
        $this->assertFalse($this->getTableLocator()->get('SecondHandItems')->exists(['id' => 1]));
        $this->assertDirectoryDoesNotExist($item->folderPath());
    }

    public function testItemOfAnotherSellerIsRefused(): void
    {
        $this->addProfile(1, 101);
        $this->addItem(1, 1);
        $this->addProfile(3, 100);
        $this->asMember100();

        $this->post('/second-hand/sold/1');
        $this->assertResponseCode(403);
        $this->post('/second-hand/delete/1');
        $this->assertResponseCode(403);
        $this->assertNull($this->getTableLocator()->get('SecondHandItems')->get(1)->sold);
    }

    public function testMyItemsListsSoldAndExpiredOnes(): void
    {
        $this->addProfile(3, 100);
        $this->addItem(1, 3);
        $this->addItem(2, 3, ['sold' => '2026-02-01 10:00:00']);
        $this->addItem(3, 3, ['created' => DateTime::now()->subMonths(7)->format('Y-m-d H:i:s')]);
        $this->asMember100();

        $this->get('/second-hand/my-items');
        $this->assertResponseOk();
        $this->assertResponseContains('/second-hand/sold/1"');
        $this->assertResponseNotContains('/second-hand/sold/2"');
        $this->assertResponseNotContains('/second-hand/sold/3"');
        $this->assertResponseContains('Expired');
        $this->assertResponseContains('/second-hand/delete/3"');
    }

    public function testAdminPageOnlyForAdmins(): void
    {
        $this->loginWith(['MemberViewAll']);
        $this->get('/second-hand-admin');
        $this->assertRedirect('/');
    }

    public function testAdminSettingsAreSavedInConfigurations(): void
    {
        $this->setting(SecondHandComponent::ENABLED, '0');
        $this->loginWith();

        $this->get('/second-hand-admin');
        $this->assertResponseOk();

        $this->post('/second-hand-admin', ['enabled' => 1, 'expiry_months' => 3]);
        $this->assertRedirect(['controller' => 'SecondHandAdmin', 'action' => 'index']);
        $values = $this->getTableLocator()->get('Configurations')->find('list', keyField: 'label', valueField: 'value')->toArray();
        $this->assertSame('1', $values[SecondHandComponent::ENABLED]);
        $this->assertSame('3', $values[SecondHandComponent::EXPIRY_MONTHS]);
        // New row: id given by the code, the id of old databases is not auto increment
        $configurations = $this->getTableLocator()->get('Configurations');
        $maxOtherId = $configurations->find()->where(['label !=' => SecondHandComponent::EXPIRY_MONTHS])->all()->max('id')->id;
        $this->assertSame($maxOtherId + 1, $configurations->find()->where(['label' => SecondHandComponent::EXPIRY_MONTHS])->firstOrFail()->id);

        $this->post('/second-hand-admin', ['enabled' => 1, 'expiry_months' => 0]);
        $this->assertResponseOk();
        $this->assertFlashMessage('The duration must be between 1 and 120 months.');
    }

    public function testAdminManagesCategories(): void
    {
        $this->addProfile(1, 101);
        $this->addItem(1, 1);
        $this->loginWith();
        $categories = $this->getTableLocator()->get('SecondHandCategories');

        $this->post('/second-hand-admin/add-category', ['name' => 'Bags']);
        $this->assertTrue($categories->exists(['name' => 'Bags']));

        $this->post('/second-hand-admin/edit-category/2', ['name' => 'Sneakers']);
        $this->assertSame('Sneakers', $categories->get(2)->name);

        // Still used by item 1
        $this->post('/second-hand-admin/delete-category/1');
        $this->assertTrue($categories->exists(['id' => 1]));

        $this->post('/second-hand-admin/delete-category/2');
        $this->assertFalse($categories->exists(['id' => 2]));
    }

    public function testAdminLogAndDeleteItem(): void
    {
        $this->addProfile(1, 101, ['name' => 'Dad']);
        $this->addItem(1, 1);
        $logs = $this->getTableLocator()->get('SecondHandLogs');
        $logs->insertQuery()
            ->insert(['item_id', 'member_id', 'seller_name', 'category', 'description', 'size', 'price', 'status', 'created'])
            ->values(['item_id' => 1, 'member_id' => 101, 'seller_name' => 'Dad', 'category' => 'Uniforms', 'description' => 'Top', 'size' => 'M', 'price' => 20, 'status' => 0, 'created' => '2026-01-01 10:00:00'])
            ->values(['item_id' => 9, 'member_id' => 101, 'seller_name' => 'Dad', 'category' => 'Uniforms', 'description' => 'Old', 'size' => 'M', 'price' => 20, 'status' => SecondHandLog::REMOVED, 'created' => '2025-01-01 10:00:00'])
            ->execute();
        $this->loginWith();

        $this->get('/second-hand-admin/items-log');
        $this->assertResponseOk();
        $this->assertResponseContains('Removed by the seller');
        $this->assertResponseContains('/second-hand-admin/delete-item/1"');
        $this->assertResponseNotContains('/second-hand-admin/delete-item/9"');

        $this->post('/second-hand-admin/delete-item/1');
        $this->assertRedirect(['controller' => 'SecondHandAdmin', 'action' => 'itemsLog']);
        $this->assertFalse($this->getTableLocator()->get('SecondHandItems')->exists(['id' => 1]));
        $this->assertSame(SecondHandLog::DELETED, $logs->find()->where(['item_id' => 1])->firstOrFail()->status);
    }
}
