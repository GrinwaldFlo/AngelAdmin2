<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\SecondHandLog;
use App\Model\Entity\SecondHandProfile;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * SecondHandProfiles Model: second-hand terms accepted by each member, and its seller details
 *
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 * @property \App\Model\Table\SecondHandItemsTable&\Cake\ORM\Association\HasMany $SecondHandItems
 */
class SecondHandProfilesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('second_hand_profiles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Members', [
            'foreignKey' => 'member_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('SecondHandItems', [
            'foreignKey' => 'profile_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    /**
     * The log is kept, the items still for sale are closed as they do not exist anymore
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        TableRegistry::getTableLocator()->get('SecondHandLogs')->closeForSale($entity->member_id, SecondHandLog::DELETED);
    }

    /**
     * Seller form: every field is mandatory
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->notEmptyString('name');

        $validator
            ->scalar('phone')
            ->maxLength('phone', 30)
            ->notEmptyString('phone')
            ->regex('phone', '/^\+?[\d\s().\/-]{6,}$/', __('This phone number is not valid'));

        $validator
            ->boolean('whatsapp');

        $validator
            ->email('email')
            ->maxLength('email', 255)
            ->notEmptyString('email');

        $validator
            ->scalar('city')
            ->maxLength('city', 100)
            ->notEmptyString('city');

        return $validator;
    }

    /**
     * Profile of a member, a new one (not saved) if missing
     */
    public function forMember(int $memberId): SecondHandProfile
    {
        $profile = $this->find()->where(['member_id' => $memberId])->first();
        if ($profile === null) {
            $profile = $this->newEmptyEntity();
            $profile->member_id = $memberId;
        }

        return $profile;
    }

    public function acceptTerms(SecondHandProfile $profile): bool
    {
        $profile->terms_accepted = DateTime::now();

        return (bool)$this->save($profile, ['validate' => false]);
    }

    /**
     * Save the seller details. The email of the member is verified, any other one needs the confirmation link
     * @param string|null $memberEmail main email of the member
     * @return bool saved
     */
    public function saveSeller(SecondHandProfile $profile, array $data, ?string $memberEmail): bool
    {
        $oldEmail = $profile->email;
        $this->patchEntity($profile, $data);
        if ($profile->hasErrors()) {
            return false;
        }

        $profile->email = trim($profile->email);
        if (!empty($memberEmail) && strcasecmp($profile->email, trim($memberEmail)) == 0) {
            $profile->email_verified = true;
            $profile->email_token = null;
        } elseif (strcasecmp($profile->email, (string)$oldEmail) != 0 || $profile->isNew()) {
            $profile->email_verified = false;
            $profile->email_token = bin2hex(random_bytes(16));
        }

        return (bool)$this->save($profile);
    }

    /**
     * Confirm the email of the confirmation link
     * @return \App\Model\Entity\SecondHandProfile|null null = unknown or already used link
     */
    public function confirmEmail(string $token): ?SecondHandProfile
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        $profile = $this->find()->where(['email_token' => $token])->first();
        if ($profile === null) {
            return null;
        }
        $profile->email_verified = true;
        $profile->email_token = null;

        return $this->save($profile, ['validate' => false]) ? $profile : null;
    }
}
