<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\Utility\Filesystem;

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

    /**
     * Path of the picture for HtmlHelper::image(), relative to webroot/img
     */
    public function pictureImage(): string
    {
        return 'secondhand/' . $this->hash . '/picture.jpg';
    }

    /**
     * The hash names the picture folder, it is needed before the first save
     */
    public function ensureHash(): void
    {
        if (empty($this->hash)) {
            $this->hash = bin2hex(random_bytes(20));
        }
    }

    public function deleteFolder(): void
    {
        if (!empty($this->hash) && is_dir($this->folderPath())) {
            (new Filesystem())->deleteDir($this->folderPath());
        }
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
