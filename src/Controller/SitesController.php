<?php
declare(strict_types=1);
namespace App\Controller;

/**
 * Sites Controller
 *
 * @property \App\Model\Table\SitesTable $Sites
 * @method \App\Model\Entity\Site[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class SitesController extends AppController
{
  /**
   * Index method
   *
   * @return \Cake\Http\Response|null|void Renders view
   */
  public function index()
  {
    $this->Authorization->authorize($this->Sites->newEmptyEntity(), 'admin');
    $sites = $this->paginate($this->Sites);

    $this->set(compact('sites'));
  }

  /**
   * View method
   *
   * @param string|null $id Site id.
   * @return \Cake\Http\Response|null|void Renders view
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function view(int|null $id = null)
  {
    $this->Authorization->authorize($this->Sites->newEmptyEntity(), 'admin');
    $site = $this->Sites->get($id, contain: []);

    $this->set('site', $site);
  }

  /**
   * Add method
   *
   * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
   */
  public function add()
  {
    $this->Authorization->authorize($this->Sites->newEmptyEntity(), 'admin');
    $site = $this->Sites->newEmptyEntity();
    if ($this->request->is('post'))
    {
      $site = $this->Sites->patchEntity($site, $this->request->getData());
      if ($this->Sites->save($site))
      {
        $this->Flash->success(__('The site has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The site could not be saved.'));
    }
    $this->set(compact('site'));
  }

  /**
   * Edit method
   *
   * @param string|null $id Site id.
   * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function edit(int|null $id = null)
  {
    $this->Authorization->authorize($this->Sites->newEmptyEntity(), 'admin');
    $site = $this->Sites->get($id, contain: []);
    if ($this->request->is(['patch', 'post', 'put']))
    {
      $site = $this->Sites->patchEntity($site, $this->request->getData());
      if ($this->Sites->save($site))
      {
        $this->Flash->success(__('The site has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The site could not be saved.'));
    }
    $this->set(compact('site'));
  }

  /**
   * Delete method
   *
   * @param string|null $id Site id.
   * @return \Cake\Http\Response|null|void Redirects to index.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function delete(int|null $id = null)
  {
    $this->Authorization->authorize($this->Sites->newEmptyEntity(), 'admin');
    $this->request->allowMethod(['post', 'delete']);
    $site = $this->Sites->get($id);
    if ($this->Sites->delete($site))
    {
      $this->Flash->success(__('The site has been deleted.'));
    }
    else
    {
      $this->Flash->error(__('The site could not be deleted.'));
    }

    return $this->redirect(['action' => 'index']);
  }

}