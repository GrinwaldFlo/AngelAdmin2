<?php

declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\View\AppView;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\View\AppView
 */
class AppViewTest extends TestCase
{
    private AppView $View;

    protected function setUp(): void
    {
        parent::setUp();
        I18n::setLocale('en');
        $this->loadRoutes();
        $request = new ServerRequest(['params' => ['controller' => 'Members', 'action' => 'index', 'plugin' => null]]);
        Router::setRequest($request);
        $this->View = new AppView($request);
    }

    protected function tearDown(): void
    {
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    public function testHelpersAndLayout(): void
    {
        $this->assertTrue($this->View->helpers()->has('Pdf'));
        $this->assertTrue($this->View->helpers()->has('My'));
        $this->assertSame('default', $this->View->getLayout());
    }

    public function testTableField(): void
    {
        $this->assertSame('<tr><th>Name</th><td>A &amp; B</td></tr>', $this->View->tableField('Name', 'A & B'));
        $this->assertSame('', $this->View->tableField('Name', ''));
        $this->assertSame('', $this->View->tableField('Name', null));
    }

    public function testTableFieldCurrency(): void
    {
        $this->assertStringContainsString('CHF', $this->View->tableFieldCurrency('Amount', 25));
        $this->assertStringContainsString('25.00', $this->View->tableFieldCurrency('Amount', 25));
        $this->assertSame('', $this->View->tableFieldCurrency('Amount', 0));
    }

    public function testBackButtons(): void
    {
        $this->assertSame('<a href="/members"><i class="gg-arrow-left-r"></i></a>', $this->View->backButton('/members'));
        $this->assertSame('<a href="/members"><i class="gg-arrow-left-r"></i></a>', $this->View->backButton('/members', true));
        $this->assertSame('<a href="/teams/view"><i class="gg-arrow-left-r"></i></a>', $this->View->backButtonCtrl('Teams', 'view', true));
        $button = $this->View->backButtonCtrl('Teams', 'index');
        $this->assertStringContainsString('class="back-button"', $button);
        $this->assertStringNotContainsString('style=', $button);
    }
}
