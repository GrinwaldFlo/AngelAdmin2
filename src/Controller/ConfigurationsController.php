<?php
declare(strict_types=1);
namespace App\Controller;

/**
 * Configurations Controller
 *
 * @property \App\Model\Table\ConfigurationsTable $Configurations
 * @method \App\Model\Entity\Configuration[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ConfigurationsController extends AppController
{
  public array $paginate = [
      'limit' => 500,
      'maxLimit' => 1000,
      'order' => ['Configurations.id' => 'asc']
  ];
  /**
   * Index method
   *
   * @return \Cake\Http\Response|null|void Renders view
   */
  public function index()
  {
    $this->Authorization->authorize($this->Configurations->newEmptyEntity(), 'admin');

    $configurations = $this->paginate($this->Configurations);

    $this->set(compact('configurations'));
  }

  /**
   * View method
   *
   * @param string|null $id Configuration id.
   * @return \Cake\Http\Response|null|void Renders view
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function view(int|null $id = null)
  {
    $this->Authorization->authorize($this->Configurations->newEmptyEntity(), 'admin');

    $configuration = $this->Configurations->get($id, contain: []);

    $this->set('configuration', $configuration);
  }

  /**
   * Add method
   *
   * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
   */
  public function add()
  {
    $this->Authorization->authorize($this->Configurations->newEmptyEntity(), 'admin');

    $configuration = $this->Configurations->newEmptyEntity();
    if ($this->request->is('post'))
    {
      $configuration = $this->Configurations->patchEntity($configuration, $this->request->getData());
      if ($this->Configurations->save($configuration))
      {
        $this->Flash->success(__('The configuration has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The configuration could not be saved.'));
    }
    $this->set(compact('configuration'));
  }

  /**
   * Edit method
   *
   * @param string|null $id Configuration id.
   * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function edit(int|null $id = null)
  {
    $this->Authorization->authorize($this->Configurations->newEmptyEntity(), 'admin');

    $configuration = $this->Configurations->get($id, contain: []);
    if ($this->request->is(['patch', 'post', 'put']))
    {
      $configuration = $this->Configurations->patchEntity($configuration, $this->request->getData());
      if ($this->Configurations->save($configuration))
      {
        $this->Flash->success(__('The configuration has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The configuration could not be saved.'));
    }
    $this->set(compact('configuration'));
  }

  /**
   * Delete method
   *
   * @param string|null $id Configuration id.
   * @return \Cake\Http\Response|null|void Redirects to index.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function delete(int|null $id = null)
  {
    $this->Authorization->authorize($this->Configurations->newEmptyEntity(), 'admin');

    $this->request->allowMethod(['post', 'delete']);
    $configuration = $this->Configurations->get($id);
    if ($this->Configurations->delete($configuration))
    {
      $this->Flash->success(__('The configuration has been deleted.'));
    }
    else
    {
      $this->Flash->error(__('The configuration could not be deleted.'));
    }

    return $this->redirect(['action' => 'index']);
  }

}