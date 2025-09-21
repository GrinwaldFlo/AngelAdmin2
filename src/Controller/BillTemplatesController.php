<?php
declare(strict_types=1);
namespace App\Controller;

/**
 * BillTemplates Controller
 *
 * @property \App\Model\Table\BillTemplatesTable $BillTemplates
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @method \App\Model\Entity\BillTemplate[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BillTemplatesController extends AppController
{
  public array $paginate = [
    'limit' => 500,
    'maxLimit' => 1000,
    'finder' => 'withSites',
  ];

  /**
   * Index method
   *
   * @return \Cake\Http\Response|null|void Renders view
   */
  public function index()
  {
    $this->Authorization->authorize($this->BillTemplates->newEmptyEntity(), 'admin');
    $billTemplates = $this->paginate($this->BillTemplates);

    $this->set(compact('billTemplates'));
  }

  /**
   * View method
   *
   * @param string|null $id Bill Template id.
   * @return \Cake\Http\Response|null|void Renders view
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function view(int|null $id = null)
  {
    $this->Authorization->authorize($this->BillTemplates->newEmptyEntity(), 'admin');
    $billTemplate = $this->BillTemplates->get($id, contain: ['Sites']);

    $this->set(compact('billTemplate'));
  }

  /**
   * Add method
   *
   * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
   */
  public function add()
  {
    $this->Authorization->authorize($this->BillTemplates->newEmptyEntity(), 'admin');

    $billTemplate = $this->BillTemplates->newEmptyEntity();
    if ($this->request->is('post')) {
      $billTemplate = $this->BillTemplates->patchEntity($billTemplate, $this->request->getData());
      if ($this->BillTemplates->save($billTemplate)) {
        $this->Flash->success(__('The invoice template has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The invoice template could not be saved.'));
    }
    $sites = $this->BillTemplates->Sites->find('list', ['limit' => 200]);
    $this->set(compact('billTemplate', 'sites'));
  }

  /**
   * Edit method
   *
   * @param string|null $id Bill Template id.
   * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function edit(int|null $id = null)
  {
    $this->Authorization->authorize($this->BillTemplates->newEmptyEntity(), 'admin');

    $billTemplate = $this->BillTemplates->get($id, contain: []);
    if ($this->request->is(['patch', 'post', 'put'])) {
      $billTemplate = $this->BillTemplates->patchEntity($billTemplate, $this->request->getData());
      if ($this->BillTemplates->save($billTemplate)) {
        $this->Flash->success(__('The invoice template has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The invoice template could not be saved.'));
    }
    $sites = $this->BillTemplates->Sites->find('list', ['limit' => 200]);
    $this->set(compact('billTemplate', 'sites'));
  }

  /**
   * Delete method
   *
   * @param string|null $id Bill Template id.
   * @return \Cake\Http\Response|null|void Redirects to index.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function delete(int|null $id = null)
  {
    $this->Authorization->authorize($this->BillTemplates->newEmptyEntity(), 'admin');

    $this->request->allowMethod(['post', 'delete']);
    $billTemplate = $this->BillTemplates->get($id);
    if ($this->BillTemplates->delete($billTemplate)) {
      $this->Flash->success(__('The invoice template has been deleted.'));
    } else {
      $this->Flash->error(__('The invoice template could not be deleted.'));
    }

    return $this->redirect(['action' => 'index']);
  }

}