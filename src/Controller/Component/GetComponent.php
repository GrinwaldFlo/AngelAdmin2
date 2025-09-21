<?php
namespace App\Controller\Component;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

class GetComponent extends Component
{
  public $name = 'Get';
  public function BillTemplates($id = null)
  {
    $Table = TableRegistry::getTableLocator()->get('BillTemplates');
    if ($id == null || $id < 1)
    {
      return $Table->find('all')->orderBy(['label' => 'ASC']);
    }
      return $Table->get($id);
  }

}