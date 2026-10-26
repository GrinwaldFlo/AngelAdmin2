<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SubventionCities Model: email address of the municipality of each city, used for the subsidy requests
 */
class SubventionCitiesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('subvention_cities');
        $this->setDisplayField('city');
        $this->setPrimaryKey('id');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('city')
            ->maxLength('city', 100)
            ->notEmptyString('city');

        $validator
            ->email('email')
            ->allowEmptyString('email');

        return $validator;
    }

    /**
     * @param string[] $cities
     * @return array<string, string> email by city, '' if unknown
     */
    public function emailsByCity(array $cities): array
    {
        $emails = array_fill_keys($cities, '');
        if (empty($cities)) {
            return $emails;
        }
        foreach ($this->find()->where(['city IN' => $cities]) as $row) {
            $emails[$row->city] = $row->email;
        }
        return $emails;
    }

    /**
     * Save the email of each city, invalid addresses are not saved
     *
     * @param array<string, string> $emails email by city
     * @return string[] cities whose email is invalid
     */
    public function saveEmails(array $emails): array
    {
        $invalid = [];
        foreach ($emails as $city => $email) {
            $row = $this->find()->where(['city' => $city])->first() ?? $this->newEntity(['city' => $city]);
            $row = $this->patchEntity($row, ['email' => trim((string)$email)]);
            if (!$this->save($row)) {
                $invalid[] = $city;
            }
        }
        return $invalid;
    }
}
