<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\SecondHandProfile;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\SecondHandProfile
 */
class SecondHandProfileTest extends TestCase
{
    private function profile(array $data): SecondHandProfile
    {
        return new SecondHandProfile($data, ['guard' => false]);
    }

    public function testWhatsappNumberHasTheCountryCode(): void
    {
        $this->assertSame('41791234567', $this->profile(['phone' => '079 123 45 67'])->whatsappNumber());
        $this->assertSame('41791234567', $this->profile(['phone' => '+41 79 123 45 67'])->whatsappNumber());
        $this->assertSame('33612345678', $this->profile(['phone' => '0033 6 12 34 56 78'])->whatsappNumber());
    }

    public function testPhoneLinkKeepsDigitsAndPlus(): void
    {
        $this->assertSame('+41791234567', $this->profile(['phone' => '+41 (79) 123-45-67'])->phoneLink());
    }

    public function testSellerNeedsAllDetailsAndAVerifiedEmail(): void
    {
        $details = ['name' => 'Mum', 'phone' => '079', 'email' => 'a@example.com', 'city' => 'Renens'];
        $this->assertFalse($this->profile([])->isSeller());
        $this->assertFalse($this->profile(['city' => ' '] + $details)->isSeller());
        $this->assertTrue($this->profile($details)->isSeller());
        $this->assertFalse($this->profile($details + ['email_verified' => false])->canSell());
        $this->assertTrue($this->profile($details + ['email_verified' => true])->canSell());
    }
}
