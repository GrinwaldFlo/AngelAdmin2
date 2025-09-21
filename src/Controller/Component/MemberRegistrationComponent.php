<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Security;
use Cake\Routing\Router;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

/**
 * Member Registration Component
 *
 * Handles member registration and agreement processes
 */
class MemberRegistrationComponent extends Component
{
    /**
     * Handle member registration
     */
    public function registerMember($data, $config)
    {
        $controller = $this->getController();
        $member = $controller->Members->newEmptyEntity();
        $controller->Members->Fields->AddMissing($member);

        $member->communication_method_id = 1;

        if (empty($data)) {
            return $member;
        }

        $member = $controller->Members->patchEntity($member, $data);
        $member->date_arrival = new \Cake\I18n\Date();
        $member->hash = Security::hash('Personal folder', 'sha1', $member->fullName . rand(0, 100));
        $member->leaving_comment = '';
        $member->active = true;
        $member->checked = true;

        $checkMember = $controller->Members->find('all')
            ->where(['first_name =' => $member->first_name, 'last_name =' => $member->last_name])
            ->first();

        if (array_key_exists('field', $data)) {
            $controller->Members->getAssociation('Fields')->patchFields($member, $data['field']);
        }

        if ($checkMember != null) {
            if ($checkMember->email == $member->email) {
                $controller->Flash->error(__('You are already registered, we sent you a mail to get your login'));
                $this->sendMyPageToMember($checkMember);
                return 'redirect_index';
            } else {
                return 'contact_info_needed';
            }
        } elseif ($controller->Members->save($member)) {
            $controller->Flash->success(__('You have been registered. Check your mailbox for next step'));
            $this->sendMyPageToMember($member);
            $controller->Mail->mailNotif("New member", "", $member, 1);
            return 'redirect_home';
        }

        $controller->Flash->error(__('We were not able to save your registration. Please check all fields or contact us'));
        return $member;
    }

    /**
     * Send personal page email
     */
    /*private function sendPersonalPageEmail($member, $config)
    {
        $url = Router::url(array('controller' => 'members', 'action' => 'myPage', $member->hash), true);
        $mailer = new Mailer();
        $title = __x('{0} is the club name', 'Your personal page at {0}', $config['clubName']);

        $mailer->setViewVars(['url' => $url]);
        $mailer->setViewVars(['title' => $title]);
        $mailer->setViewVars(['name' => $member->FullName]);

        $mailer
            ->setEmailFormat('both')
            ->setTo($member->email, $member->FullName)
            ->setFrom($config['email'], $config['emailName'])
            ->setSubject($title)
            ->viewBuilder()
            ->setTemplate('personalPage')
            ->setLayout('default');

        $mailer->deliver();
    }*/

    /**
     * Send welcome email
     */
    private function sendWelcomeEmail($member, $config)
    {
        $url = Router::url(array('controller' => 'users', 'action' => 'register', $member->hash), true);
        $mailer = new Mailer();
        $title = __x('{0} is the name of the club you join', 'Welcome to {0}', $config['clubName']);

        $mailer->setViewVars(['url' => $url]);
        $mailer->setViewVars(['title' => $title]);
        $mailer->setViewVars(['name' => $member->fullName]);

        $mailer
            ->setEmailFormat('both')
            ->setTo($member->email, $member->fullName)
            ->setFrom($config['email'], $config['emailName'])
            ->setSubject($title)
            ->viewBuilder()
            ->setTemplate('welcome')
            ->setLayout('default');

        $mailer->deliver();
    }

