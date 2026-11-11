<?php

declare(strict_types=1);

namespace App\Utility;

use Cake\Cache\Cache;
use Cake\I18n\DateTime;

/**
 * Limit how often something can happen for a key (an IP, a member...), e.g. sending emails.
 * Each limiter has one or more windows: [seconds => maximum number of hits in these seconds].
 * The hits are stored in the `rate_limit` cache, so the longest window must stay below its duration (1 day).
 */
class RateLimiter
{
    public const CACHE = 'rate_limit';

    /**
     * @param string $name name of the limiter, keys of different limiters never mix
     * @param array<int, int> $windows [seconds => maximum hits]
     */
    public function __construct(private string $name, private array $windows)
    {
    }

    /**
     * Count a hit for the key if no window is full
     * @return bool false if the limit is reached (the hit is not counted)
     */
    public function attempt(string $key): bool
    {
        $now = DateTime::now()->getTimestamp();
        $hits = $this->hits($key, $now);
        foreach ($this->windows as $seconds => $max) {
            $inWindow = array_filter($hits, fn(int $time) => $time > $now - $seconds);
            if (count($inWindow) >= $max) {
                return false;
            }
        }

        $hits[] = $now;
        Cache::write($this->cacheKey($key), $hits, self::CACHE);

        return true;
    }

    /**
     * Forget all the hits (for all the limiters)
     */
    public static function clear(): void
    {
        Cache::clear(self::CACHE);
    }

    /**
     * Hits of the key still inside the longest window
     * @return int[] timestamps
     */
    private function hits(string $key, int $now): array
    {
        $hits = Cache::read($this->cacheKey($key), self::CACHE);
        $longest = max(array_keys($this->windows));

        return array_values(array_filter(
            is_array($hits) ? $hits : [],
            fn($time) => is_int($time) && $time > $now - $longest
        ));
    }

    /**
     * Hashed: a cache key accepts only some characters, and the emails are not stored in clear
     */
    private function cacheKey(string $key): string
    {
        return sha1($this->name . '|' . mb_strtolower($key));
    }
}
