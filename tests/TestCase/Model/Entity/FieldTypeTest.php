<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\FieldType;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\FieldType
 */
class FieldTypeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        I18n::setLocale('en');
    }

    protected function tearDown(): void
    {
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    public function testStyleStr(): void
    {
        $this->assertCount(8, (new FieldType())->StyleList);
        $this->assertSame('Email', (new FieldType(['style' => 1]))->style_str);
        $this->assertSame('Cloth Size', (new FieldType(['style' => 7]))->StyleStr);
        $this->assertSame('', (new FieldType(['style' => 99]))->style_str);
        // Virtual: shown in the list of field types
        $this->assertSame('Date', (new FieldType(['style' => 5]))->toArray()['style_str']);
    }
}
