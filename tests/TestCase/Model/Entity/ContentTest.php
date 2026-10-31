<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Content;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\Content
 */
class ContentTest extends TestCase
{
    public function testIsHtml(): void
    {
        $this->assertTrue(Content::isHtml('<p>Hello</p>'));
        $this->assertTrue(Content::isHtml('Caf&eacute;'));
        $this->assertTrue(Content::isHtml('Line&#39;s'));
        $this->assertFalse(Content::isHtml("Plain text\n\nwith 2 < 3 & more"));
        $this->assertFalse(Content::isHtml('{city}'));
    }

    public function testLocation(): void
    {
        $this->assertSame('Top menu', (new Content(['location' => 1]))->LocationStr);
        $this->assertSame('Subventions', (new Content(['location' => Content::SUBVENTIONS]))->LocationStr);
        $this->assertSame('Email subventions', (new Content(['location' => Content::EMAIL_SUBVENTIONS]))->LocationStr);
        $this->assertSame('', (new Content(['location' => 99]))->LocationStr);
    }
}
