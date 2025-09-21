<?php
declare(strict_types=1);
namespace App\Model\Table;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

/**
 * Fields Model
 *
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 * @property \App\Model\Table\FieldTypesTable&\Cake\ORM\Association\BelongsTo $FieldTypes
 *
 * @method \App\Model\Entity\Field newEmptyEntity()
 * @method \App\Model\Entity\Field newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Field[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Field get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\Field findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Field patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Field[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Field|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Field saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Field[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Field[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Field[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Field[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class FieldsTable extends Table
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

    $this->setTable('fields');
    $this->setDisplayField('member_id');
    $this->setPrimaryKey(['member_id', 'field_type_id']);

    $this->belongsTo('Members', [
        'foreignKey' => 'member_id',
        'joinType' => 'INNER',
    ]);
    $this->belongsTo('FieldTypes', [
        'foreignKey' => 'field_type_id',
        'joinType' => 'INNER',
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
      ->scalar('value')
      ->maxLength('value', 255)
      ->allowEmptyString('value');

    return $validator;
  }

  /**
   * Returns a rules checker object that will be used for validating
   * application integrity.
   *
   * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
   * @return \Cake\ORM\RulesChecker
   */
  public function buildRules(RulesChecker $rules): RulesChecker
  {
    $rules->add($rules->existsIn(['member_id'], 'Members'));
    $rules->add($rules->existsIn(['field_type_id'], 'FieldTypes'));

    return $rules;
  }

  public function AddMissing($m)
  {
    if (empty($m->fields))
      $m->fields = Array();

    $exists = Array();
    foreach ($m->fields as $field)
    {
      array_push($exists, $field->field_type->id);
    }
    $fieldTypes = TableRegistry::getTableLocator()->get('FieldTypes');

    if (empty($exists))
      $lst = $fieldTypes->find('all')->orderBy(['sort' => 'ASC']);
    else
      $lst = $fieldTypes->find('all')->where(["id NOT IN" => $exists])->orderBy(['sort' => 'ASC']);

    foreach ($lst as $item)
    {
      $newItem = $this->newEmptyEntity();
      $newItem->field_type = $item;
      $newItem->value = "";
      $newItem->member_id = $m->id;
      $newItem->field_type_id = $item->id;
      array_push($m->fields, $newItem);
    }
  }

  public function patchFields($member, $fields)
  {
    foreach ($fields as $key => $field)
    {
      $this->patchField($member, $key, $field);
    }
  }

  public function patchField($member, $key, $field)
  {
    foreach ($member->fields as $mFields)
    {
      if ($mFields->field_type_id == $key)
      {
        if ($mFields->value != $field)
        {
          $mFields->value = $field;
        }

        return;
      }
    }
  }

  public function saveFields($member, $fields)
  {

    foreach ($fields as $key => $field)
    {
      $this->saveField($member, $key, $field);
    }
  }

  public function saveField($member, $key, $field)
  {
    foreach ($member->fields as $mFields)
    {
      if ($mFields->field_type_id == $key)
      {
        if (empty($field) || $field == "0" || $field == "")
        {
          $this->delete($mFields);
        }
        elseif ($mFields->value != $field)
        {
          $mFields->member_id = $member->id;
          $mFields->value = $field;
          $this->save($mFields);
        }

        return;
      }
    }
  }

}