<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\GetComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Controller\Component\GetComponent
 */
class GetComponentTest extends TestCase
{
    protected array $fixtures = ['app.Sites', 'app.BillTemplates'];

    private GetComponent $Get;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Get = new GetComponent(new ComponentRegistry(new Controller(new ServerRequest())));
        $templates = $this->getTableLocator()->get('BillTemplates');
        $templates->deleteAll([]);
        $templates->insertQuery()->insert(['id', 'label', 'amount'])
            ->values(['id' => 1, 'label' => 'Shirt', 'amount' => 20])
            ->values(['id' => 2, 'label' => 'Bag', 'amount' => 30])
            ->execute();
    }

    public function testAllTemplatesByLabel(): void
    {
        $this->assertSame(['Bag', 'Shirt'], $this->Get->BillTemplates()->all()->extract('label')->toList());
        $this->assertSame(['Bag', 'Shirt'], $this->Get->BillTemplates(0)->all()->extract('label')->toList());
    }

    public function testOneTemplate(): void
    {
        $this->assertSame('Shirt', $this->Get->BillTemplates(1)->label);
    }
}
