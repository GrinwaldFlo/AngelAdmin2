<?php
declare(strict_types=1);
namespace App\Controller;

/**
 * FieldTypes Controller
 *
 * @property \App\Model\Table\FieldTypesTable $FieldTypes
 * @method \App\Model\Entity\FieldType[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class FieldTypesController extends AppController
{
    public array $paginate = [
      'limit' => 500,
      'maxLimit' => 1000,
      'order' => ['FieldTypes.sort' => 'asc']
  ];
    
  /**
   * Index method
   *
   * @return \Cake\Http\Response|null|void Renders view
   */
  public function index()
  {
    $this->Authorization->authorize($this->FieldTypes->newEmptyEntity(), 'admin');
    $fieldTypes = $this->paginate($this->FieldTypes);

    $this->set(compact('fieldTypes'));
  }

  /**
   * View method
   *
   * @param string|null $id Field Type id.
   * @return \Cake\Http\Response|null|void Renders view
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function view(int|null $id = null)
  {
    $this->Authorization->authorize($this->FieldTypes->newEmptyEntity(), 'admin');

    $fieldType = $this->FieldTypes->get($id, contain: ['Fields']);

    $this->set('fieldType', $fieldType);
  }

  /**
   * Add method
   *
   * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
   */
  public function add()
  {
    $this->Authorization->authorize($this->FieldTypes->newEmptyEntity(), 'admin');

    $fieldType = $this->FieldTypes->newEmptyEntity();
    if ($this->request->is('post'))
    {
      $fieldType = $this->FieldTypes->patchEntity($fieldType, $this->request->getData());
      if ($this->FieldTypes->save($fieldType))
      {
        $this->Flash->success(__('The field has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The field could not be saved.'));
    }
    $this->set(compact('fieldType'));
  }

  /**
   * Edit method
   *
   * @param string|null $id Field Type id.
   * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function edit(int|null $id = null)
  {
    $this->Authorization->authorize($this->FieldTypes->newEmptyEntity(), 'admin');

    $fieldType = $this->FieldTypes->get($id, contain: []);
    if ($this->request->is(['patch', 'post', 'put']))
    {
      $fieldType = $this->FieldTypes->patchEntity($fieldType, $this->request->getData());
      if ($this->FieldTypes->save($fieldType))
      {
        $this->Flash->success(__('The field has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The field could not be saved.'));
    }
    $this->set(compact('fieldType'));
  }

  /**
   * Delete method
   *
   * @param string|null $id Field Type id.
   * @return \Cake\Http\Response|null|void Redirects to index.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function delete(int|null $id = null)
  {
    $this->Authorization->authorize($this->FieldTypes->newEmptyEntity(), 'admin');

    $this->request->allowMethod(['post', 'delete']);
    $fieldType = $this->FieldTypes->get($id);
    if ($this->FieldTypes->delete($fieldType))
    {
      $this->Flash->success(__('The field has been deleted.'));
    }
    else
    {
      $this->Flash->error(__('The field could not be deleted.'));
    }

    return $this->redirect(['action' => 'index']);
  }

}