<?php
declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;

/**
 * FieldType Entity
 *
 * @property int $id
 * @property string $label
 * @property int $style
 * @property int $member_edit
 * @property int $sort
 * @property int $hidden
 * @property int $mandatory
 *
 * @property \App\Model\Entity\Field[] $fields
 * @property string $style_str //Hardcoded list of styles, used in templates
 */
class FieldType extends Entity
{
  /**
   * Fields that can be mass assigned using newEntity() or patchEntity().
   *
   * Note that when '*' is set to true, this allows all unspecified fields to
   * be mass assigned. For security purposes, it is advised to set '*' to false
   * (or remove it), and explicitly make individual fields accessible as needed.
   *
   * @var array
   */
  protected array $_accessible = [
    'label' => true,
    'style' => true,
    'member_edit' => true,
    'fields' => true,
    'sort' => true,
    'hidden' => true,
    'mandatory' => true,
  ];
  /**
   * Virtual fields that are computed from other fields.
   *
   * @var array
   */
  protected array $_virtual = ['style_str'];

  protected function _getStyleList()
  {
    return [0 => __x('Value type of a field', 'Text'), 1 => __('Email'), 2 => __('Phone'), 3 => __('Number'), 4 => __('Checkbox'), 5 => __('Date'), 6 => __('Picture'), 7 => __('Cloth Size')];
  }

  protected function _getStyleStr()
  {
    return isset($styleList[$this->style]) ? $this->_getStyleList() : '';
  }

}