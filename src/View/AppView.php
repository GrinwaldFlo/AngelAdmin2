<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\View;

use BootstrapUI\View\UIView;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @property \App\View\Helper\MyHelper $my
 * @link https://book.cakephp.org/5/en/views.html#the-app-view
 */
class AppView extends UIView
{
  /**
   * Initialization hook method.
   *
   * Use this method to add common initialization code like adding helpers.
   *
   * e.g. `$this->addHelper('Html');`
   *
   * @return void
   */
  public function initialize(): void
  {
    parent::initialize();
    $this->addHelper('Pdf');
    $this->addHelper('My');
    $this->setLayout('default');
  }

  public function tableFieldCurrency($label, $value)
  {
    if ($value) {
      return '<tr><th>' . $label . '</th><td>' . $this->Number->currency($value, 'CHF') . '</td></tr>';
    } else {
      return '';
    }
  }

  public function tableField($label, $value)
  {
    if ($value) {
      return '<tr><th>' . $label . '</th><td>' . h($value) . '</td></tr>';
    } else {
      return '';
    }
  }

  public function backButton($refer, $floating = false)
  {
    if ($floating) {
      return $this->Html->link('<i class="gg-arrow-left-r"></i>', $refer, ['escape' => false]);
    }

    return $this->Html->link('<i class="gg-arrow-left-r"></i>', $refer, ['escape' => false]);
  }

  public function backButtonCtrl($controler, $action, $floating = false)
  {
    if ($floating) {
      return $this->Html->link('<i class="gg-arrow-left-r"></i>', ['controller' => $controler, 'action' => $action], ['escape' => false]);
    }

    return $this->Html->link('<i class="gg-arrow-left-r"></i>', ['controller' => $controler, 'action' => $action], ['escape' => false, 'style' => 'width: 60px;']);
  }

}