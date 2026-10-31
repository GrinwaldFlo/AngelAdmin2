<?php

declare(strict_types=1);

namespace App\Test\TestSuite;

use App\Controller\PrefResult;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Pages to manage a table (index, view, add, edit, delete), all limited to one right of the role.
 * User 1 (member 1, role 1) is logged with only this right.
 */
abstract class CrudControllerTestCase extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.BillTemplates', 'app.FieldTypes', 'app.Fields',
    ];

    /**
     * @var string url of the controller, e.g. '/roles'
     */
    protected string $url;

    /**
     * @var string table of the controller
     */
    protected string $table;

    /**
     * @var string right of the role needed for all the pages
     */
    protected string $right = 'Admin';

    /**
     * @var bool false if the controller has no delete action
     */
    protected bool $canDelete = true;

    /**
     * Data of a valid form, $field of it is checked after the save
     */
    abstract protected function validData(): array;

    /**
     * @var string field of validData() checked after add / edit
     */
    protected string $field;

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

    /**
     * @param string[]|null $rights null = only the right of the controller
     */
    protected function login(?array $rights = null): void
    {
        $this->loginWith($rights ?? [$this->right]);
    }

    private function rows(): int
    {
        return $this->getTableLocator()->get($this->table)->find()->count();
    }

    public function testRequiresLogin(): void
    {
        $this->get($this->url);

        $this->assertRedirectContains('/users/login');
    }

    public function testForbiddenWithoutTheRight(): void
    {
        $others = array_diff(['MemberViewAll', 'MemberEditAll', 'MemberEditOwn', 'BillViewAll', 'BillEditAll', 'Admin', 'Editor'], [$this->right]);
        $this->login($others);
        $before = $this->rows();

        foreach (['', '/view/1', '/add', '/edit/1'] as $page) {
            $this->get($this->url . $page);
            $this->assertResponseCode(403, $page);
        }
        $this->post($this->url . '/add', $this->validData());
        $this->assertResponseCode(403);
        if ($this->canDelete) {
            $this->post($this->url . '/delete/1');
            $this->assertResponseCode(403);
        }
        $this->assertSame($before, $this->rows());
    }

    public function testIndexAndView(): void
    {
        $this->login();

        $this->get($this->url);
        $this->assertResponseOk();
        $this->get($this->url . '/view/1');
        $this->assertResponseOk();
    }

    public function testAdd(): void
    {
        $this->login();
        $before = $this->rows();

        $this->get($this->url . '/add');
        $this->assertResponseOk();
        $this->post($this->url . '/add', $this->validData());

        $this->assertRedirect(['action' => 'index']);
        $this->assertFlashElement('flash/success');
        $this->assertSame($before + 1, $this->rows());
    }

    public function testEdit(): void
    {
        $this->login();

        $this->get($this->url . '/edit/1');
        $this->assertResponseOk();
        $this->post($this->url . '/edit/1', $this->validData());

        $this->assertRedirect(['action' => 'index']);
        $this->assertSame($this->validData()[$this->field], $this->getTableLocator()->get($this->table)->get(1)->{$this->field});
    }

    public function testDelete(): void
    {
        if (!$this->canDelete) {
            $this->assertFalse(method_exists($this->controllerClass(), 'delete'));

            return;
        }
        $this->addRow(99);
        $this->login();

        $this->get($this->url . '/delete/99');
        $this->assertResponseCode(405);
        $this->post($this->url . '/delete/99');

        $this->assertRedirect(['action' => 'index']);
        $this->assertFalse($this->getTableLocator()->get($this->table)->exists(['id' => 99]));
    }

    public function testMissingRecord(): void
    {
        $this->login();

        $this->get($this->url . '/view/999');

        $this->assertResponseCode(404);
    }

    /**
     * Row without link to other tables, to be deleted
     */
    protected function addRow(int $id): void
    {
        $table = $this->getTableLocator()->get($this->table);
        $entity = $table->newEntity($this->validData());
        $entity->id = $id;
        $table->saveOrFail($entity);
    }

    private function controllerClass(): string
    {
        return 'App\\Controller\\' . $this->table . 'Controller';
    }
}
