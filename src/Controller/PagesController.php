<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;
use Cake\ORM\TableRegistry;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class PagesController extends AppController
{
  public $components = ['Slack'];

  public function beforeFilter(\Cake\Event\EventInterface $event)
  {
    parent::beforeFilter($event);
    // Configure the login action to not require authentication, preventing
    // the infinite redirect loop issue
    //$this->Authentication->addUnauthenticatedActions(['display']);
    $this->Authorization->skipAuthorization();
  }

  public function initialize(): void
  {
    parent::initialize();
    $this->loadComponent('Slack');
  }

  /**
   * Displays a view
   *
   * @param array ...$path Path segments.
   * @return \Cake\Http\Response|null
   * @throws \Cake\Http\Exception\ForbiddenException When a directory traversal attempt.
   * @throws \Cake\View\Exception\MissingTemplateException When the view file could not
   *   be found and in debug mode.
   * @throws \Cake\Http\Exception\NotFoundException When the view file could not
   *   be found and not in debug mode.
   * @throws \Cake\View\Exception\MissingTemplateException In debug mode.
   */
  public function display(...$path): ?Response
  {
    if (!$path) {
      return $this->redirect('/');
    }
    if (in_array('..', $path, true) || in_array('.', $path, true)) {
      throw new ForbiddenException();
    }
    $page = $subpage = null;

    if (!empty($path[0])) {
      $page = $path[0];
    }
    if (!empty($path[1])) {
      $subpage = $path[1];
    }
    $this->set(compact('page', 'subpage'));

    try {
      $this->home();
      return $this->render(implode('/', $path));
    } catch (MissingTemplateException $exception) {
      if (Configure::read('debug')) {
        throw $exception;
      }
      throw new NotFoundException();
    }
  }

  private function home()
  {
    $today = new \DateTime();
    $todayEarly = new \DateTime();
    $todayEarly->sub(new \DateInterval('PT12H'));

    $members = TableRegistry::getTableLocator()->get('Members');
    $bills = TableRegistry::getTableLocator()->get('Bills');
    $meetings = TableRegistry::getTableLocator()->get('Meetings');
    $contentsTable = TableRegistry::getTableLocator()->get('Contents');

    $curUser = $this->Authentication->getIdentity();
    $curMember = $members->findById($curUser->member_id)->contain('Teams')->firstOrFail();
    $teamList = array();
    foreach ($curMember->teams as $team) {
      array_push($teamList, $team->id);
    }

    $lateBills = $bills->find('all')
      ->contain(['Members', 'Sites'])
      ->where(['member_id' => $curUser->member_id])
      ->where(['paid' => 0])
      ->where(['canceled' => 0])
      ->all();

    if (empty($teamList)) {
      $smallMeetings = array();
      $bigMeetings = array();
      $doodleMeetings = array();
    } else {
      $smallMeetings = $meetings->find('all')->where(['team_id IN' => $teamList])->where(['meeting_date >' => $todayEarly])->where(['big_event =' => 0])->where(['doodle =' => 0])->orderBy(['meeting_date' => 'ASC'])->limit(5);
      $bigMeetings = $meetings->find('all')->where(['team_id IN' => $teamList])->where(['meeting_date >' => $todayEarly])->where(['big_event =' => 1])->orderBy(['meeting_date' => 'ASC'])->limit(5);
      $doodleMeetings = $meetings->find('all')->where(['team_id IN' => $teamList])->where(['meeting_date >' => $todayEarly])->where(['big_event =' => 0])->where(['doodle =' => 1])->orderBy(['meeting_date' => 'ASC'])->limit(5);
    }

    $presences = TableRegistry::getTableLocator()->get('Presences');

    foreach ($smallMeetings as $item) {
      if ($item->doodle) {
        $item->my = $presences->find('all')->where(['member_id' => $this->curMember->id])->where(['meeting_id' => $item->id])->first();
      }
    }
    foreach ($bigMeetings as $item) {
      if ($item->doodle) {
        $item->my = $presences->find('all')->where(['member_id' => $this->curMember->id])->where(['meeting_id' => $item->id])->first();
      }
    }
    foreach ($doodleMeetings as $item) {
      if ($item->doodle) {
        $item->my = $presences->find('all')->where(['member_id' => $this->curMember->id])->where(['meeting_id' => $item->id])->first();
      }
    }
    
    //$query->innerJoinWith('Teams')->where(['Teams.id IN' => $options['teamId']]);
    if (!empty($curMember->date_birth)) {
      $birthday = $today->format('m-d') == $curMember->date_birth->format('m-d');
    } else {
      $birthday = false;
    }

    // ** Contents
    $messages = $contentsTable->find('all')->where(['location =' => 2, 'team_id IN' => $this->curMember->TeamIds]);
    $contents = $contentsTable->find('all')->where(['location =' => 4, 'team_id IN' => $this->curMember->TeamIds]);

    $contentEvent = $contentsTable->find(
      'list',
      keyField: 'location',
      valueField: 'text'
    )->where(['location IN' => [5, 6, 7, 8, 9, 10]])->toArray();

    $slackMessages = $this->Slack->getSlack();

    $this->set(compact("messages", 'curMember', 'lateBills', 'birthday', 'smallMeetings', 'bigMeetings', 'doodleMeetings', 'contents', 'contentEvent', 'slackMessages'));
  }
}