<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * SecondHandItem Entity: item for sale, its picture is in a folder named by its hash so its URL cannot be guessed
 *
 * @property int $id
 * @property int $profile_id
 * @property int $category_id
 * @property string $description
 * @property string $size
 * @property string $price
 * @property string $hash
 * @property \Cake\I18n\DateTime|null $sold
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \App\Model\Entity\SecondHandProfile $profile
 * @property \App\Model\Entity\SecondHandCategory $category
 */
class SecondHandItem extends Entity
{
    protected array $_accessible = [
        'category_id' => true,
        'description' => true,
        'size' => true,
        'price' => true,
    ];

    public function folderPath(): string
    {
        return WWW_ROOT . 'img/secondhand/' . $this->hash . '/';
    }

    public function picturePath(): string
    {
        return $this->folderPath() . 'picture.jpg';
    }

    public function pictureUrl(): string
    {
        return '/img/secondhand/' . $this->hash . '/picture.jpg';
    }

    /**
     * Items created before this date are expired
     * @param int $months duration set by the admin
     */
    public static function expiryLimit(int $months): DateTime
    {
        return DateTime::now()->subMonths($months);
    }

    /**
     * @param int $months duration set by the admin
     */
    public function isExpired(int $months): bool
    {
        return $this->created !== null && $this->created < self::expiryLimit($months);
    }
}
