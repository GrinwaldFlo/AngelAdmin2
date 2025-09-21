<?php
declare(strict_types=1);
namespace App\Controller;
use Cake\Utility\Security;
use Cake\Routing\Router;
use Cake\Mailer\Mailer;
use Cake\Core\Configure;

class UsersController extends AppController
{
    public array $paginate = [
        'limit' => 500,
        'maxLimit' => 1000,
        'finder' => 'withRoleMembers',
    ];

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        // Configure the login action to not require authentication, preventing
        // the infinite redirect loop issue
        $this->Authentication->addUnauthenticatedActions(['login', 'register', 'forgotPassword', 'reset', 'setLanguage', 'emailLogin']);
    }

    public function login()
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        // Log referrer information for debugging external link issues
        $referrer = $this->request->getHeaderLine('Referer');
        $userAgent = $this->request->getHeaderLine('User-Agent');

        // Debug logging for external link troubleshooting
        if (!empty($referrer) && strpos($referrer, 'google') !== false) {
            $this->log(sprintf(
                'External login attempt from Google service - Referrer: %s, User-Agent: %s, Auth Result: %s',
                $referrer,
                $userAgent,
                $result->isValid() ? 'valid' : 'invalid'
            ), 'info');
        }

        // regardless of POST or GET, redirect if user is logged in
        if ($result->isValid()) {
            // Regenerate session ID for security on successful login
            $this->getRequest()->getSession()->renew();

            // redirect to /articles after login success
            $redirect = $this->request->getQuery('redirect', '/');
            $redirect = str_replace(Configure::read('domain') . "/", "/", $redirect);
            $curUser = $this->Users->get($this->Authentication->getIdentity()->id);
            $curUser->lastLogin = new \DateTime();
            $this->Users->save($curUser);
            return $this->redirect($redirect);
        }

        // display error if user submitted and authentication failed
        if ($this->request->is('post') && !$result->isValid()) {
            $this->Flash->error(__('Invalid username or password'));
            $this->Mail->mailNotif(__('Wrong password entered for {0}', $this->request->getData()['username']), '', null, 0);
        }

        // If coming from external source and not authenticated, show helpful message
        if (!empty($referrer) && strpos($referrer, 'google') !== false && !$result->isValid()) {
            $this->Flash->info(__('You were redirected from an external service. Please login to continue.'));
        }
    }

    public function logout()
    {
        $this->Authorization->skipAuthorization();
        $result = $this->Authentication->getResult();
        // regardless of POST or GET, redirect if user is logged in
        if ($result->isValid()) {
            $this->Authentication->logout();
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
    }

    public function index()
    {
        $this->Authorization->authorize($this->Users->newEmptyEntity(), 'admin');
        $users = $this->paginate($this->Users);

        $this->set(compact('users'));
    }

    public function view($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Roles', 'Members'],
        ]);
        $this->Authorization->authorize($user, 'view');

        $this->set('user', $user);
    }

    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        $this->Authorization->authorize($user, 'admin');

        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved.'));
        }
        $roles = $this->Users->Roles->find('list', ['limit' => 200]);
        $members = $this->Users->Members->find('list', ['limit' => 200]);
        $this->set(compact('user', 'roles', 'members'));
    }

    public function edit(int|null $id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => [],
        ]);
        $this->Authorization->authorize($user, 'edit');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved.'));
        }
        $roles = $this->Users->Roles->find('list');
        $members = $this->Users->Members->find('list', [
            'valueField' => function ($member) {
                return $member->get('label');
            }
        ]);

        $this->set(compact('user', 'roles', 'members'));
    }

    public function register($memberHash)
    {
        $this->Authorization->skipAuthorization();
        $user = $this->Users->newEmptyEntity();
        $member = $this->Users->Members->findByHash($memberHash)->FirstOrFail();

        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            $user->member_id = $member->id;
            $user->role_id = 4;
            $user->pass_key = "";
            $user->tokenhash = "";
            $passwordMatch = $this->request->getData()['password'] == $this->request->getData()['passwordConfirmation'];

            if (!$passwordMatch) {
                $this->Flash->error(__('Please enter twice the same passowrd'));
            } else if ($this->Users->save($user)) {
                $this->Flash->success(__('You have been successfully registered, now you can login'));
                return $this->redirect('/');
            } else {
                $this->Flash->error(__('The user could not be saved.'));
            }
        }
        $this->set(compact('user', 'member'));
    }

    public function forgotPassword()
    {
        $this->Authorization->skipAuthorization();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $emailOrUsername = $this->request->getData('email_or_username');

            $user = $this->Users->find('all', [
                'conditions' => [
                    'OR' => [
                        'Members.email' => $emailOrUsername,
                        'Users.username' => $emailOrUsername
                    ]
                ],
                'contain' => ['Members']
            ])->first();

            if (!empty($user)) {
                $user->tokenhash = Security::hash('CakePHP Framework', 'sha1', $user->username . rand(0, 100));
                $this->Users->save($user);

                $url = Router::url(array('controller' => 'users', 'action' => 'reset', $user->tokenhash), true);

                $mailer = new Mailer();

                $title = __('Reset your password');
                $mailer->setViewVars(['url' => $url]);
                $mailer->setViewVars(['title' => $title]);
                $mailer->setViewVars(['name' => $user->member->FullName]);

                $mailer
                    ->setEmailFormat('both')
                    ->setTo($user->member->email, $user->member->FullName)
                    ->setFrom($this->config['email'], $this->config['emailName'])
                    ->setSubject($title)
                    ->viewBuilder()
                    ->setTemplate('resetpsw')
                    ->setLayout('default');

                $mailer->deliver();
            }
            $this->Flash->success(__('If this email or username exists, a reset link has been sent to the associated email'));
        }
        return;
    }

    function reset($token = null)
    {
        $this->Authorization->skipAuthorization();

        if (empty($token)) {
            $this->Flash->error(__('Missing token'));
            return $this->redirect('/');
        }

        $user = $this->Users->findByTokenhash($token)->Contain('Members')->firstOrFail();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('Your password has been updated'));

                return $this->redirect('/');
            }
            $this->Flash->error(__('The user could not be saved.'));
        }
        $this->set(compact('user'));


        /*
          if (!empty($token))
          {
          $u = $this->User->findBytokenhash($token);
          if ($u)
          {
          $this->User->id = $u['User']['id'];
          if (!empty($this->data))
          {
          $this->User->data = $this->data;
          $this->User->data['User']['username'] = $u['User']['username'];
          $new_hash = sha1($u['User']['username'] . rand(0, 100));
          //created token
          $this->User->data['User']['tokenhash'] = $new_hash;
          if ($this->User->validates(array('fieldList' => array('password', 'password_confirm'))))
          {
          if ($this->User->save($this->User->data))
          {
          $this->Session->setFlash('Le mot de passe a été mis à jour');
          $this->redirect(array('controller' => 'users', 'action' => 'login'));
          }
          }
          else
          {
          $this->set('errors', $this->User->invalidFields());
          }
          }
          }
          else
          {
          $this->Session->setFlash("Le reset n'a pas fonctionné");
          }
          }
          else
          {
          $this->redirect('/');
          } */
    }

    public function setLanguage($lng = null)
    {
        $this->Authorization->skipAuthorization();

        if ($lng == null || ($lng != 'en' && $lng != 'fr' && $lng != 'de' && $lng != 'es' && $lng != 'it')) {
            $this->Flash->error(__("This language doesn't exists"));
            return $this->redirect('/');
        }

        $this->getPrefSessionStr('lng', $lng, 'fr');
        return $this->redirect($this->referer());
    }

    public function emailLogin()
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod(['post']);

        if ($this->request->is('post')) {
            $fullName = trim($this->request->getData('full_name'));

            if (empty($fullName)) {
                $this->Flash->error(__('Please enter your full name'));
                return $this->redirect(['action' => 'login']);
            }

            // Find member by concatenating first_name and last_name (case insensitive)
            $member = $this->Users->Members->find('all')
                ->where([
                    'LOWER(CONCAT(first_name, " ", last_name)) =' => strtolower($fullName)
                ])
                ->first();

            if ($member) {
                // Load the MemberRegistration component and send login link
                $this->loadComponent('MemberRegistration');
                $this->MemberRegistration->sendMyPageToMember($member);
            }
            else
            {
                $this->Mail->mailNotif(__('Wrong name entered for {0}', $fullName), '', null, 0);
            }
            $this->Flash->success(__('If the member exists, a login link has been sent to the member {0}', $fullName));
        }

        return $this->redirect(['action' => 'login']);
    }
}
