<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Mailing;
use App\Model\Entity\MailingItem;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Mailings Model: emails sent to teams, one mailing per batch
 *
 * @property \App\Model\Table\MailingItemsTable&\Cake\ORM\Association\HasMany $MailingItems
 * @method \App\Model\Entity\Mailing newEmptyEntity()
 * @method \App\Model\Entity\Mailing get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 */
class MailingsTable extends Table
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

        $this->setTable('mailings');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('MailingItems', [
            'foreignKey' => 'mailing_id',
            'dependent' => true,
        ]);
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('title')
            ->maxLength('title', 100)
            ->requirePresence('title', 'create')
            ->notEmptyString('title')
            ->add('title', 'placeholders', ['rule' => [$this, 'validPlaceholders']]);

        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content')
            ->add('content', 'placeholders', ['rule' => [$this, 'validPlaceholders']]);

        $validator
            ->scalar('teams')
            ->maxLength('teams', 1000);

        $validator
            ->scalar('team_id_list')
            ->maxLength('team_id_list', 255);

        return $validator;
    }

    /**
     * Validation rule: every placeholder of the text can be replaced
     * @return string|true error message listing the wrong placeholders
     */
    public function validPlaceholders(mixed $value): string|bool
    {
        $invalid = Mailing::invalidPlaceholders((string)$value);
        if (empty($invalid)) {
            return true;
        }

        return __('Invalid placeholders: {0}. Available placeholders: {1}', implode(', ', $invalid), implode(', ', array_keys(Mailing::placeholders())));
    }

    /**
     * Build a mailing to the active members (with an email) of active teams, each member once
     * even if they are in several of the teams
     * @param array $data subject and content of the mailing ('title', 'content')
     * @param array $teamIds teams receiving the mailing
     * @return \App\Model\Entity\Mailing not saved, with validation errors if any
     */
    public function newForTeams(array $data, array $teamIds): Mailing
    {
        $teams = $this->MailingItems->Members->Teams->find('list')
            ->where(['Teams.id IN' => $teamIds ?: [0], 'Teams.active' => true])
            ->orderBy(['Teams.name' => 'ASC'])
            ->toArray();

        $members = $this->MailingItems->Members->find()
            ->select(['Members.id', 'Members.email'])
            ->distinct()
            ->innerJoinWith('Teams', fn ($q) => $q->where(['Teams.id IN' => array_keys($teams) ?: [0]]))
            ->where(['Members.active' => true, 'Members.email !=' => ''])
            ->orderBy(['Members.id' => 'ASC'])
            ->disableHydration()
            ->all();

        $items = [];
        foreach ($members as $member) {
            $items[] = ['member_id' => $member['id'], 'email' => trim($member['email']), 'status' => MailingItem::PENDING];
        }

        $mailing = $this->newEntity([
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'teams' => mb_substr(implode(', ', $teams), 0, 1000),
            'team_id_list' => implode(',', array_keys($teams)),
            'status' => Mailing::IN_PROGRESS,
            'mailing_items' => $items,
        ], ['associated' => ['MailingItems']]);

        if (empty($teams)) {
            $mailing->setError('team_ids', __('Select at least one team'));
        } elseif (empty($items)) {
            $mailing->setError('team_ids', __('There is no active member with an email in these teams'));
        }

        return $mailing;
    }

    /**
     * New mailing with the subject, content and teams of an existing one (not saved)
     * @return array [\App\Model\Entity\Mailing, team ids]
     */
    public function copy(Mailing $source): array
    {
        $mailing = $this->newEntity(['title' => $source->title, 'content' => $source->content], ['validate' => false]);
        $teamIds = array_values(array_filter(array_map('intval', explode(',', (string)$source->team_id_list))));

        // Mailings created before the ids were kept: find the teams by their names
        if (empty($teamIds) && $source->teams !== '') {
            $teamIds = $this->MailingItems->Members->Teams->find()
                ->select(['Teams.id'])
                ->where(['Teams.name IN' => explode(', ', $source->teams)])
                ->all()
                ->extract('id')
                ->toList();
        }

        return [$mailing, $teamIds];
    }
}
