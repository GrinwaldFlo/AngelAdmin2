<?php
declare(strict_types=1);
namespace App\Controller;
use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Http\Exception\NotFoundException;

class AdminController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->Authorization->skipAuthorization();
        if (!$this->curRole->Admin) {
            $this->redirect('/');
        }
    }

    public function dashboard()
    {
        $this->set("wide", '100');

        $teamsReg = TableRegistry::getTableLocator()->get('Teams');
        $billsReg = TableRegistry::getTableLocator()->get('Bills');
        $membersReg = TableRegistry::getTableLocator()->get('Members');

        $teams = $teamsReg->find('all')->where(['active =' => 1])->orderBy(['name' => 'ASC']);

        $finances = array();
        array_push($finances, [__('Open invoices'), $billsReg->GetNbOpenInvoice() . '<br>' . $billsReg->GetSumOpenInvoice() . '&nbspCHF']);
        array_push($finances, [__('Membership fee (paid/invoiced)'), $billsReg->GetSumPaidFees($this->config['dateSeasonStart']) . '&nbspCHF<br>' . $billsReg->GetSumFeesFrom($this->config['dateSeasonStart']) . '&nbspCHF']);
        array_push($finances, [__('Invoiced this year (paid/total)'), $billsReg->GetSumInvoicedPaidFrom($this->config['dateSeasonStart']) . '&nbspCHF<br>' . $billsReg->GetSumInvoicedFrom($this->config['dateSeasonStart']) . '&nbspCHF']);

        $members = array();
        array_push($members, [__('No. of members'), $membersReg->CountActiveMembers()]);
        array_push($members, [__('Not registered'), $membersReg->CountNotRegistered()]);
        array_push($members, [__('Children / Adults'), $membersReg->CountActiveChilds() . ' / ' . $membersReg->CountActiveAdults()]);
        array_push($members, [__('Membership fee paid'), $membersReg->CountActiveFeesPaid()]);

        $configuration = array();
        array_push($configuration, [__('First day of the season'), $this->config['dateSeasonStart']->format('d M Y')]);
        array_push($configuration, [__('Club name'), $this->config['clubName']]);

        $this->set(compact(['teams', 'finances', 'members', 'configuration']));
    }

    public function newYear()
    {

    }

    public function resetMembership(int $validation = 0)
    {
        $membersReg = TableRegistry::getTableLocator()->get('Members');
        $members = $membersReg->find('all')->where(['membership_fee_paid >' => 0]);

        if ($validation == 0) {

        } elseif ($validation == $this->Session->read("resetValidation")) {
            $membersReg->updateAll(['membership_fee_paid' => 0], ['membership_fee_paid >' => 0]);

            $this->Flash->success(__x('Some values were put back to default settings', 'Memberships have been reset'));
        } else {
            $this->Flash->error(__('Wrong validation code'));
        }

        $resetValidation = rand();
        $this->Session->write('resetValidation', $resetValidation);

        $this->set(compact('resetValidation', 'members'));
    }

    public function resetRegistration(int $validation = 0)
    {
        $membersReg = TableRegistry::getTableLocator()->get('Members');
        $members = $membersReg->find('all')->where(['registered >' => 0]);

        if ($validation == 0) {

        } elseif ($validation == $this->Session->read("resetValidation")) {
            $membersReg->updateAll(['registered' => 0], ['registered >' => 0]);

            $this->Flash->success(__('Registrations have been reset'));
        } else {
            $this->Flash->error(__('Wrong validation code'));
        }

        $resetValidation = rand();
        $this->Session->write('resetValidation', $resetValidation);

        $this->set(compact('resetValidation', 'members'));
    }

    public function generateMembership(int|null $teamId = null)
    {
        $membersReg = TableRegistry::getTableLocator()->get('Members');
        $billsReg = TableRegistry::getTableLocator()->get('Bills');
        if ($this->request->is('post')) {
            $result = $this->request->getData();
            $date = array(5);
            $date[1] = new \DateTime();
            $date[1]->add(new \DateInterval('P30D'));
            $date[2] = new \DateTime();
            $date[2]->add(new \DateInterval('P60D'));
            $date[3] = new \DateTime();
            $date[3]->add(new \DateInterval('P90D'));
            $date[4] = new \DateTime();
            $date[4]->add(new \DateInterval('P120D'));

            foreach ($result['MemberId'] as $memberId => $value) {
                if ($value == 1) {
                    $member = $membersReg->get($memberId, contain: ['Users', 'Teams']);

                    if ($member->multi_payment > 1) {
                        for ($i = 1; $i <= $member->multi_payment; $i++) {
                            $billsReg->CreateMembershipFee($this->config, $member, " " . $i . "/" . $member->multi_payment, $date[$i], $member->MembershipFee($this->config['feeMax']) / ($member->multi_payment * 1.0));
                        }
                    } else {
                        $billsReg->CreateMembershipFee($this->config, $member, "", $date[1], $member->MembershipFee($this->config['feeMax']));
                    }

                    $this->Flash->success(__('Membership fee created for ') . ' ' . $member->FullName);
                }
            }
            return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
        }




        $teamId = $this->getPrefSession('teamId', $teamId, 0);

        $members = $membersReg->find(
            'Members',
            teamId: $teamId,
            memberFilter: 1
        )->contain('Teams')->orderBy(['first_name' => 'ASC', 'last_name' => 'ASC']);

        $teams = $this->getTeamsActiv();

        $this->set(compact('members', 'teamId', 'teams'));
    }

    public function viewLog(string $type = 'debug')
    {
        if ($this->request->is('post')) {
            $logFiles = [
                LOGS . 'debug.log',
                LOGS . 'error.log',
                LOGS . 'security.log'
            ];
            foreach ($logFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            $this->Flash->success(__('Both log files have been deleted.'));
            return $this->redirect(['action' => 'viewLog', $type]);
        }

        $logFile = match ($type) {
            'debug' => LOGS . 'debug.log',
            'error' => LOGS . 'error.log',
            'security' => LOGS . 'security.log',
            default => throw new NotFoundException('Invalid log type'),
        };

        $lines = file_exists($logFile)
            ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            : ["Log file not found"];

        $this->set(compact('lines', 'type'));
    }
}
