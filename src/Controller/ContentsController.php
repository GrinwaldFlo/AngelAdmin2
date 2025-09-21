<?php
declare(strict_types=1);
namespace App\Controller;
use Cake\ORM\TableRegistry;

/**
 * Contents Controller
 *
 * @property \App\Model\Table\ContentsTable $Contents
 * @method \App\Model\Entity\Content[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ContentsController extends AppController
{
  public array $paginate = [
      'limit' => 500,
      'maxLimit' => 1000,
      'order' => ['location' => 'asc', 'Contents.sort' => 'asc']
  ];
  /**
   * Index method
   *
   * @return \Cake\Http\Response|null|void Renders view
   */
  public function index()
  {
    $this->Authorization->authorize($this->Contents->newEmptyEntity(), 'editor');


    $contents = $this->paginate($this->Contents);

    $this->set(compact('contents'));
  }

  /**
   * View method
   *
   * @param string|null $id Content id.
   * @return \Cake\Http\Response|null|void Renders view
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function view(int|null $id = null)
  {
    $this->Authorization->authorize($this->Contents->newEmptyEntity(), 'editor');
    $content = $this->Contents->get($id, contain: ['Teams']);

    $this->set('content', $content);
  }

  /**
   * Add method
   *
   * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
   */
  public function add()
  {
    $this->Authorization->authorize($this->Contents->newEmptyEntity(), 'editor');

    $content = $this->Contents->newEmptyEntity();
    if ($this->request->is('post'))
    {
      $content = $this->Contents->patchEntity($content, $this->request->getData());
      if ($this->Contents->save($content))
      {
        $this->Flash->success(__('The content has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The content could not be saved.'));
    }
    $teams = $this->getTeamsActiv();
    $this->set(compact('content', 'teams'));
  }

  /**
   * Edit method
   *
   * @param string|null $id Content id.
   * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function edit(int|null $id = null)
  {
    $this->Authorization->authorize($this->Contents->newEmptyEntity(), 'editor');

    $content = $this->Contents->get($id, contain: []);
    if ($this->request->is(['patch', 'post', 'put']))
    {
      $content = $this->Contents->patchEntity($content, $this->request->getData());
      if($content->team_id == null)
        $content->team_id = 0;
      if ($this->Contents->save($content))
      {
        $this->Flash->success(__('The content has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The content could not be saved.'));
    }
    $teams = $this->getTeamsActiv();
    $this->set(compact('content', 'teams'));
  }

  /**
   * Delete method
   *
   * @param string|null $id Content id.
   * @return \Cake\Http\Response|null|void Redirects to index.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function delete(int|null $id = null)
  {
    $this->Authorization->authorize($this->Contents->newEmptyEntity(), 'editor');

    $this->request->allowMethod(['post', 'delete']);
    $content = $this->Contents->get($id);
    if ($this->Contents->delete($content))
    {
      $this->Flash->success(__('The content has been deleted.'));
    }
    else
    {
      $this->Flash->error(__('The content could not be deleted.'));
    }

    return $this->redirect(['action' => 'index']);
  }

  public function info()
  {
    $this->Authorization->skipAuthorization();

    $contents = $this->Contents->find('all')->where(['location =' => 3, 'team_id IN' => $this->curMember->TeamIds]);

    $teams = TableRegistry::getTableLocator()->get('Teams');
    $teams = $teams->find('all')->where(['active =' => 1])->orderBy(['name' => 'ASC']);

    $this->set(compact('contents', 'teams'));
  }

}