    /**
     * Handle agreement process
     */
    public function processAgreement($member, $data, $year)
    {
        $controller = $this->getController();

        if (empty($data['signatureMember']) || (!$member->IsAdult && empty($data['signatureParent']))) {
            $controller->Flash->error(__('Please sign the document'));
            return false;
        }

        $regId = $controller->saveSignature($member->id, $data['signatureMember'], $data['signatureParent'], $year);

        if ($regId > 0) {
            $controller->Flash->success(__('You are now registered, it will be validated by a coach'));
            $controller->Mail->mailNotif(__('New member registered'), __('The member has just registred. You need to validate the signature.'), $member);
            return $regId;
        }

        $controller->Flash->error(__('The member could not be saved.'));
        return false;
    }

    /**
     * Validate signatures
     */
    public function validateSignatures($data, $config)
    {
        $controller = $this->getController();
        $registrations = TableRegistry::getTableLocator()->get('Registrations');

        $curMemberId = $data['member_id'];
        $delete = isset($data['Delete']);

        if (!empty($curMemberId)) {
            $curReg = $registrations->find('all')
                ->where(['year =' => $config['year'], 'member_id =' => $curMemberId])
                ->first();

            $curMember = $controller->Members->findById($curMemberId)->first();

            if (!$delete) {
                $curReg->validation_id = $controller->curMember->id;
                $registrations->save($curReg);

                $curMember->registered = true;
                $controller->Members->save($curMember);
                $controller->Mail->mailToMember(__('Signature approved'), __('Your signature has been approved'), $curMember);
            } else {
                $registrations->delete($curReg);
                $controller->Mail->mailToMember(__('Signature not approved'), __('Your signature has not been approved, please do it again'), $curMember);
            }
        }

        return $registrations->find('all')
            ->where(['year =' => $config['year'], 'validation_id =' => 0])
            ->contain('Members');
    }

    /**
     * Send invitations to members
     */
    public function sendInvitations($memberIds, $config)
    {
        $controller = $this->getController();
        $sentCount = 0;

        foreach ($memberIds as $memberId => $value) {
            if ($value == 1) {
                $member = $controller->Members->get($memberId, contain: ['Users']);
                $this->sendMyPageToMember($member);
                $controller->Flash->success(__('Invitation has been sent to ') . ' ' . $member->fullName);
                $sentCount++;
            }
        }

        return $sentCount;
    }

    public function sendMyPageToMember($member)
    {
        $url = Router::url(array('controller' => 'Members', 'action' => 'my-page', $member->hash), true);
        $url = '<a href="' . $url . '">' . __("Your personal page") . '</a>';

        if($member->registered)
        {
            $title = __("Your personal page");
        }
        elseif($member->active)
        {
            $title = __("Action needed, registrer for the year (or leave the club)");
        }
        else
        {
            $title = __("Want to come back ?");
        }

        $content = __("Click here to visit: {0}\nOn your personal page, you can:\n- View and manage your bills\n- Update your personal information\n- Register for the upcoming year\n- Cancel your membership if you wish\nWe look forward to seeing you soon!", $url);

        $controller = $this->getController();
        //$content = __("A new cheerleading season is starting and we need to know if you want to continue with our team.\n\nPlease let us know your decision by clicking on this link: {1}\n\nYou have two options:\n- Continue for another exciting season with us\n- Stop your participation in the team\n\nImportant: You must make a choice, otherwise we will contact you again soon.\n\nThank you for taking the time to respond!\n\nThe cheerleading team", $member->first_name, $url);
        $controller->Mail->mailToMember($title, $content, $member);
    }


    /**
     * Log security events for failed access attempts
     */
    public function logSecurityEvent(string $message, ?string $hash, string $details, $request): void
    {
        // Get client IP address
        $clientIp = $request->clientIp() ?: 'unknown';

        // Get user agent
        $userAgent = $request->getHeaderLine('User-Agent') ?: 'unknown';

        // Prepare log data
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $clientIp,
            'hash' => $hash,
            'user_agent' => $userAgent,
            'details' => $details,
        ];

        // Log to CakePHP logger using warning level with security scope
        Log::write('warning', $message . ' - ' . json_encode($logData), ['scope' => ['security']]);
    }
}
