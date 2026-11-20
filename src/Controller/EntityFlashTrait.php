<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\EntityInterface;
use Cake\Utility\Hash;

/**
 * Flash message after saving an entity, with the first validation error when it failed
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
trait EntityFlashTrait
{
    /**
     * @return bool true if the entity was saved (no error)
     */
    protected function flashSaved(EntityInterface $entity, string $success, string $failure): bool
    {
        if (!$entity->hasErrors()) {
            $this->Flash->success($success);

            return true;
        }
        $errors = Hash::flatten($entity->getErrors());
        $this->Flash->error($failure . ($errors ? ' ' . reset($errors) : ''));

        return false;
    }

    /**
     * Flash message after deleting an entity: the errors of the delete rules (they tell why), or $failure
     * @return bool $deleted
     */
    protected function flashDeleted(bool $deleted, EntityInterface $entity, string $success, string $failure): bool
    {
        if ($deleted) {
            $this->Flash->success($success);

            return true;
        }
        $errors = Hash::flatten($entity->getErrors());
        $this->Flash->error($errors ? implode(' ', $errors) : $failure);

        return false;
    }
}
