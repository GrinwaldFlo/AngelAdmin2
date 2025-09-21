<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Security;

/**
 * Member Management Component
 * 
 * Handles CRUD operations and status changes for members
 */
class MemberManagementComponent extends Component
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        // Components are available through the controller
    }

    /**
     * Create a new member
     */
    public function createMember($data)
    {
        $controller = $this->getController();
        $member = $controller->Members->newEmptyEntity();
        $controller->Members->getAssociation('Fields')->AddMissing($member);
        
        // Hash authenticated users should not be able to create new members
        if ($controller->isHashAuthenticated()) {
            $controller->Flash->error(__('You do not have permission to create new members.'));
            return 'redirect_home';
        }
        
        $controller->Authorization->authorize($member, 'edit');

        if (!empty($data)) {
            $member = $controller->Members->patchEntity($member, $data);
            $member->date_arrival = new \Cake\I18n\Date();
            $member->hash = Security::hash('Personal folder', 'sha1', $member->fullName . rand(0, 100));
            
            if (array_key_exists('field', $data)) {
                $controller->Members->getAssociation('Fields')->patchFields($member, $data['field']);
            }
            
            $checkMember = $controller->Members->find('all')
                ->where(['first_name =' => $member->first_name, 'last_name =' => $member->last_name])
                ->first();

            if ($checkMember != null) {
                $controller->Flash->error(__('The member already exists'));
                return null;
            } elseif ($controller->Members->save($member)) {
                $controller->Flash->success(__('The member has been saved.'));
                return $member;
            }

            $controller->Flash->error(__('The member could not be saved.'));
        }
        
        return $member;
    }

    /**
     * Update an existing member
     */
    public function updateMember($id, $data, $isAllowed = false)
    {
        $controller = $this->getController();
        $member = $controller->Members->get($id, contain: ['Teams', 'Fields', 'Fields.FieldTypes']);

        $controller->Members->getAssociation('Fields')->AddMissing($member);
        usort($member->fields, function ($a, $b) {
            return $a->field_type->sort <=> $b->field_type->sort;
        });
        
        // For regular users, use normal authorization
        if(!$isAllowed) {
            $controller->Authorization->authorize($member);
        }

        if (!empty($data)) {
            $member = $controller->Members->patchEntity($member, $data);
            if (array_key_exists('field', $data)) {
                $controller->Members->getAssociation('Fields')->saveFields($member, $data['field']);
            }

            if ($controller->Members->save($member)) {
                $controller->Flash->success(__('The member has been saved.'));
                return $member;
            }
            $controller->Flash->error(__('The member could not be saved.'));
        }
        
        return $member;
    }

    /**
     * Mark member as checked
     */
    public function setMemberChecked($id, $isAllowed = false)
    {
        $controller = $this->getController();
        $member = $controller->Members->get($id, contain: ['Teams']);

        if(!$isAllowed)
        {
            $controller->Authorization->authorize($member, 'edit');
        }

        $member->checked = true;

        if ($controller->Members->save($member)) {
            $controller->Flash->success(__('The member has been saved.'));
            return true;
        } else {
            $controller->Flash->error(__('The member could not be saved.'));
            return false;
        }
    }

    /**
     * Mark member as active
     */
    public function setMemberActive($id, $isAllowed = false)
    {
        $controller = $this->getController();
        $member = $controller->Members->get($id, contain: ['Teams']);

        if (!$isAllowed) {
            $controller->Authorization->authorize($member, 'edit');
        }

        $member->active = true;

        if ($controller->Members->save($member)) {
            $controller->Flash->success(__('The member has been saved.'));
            return true;
        } else {
            $controller->Flash->error(__('The member could not be saved.'));
            return false;
        }
    }

    /**
     * Cancel member registration
     */
    public function cancelMemberRegistration($id, $data = null, $isAllowed = false)
    {
        $controller = $this->getController();
        $member = $controller->Members->get($id);
        $openBills = $controller->Members->Bills->find('all')
            ->contain(['Members', 'Sites'])
            ->where(['member_id =' => $id, 'canceled =' => 0, 'paid =' => 0]);

        if (!$isAllowed) {
            $controller->Authorization->authorize($member, 'edit');
        }

        if (!empty($data)) {
            $member = $controller->Members->patchEntity($member, $data);
            $member->registered = false;
            $member->active = false;
            $member->date_fin = new \Cake\I18n\Date();
            
            if ($controller->Members->save($member)) {
                $controller->Flash->success(__('The member has been saved.'));
                return true;
            }
            $controller->Flash->error(__('The member could not be saved.'));
            return false;
        }

        return ['member' => $member, 'openBills' => $openBills];
    }

    /**
     * Delete a member
     */
    public function deleteMember($id)
    {
        $controller = $this->getController();
        $member = $controller->Members->get($id);
        
        if ($controller->Members->delete($member)) {
            $controller->Flash->success(__('The member has been deleted.'));
            return true;
        } else {
            $controller->Flash->error(__('The member could not be deleted.'));
            return false;
        }
    }

    /**
     * Set member language
     */
    public function setMemberLanguage($lng)
    {
        $controller = $this->getController();
        
        if ($lng == null || !in_array($lng, ['en', 'fr', 'de', 'es', 'it'])) {
            $controller->Flash->error(__("This language doesn't exists"));
            return false;
        }

        $controller->curMember->language = $lng;
        $controller->Members->save($controller->curMember);
        $controller->getPrefSessionStr('lng', $lng, 'fr');
        
        return true;
    }
}
