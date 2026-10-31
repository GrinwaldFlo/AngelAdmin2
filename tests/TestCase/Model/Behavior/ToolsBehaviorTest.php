<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Model\Behavior\ToolsBehavior;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Behavior\ToolsBehavior
 */
class ToolsBehaviorTest extends TestCase
{
    private ToolsBehavior $Tools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Tools = new ToolsBehavior(new Table());
    }

    public function testFormatTel(): void
    {
        $this->assertSame('079 123 45 67', $this->Tools->formatTel('0791234567'));
        $this->assertSame('079 123 45 67', $this->Tools->formatTel('079/123.45.67'));
        $this->assertSame('079 123 45 67', $this->Tools->formatTel('+41 79 123 45 67'));
        $this->assertSame('021 123 45 67', $this->Tools->formatTel('0041211234567'));
        // Not a Swiss number: kept as typed (without separators)
        $this->assertSame('+33612345678', $this->Tools->formatTel('+33 6 12 34 56 78'));
        $this->assertSame('123', $this->Tools->formatTel('123'));
        $this->assertSame('', $this->Tools->formatTel(''));
        $this->assertNull($this->Tools->formatTel(null));
    }

    public function testFormatAddress(): void
    {
        $this->assertSame('Av. de la Gare 1', $this->Tools->formatAddress('Avenue de la Gare 1'));
        $this->assertSame('Rte de Lausanne 2', $this->Tools->formatAddress('route de Lausanne 2'));
        $this->assertSame('Rte de Lausanne 2', $this->Tools->formatAddress('Rte. de Lausanne 2'));
        $this->assertSame('Ch. des Fleurs 3', $this->Tools->formatAddress('Chem. des Fleurs 3'));
        $this->assertSame('Ch. des Fleurs 3', $this->Tools->formatAddress('chemin des Fleurs 3'));
        $this->assertSame('Rue 1', $this->Tools->formatAddress('Rue 1'));
        $this->assertNull($this->Tools->formatAddress(null));
    }
}
