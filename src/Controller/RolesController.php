<?php
declare(strict_types=1);
namespace App\Controller;

/**
 * Roles Controller
 *
 * @property \App\Model\Table\RolesTable $Roles
 *
 * @method \App\Model\Entity\Role[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RolesController extends AppController
{
  /**
   * Index method
   *
   * @return \Cake\Http\Response|null|void Renders view
   */
  public function index()
  {
    $this->Authorization->authorize($this->Roles->newEmptyEntity(), 'admin');
    $roles = $this->paginate($this->Roles);

    $this->set(compact('roles'));
  }

  /**
   * View method
   *
   * @param string|null $id Role id.
   * @return \Cake\Http\Response|null|void Renders view
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function view(int|null $id = null)
  {
    $this->Authorization->authorize($this->Roles->newEmptyEntity(), 'admin');
    $role = $this->Roles->get($id, contain: ['Users']);

    $this->set('role', $role);
  }

  /**
   * Add method
   *
   * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
   */
  public function add()
  {
    $this->Authorization->authorize($this->Roles->newEmptyEntity(), 'admin');
    $role = $this->Roles->newEmptyEntity();
    if ($this->request->is('post'))
    {
      $role = $this->Roles->patchEntity($role, $this->request->getData());
      if ($this->Roles->save($role))
      {
        $this->Flash->success(__('The role has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The role could not be saved.'));
    }
    $this->set(compact('role'));
  }

  /**
   * Edit method
   *
   * @param string|null $id Role id.
   * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function edit(int|null $id = null)
  {
    $this->Authorization->authorize($this->Roles->newEmptyEntity(), 'admin');
    $role = $this->Roles->get($id, contain: []);
    if ($this->request->is(['patch', 'post', 'put']))
    {
      $role = $this->Roles->patchEntity($role, $this->request->getData());
      if ($this->Roles->save($role))
      {
        $this->Flash->success(__('The role has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The role could not be saved.'));
    }
    $this->set(compact('role'));
  }

}