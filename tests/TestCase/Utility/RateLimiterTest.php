<?php

declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\RateLimiter;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Utility\RateLimiter
 */
class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear();
        DateTime::setTestNow('2026-11-11 10:00:00');
    }

    protected function tearDown(): void
    {
        DateTime::setTestNow(null);
        RateLimiter::clear();
        parent::tearDown();
    }

    public function testLimitInTheWindow(): void
    {
        $limiter = new RateLimiter('test', [60 => 2]);

        $this->assertTrue($limiter->attempt('a'));
        $this->assertTrue($limiter->attempt('a'));
        $this->assertFalse($limiter->attempt('a'));
        // The other keys have their own count
        $this->assertTrue($limiter->attempt('b'));
    }

    public function testWindowEnds(): void
    {
        $limiter = new RateLimiter('test', [60 => 1]);
        $this->assertTrue($limiter->attempt('a'));

        DateTime::setTestNow('2026-11-11 10:00:59');
        $this->assertFalse($limiter->attempt('a'));
        DateTime::setTestNow('2026-11-11 10:01:00');
        $this->assertTrue($limiter->attempt('a'));
    }

    public function testRefusedHitsAreNotCounted(): void
    {
        $limiter = new RateLimiter('test', [60 => 1]);
        $limiter->attempt('a');
        DateTime::setTestNow('2026-11-11 10:00:30');
        $this->assertFalse($limiter->attempt('a'));

        // Free 60 seconds after the first hit, not after the refused one
        DateTime::setTestNow('2026-11-11 10:01:00');
        $this->assertTrue($limiter->attempt('a'));
    }

    public function testAllTheWindowsAreChecked(): void
    {
        $limiter = new RateLimiter('test', [60 => 1, 3600 => 2]);
        $this->assertTrue($limiter->attempt('a'));
        DateTime::setTestNow('2026-11-11 10:01:00');
        $this->assertTrue($limiter->attempt('a'));
        DateTime::setTestNow('2026-11-11 10:02:00');
        $this->assertFalse($limiter->attempt('a'));
        DateTime::setTestNow('2026-11-11 11:00:00');
        $this->assertTrue($limiter->attempt('a'));
    }

    public function testKeysAreCaseInsensitiveAndLimitersSeparated(): void
    {
        $this->assertTrue((new RateLimiter('one', [60 => 1]))->attempt('Parent@Example.com'));

        $this->assertFalse((new RateLimiter('one', [60 => 1]))->attempt('parent@example.com'));
        $this->assertTrue((new RateLimiter('two', [60 => 1]))->attempt('parent@example.com'));
    }
}
