<?php
declare(strict_types=1);
namespace App\Controller;
use App\Controller\AppController;
use App\Model\Entity\Content;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Http\Exception\NotFoundException;

/**
 * @property \App\Controller\Component\SubventionComponent $Subvention
 */
class AdminController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Subvention');

        $this->Authorization->skipAuthorization();
    }

    /**
     * Admin pages are only for the admin role
     * (not logged users are redirected to the login by the authentication)
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if ($this->curRole !== null && !$this->curRole->Admin) {
            return $this->redirect('/');
        }
    }

    public function dashboard()
    {
        $this->set("wide", '100');
        $this->loadComponent('Dashboard');

        $siteId = $this->getSiteId();
        $seasonStart = $this->config['dateSeasonStart'];

        $members = $this->Dashboard->members($siteId, $seasonStart, (int)$this->config['year']);
        $finances = $this->Dashboard->finances($siteId, $seasonStart);
        $attendance = $this->Dashboard->attendance($siteId, $seasonStart);
        $teams = $this->Dashboard->teams($siteId);

        $this->set(compact('members', 'finances', 'attendance', 'teams', 'siteId', 'seasonStart'));
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
        // Bills need a real site: the one chosen in the menu, or the only one when the menu is hidden
        $pref = new PrefResult($this, $teamId, null, count(PrefResult::getSites()) == 1);
        $site = $pref->site;

        if ($this->request->is('post')) {
            if ($site === null) {
                $this->Flash->error(__('Select a site to create membership fees'));
                return $this->redirect(['action' => 'generateMembership']);
            }
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
                    $fee = $member->MembershipFee($site->feeMax);

                    if ($member->multi_payment > 1) {
                        for ($i = 1; $i <= $member->multi_payment; $i++) {
                            $billsReg->CreateMembershipFee($this->config, $member, " " . $i . "/" . $member->multi_payment, $date[$i], $fee / ($member->multi_payment * 1.0), $site->id);
                        }
                    } else {
                        $billsReg->CreateMembershipFee($this->config, $member, "", $date[1], $fee, $site->id);
                    }

                    $this->Flash->success(__('Membership fee created for ') . ' ' . $member->FullName);
                }
            }
            return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
        }

        $members = [];
        if ($site === null) {
            $this->Flash->error(__('Select a site to create membership fees'));
        } else {
            $members = $this->getMembers($pref->teamId, 1, $site->id);
        }
        $teamId = $pref->teamId;
        $teams = $pref->teams;

        $this->set(compact('members', 'teamId', 'teams', 'site'));
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

    public function subventions(int $ageFilter = 0, int $grid = 0)
    {
        $this->subventionsCommon($ageFilter, $grid, false);
    }

    public function subventionsPdf(int $ageFilter = 0)
    {
        $this->subventionsCommon($ageFilter, 0, true);
    }

    private function subventionsCommon(int $ageFilter = 0, int $grid = 0, bool $pdf = false)
    {
        $pref = new PrefResult($this, 0, 1, true);
        $siteId = $pref->siteId;

        $contents = $this->Subvention->getContents();
        $subventionsData = $this->Subvention->getList($pref, $ageFilter);

        if ($pdf) {
            $this->Set("outputType", 'D');
            $this->Set("outputPath", 'Subventions.pdf');
            $site = $pref->site;
            $this->set(compact('subventionsData', 'siteId', 'ageFilter', 'site', 'contents'));
        } else {
            $this->set(array_merge($subventionsData, compact('pref', 'ageFilter', 'grid', 'contents')));
        }
    }

    /**
     * Email address of the municipality of each city of the subsidy requests
     */
    public function subventionEmails(int $ageFilter = 0)
    {
        $pref = new PrefResult($this, 0, 1, true);
        $subventionsData = $this->Subvention->getList($pref, $ageFilter);
        $counts = array_map('count', $subventionsData['membersCity']);
        $subventionCities = TableRegistry::getTableLocator()->get('SubventionCities');
        $emails = $subventionCities->emailsByCity($subventionsData['membersCityList']);

        if ($this->request->is(['post', 'put'])) {
            foreach ((array)$this->request->getData('cities') as $row) {
                $emails[(string)($row['city'] ?? '')] = (string)($row['email'] ?? '');
            }
            unset($emails['']);
            $invalid = $subventionCities->saveEmails($emails);
            if (empty($invalid)) {
                $this->Flash->success(__('The emails have been saved.'));
                return $this->redirect(['action' => 'subventions', $ageFilter]);
            }
            $this->Flash->error(__('Invalid email address: {0}', implode(', ', $invalid)));
        }

        $hasBody = !empty($this->Subvention->getContents(Content::EMAIL_SUBVENTIONS));
        $this->set(compact('emails', 'counts', 'ageFilter', 'hasBody'));
    }

    /**
     * Zip of ready to send emails (.eml), one per city with its letter, to import in an email client
     */
    public function subventionsEmailPack(int $ageFilter = 0)
    {
        $pref = new PrefResult($this, 0, 1, true);
        $subventionsData = $this->Subvention->getList($pref, $ageFilter);
        $emails = TableRegistry::getTableLocator()->get('SubventionCities')
            ->emailsByCity($subventionsData['membersCityList']);
        $body = implode("\n\n", $this->Subvention->getContents(Content::EMAIL_SUBVENTIONS));

        $zipPath = $this->Subvention->createEmailPack(
            $pref->site,
            $subventionsData,
            $this->Subvention->getContents(),
            $body,
            $emails
        );
        $zip = file_get_contents($zipPath);
        unlink($zipPath);

        return $this->response
            ->withStringBody($zip)
            ->withType('zip')
            ->withDownload(__('Subsidies') . '.zip');
    }
}
