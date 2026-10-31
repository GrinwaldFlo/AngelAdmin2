<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\MailingItem;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\MailingItem
 */
class MailingItemTest extends TestCase
{
    public function testStatusLabel(): void
    {
        I18n::setLocale('en');

        $this->assertSame('Sent', (new MailingItem(['status' => MailingItem::SENT]))->statusLabel());
        $this->assertSame('Invalid address', (new MailingItem(['status' => MailingItem::INVALID]))->statusLabel());
        $this->assertSame('Pending', (new MailingItem(['status' => MailingItem::PENDING, 'attempts' => 0]))->statusLabel());
        $this->assertSame('Pending (2 attempts)', (new MailingItem(['status' => MailingItem::PENDING, 'attempts' => 2]))->statusLabel());

        I18n::setLocale(I18n::getDefaultLocale());
    }
}
