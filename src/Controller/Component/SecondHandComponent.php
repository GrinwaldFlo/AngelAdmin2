<?php

declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Entity\SecondHandItem;
use App\Model\Entity\SecondHandProfile;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

/**
 * Second-hand items: settings of the module, pictures of the items, email confirmation
 *
 * @property \App\Controller\Component\ImageComponent $Image
 * @property \App\Controller\Component\MemberDocumentComponent $MemberDocument
 * @property \App\Controller\Component\MailComponent $Mail
 */
class SecondHandComponent extends Component
{
    protected array $components = ['Image', 'MemberDocument', 'Mail'];

    // Settings stored in the Configurations table
    public const ENABLED = 'secondHandEnabled';
    public const EXPIRY_MONTHS = 'secondHandExpiryMonths';
    public const DEFAULT_EXPIRY_MONTHS = 6;

    // Largest side of the pictures
    public const PICTURE_SIZE = 1200;

    public function isEnabled(): bool
    {
        return !empty($this->getController()->config[self::ENABLED]);
    }

    /**
     * Months after which the items expire
     */
    public function expiryMonths(): int
    {
        $months = (int)($this->getController()->config[self::EXPIRY_MONTHS] ?? self::DEFAULT_EXPIRY_MONTHS);

        return $months > 0 ? $months : self::DEFAULT_EXPIRY_MONTHS;
    }

    /**
     * Save a setting as a row of the Configurations table
     */
    public function saveSetting(string $label, string $value): void
    {
        $configurations = TableRegistry::getTableLocator()->get('Configurations');
        $row = $configurations->find()->where(['label' => $label])->first();
        if ($row === null) {
            $row = $configurations->newEmptyEntity();
            // The id of old databases is not auto increment
            $query = $configurations->find();
            $row->id = (int)$query->select(['maxId' => $query->func()->max('id')])->first()?->maxId + 1;
        }
        $row->label = $label;
        $row->value = $value;
        $configurations->saveOrFail($row);
        $this->getController()->config[$label] = $value;
    }

    /**
     * Save the picture of an item (JPEG or HEIC), resized to PICTURE_SIZE at most
     * Errors are shown with a flash message
     */
    public function savePicture(SecondHandItem $item, ?UploadedFileInterface $file): bool
    {
        $controller = $this->getController();
        if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
            $controller->Flash->error(__('Please add a picture.'));

            return false;
        }

        $folder = $item->folderPath();
        if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
            $controller->Flash->error(__('Failed to send picture'));

            return false;
        }

        $original = $folder . 'original.jpg';
        if (!$this->MemberDocument->processImageFile($file, $original)) {
            return false;
        }
        $this->Image->rotateFromExif($original);

        $saved = false;
        $size = @getimagesize($original);
        if ($size !== false) {
            [$width, $height] = $size;
            $resizeWidth = $width >= $height && $width > self::PICTURE_SIZE ? self::PICTURE_SIZE : 0;
            $resizeHeight = $height > $width && $height > self::PICTURE_SIZE ? self::PICTURE_SIZE : 0;
            $saved = (bool)$this->Image->resize($original, $item->picturePath(), $resizeWidth, $resizeHeight, 90);
        }
        @unlink($original);

        if (!$saved) {
            $controller->Flash->error(__('Failed to process image'));
        }

        return $saved;
    }

    /**
     * Send the link confirming the email of a seller
     */
    public function sendEmailConfirmation(SecondHandProfile $profile): bool
    {
        $controller = $this->getController();
        $url = Router::url(['controller' => 'SecondHand', 'action' => 'confirmEmail', $profile->email_token], true);
        $content = __('Hello {0},', h($profile->name)) . "\n"
            . __('Please confirm your email address to sell items on the second-hand page:') . "\n"
            . '<a href="' . h($url) . '">' . h($url) . '</a>';

        try {
            $this->Mail->deliver(
                $profile->email,
                $profile->name,
                $controller->config['clubName'] . ' - ' . __('Confirm your email address'),
                ['content' => $content]
            );
        } catch (Throwable $exception) {
            $controller->Flash->error(__('The email could not be sent: {0}', $exception->getMessage()));

            return false;
        }

        return true;
    }
}
