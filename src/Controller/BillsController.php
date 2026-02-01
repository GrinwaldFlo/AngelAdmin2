<?php
declare(strict_types=1);
namespace App\Controller;
use Cake\Utility\Text;
use Cake\Routing\Router;
use Cake\Mailer\Mailer;
use Cake\I18n\I18n;
use Cake\ORM\TableRegistry;
use Sprain\SwissQrBill as QrBill;
use \Exception;

/**
 * Bills Controller
 *
 * @property \App\Model\Table\BillsTable $Bills
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @method \App\Model\Entity\Bill[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BillsController extends AppController
{
    public array $paginate = [
        'limit' => 500,
        'maxLimit' => 1000,
        'order' => ['Bills.id' => 'asc'],
        'finder' => 'withMembersTeamsSites'
    ];
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Image');
        $this->loadComponent('Zip');
    }

    /**
     *
     * @param int|null $billStatus 0=All, 1=Open, 2=Late, 3=Paid, 4=Draft
     * @param int|null $memberStatus
     * @param int|null $teamId
     */
    public function index(int|null $billStatus = null, int|null $memberStatus = null, int|null $teamId = null, int|null $siteId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'viewall');

        $billStatus = $this->getPrefSession('billStatus', $billStatus, 1);
        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $siteId = $this->getPrefSession('siteId', $siteId, 1);

        $bills = $this->getBills($billStatus, $memberStatus, $teamId, $siteId);
        $bills = $this->paginate($bills);
        $teams = $this->getTeamsActiv($siteId);
        $sites = $this->Bills->Sites->find('list');

        $this->set(compact('bills', 'teamId', 'memberStatus', 'billStatus', 'teams', 'sites', 'siteId'));
    }

    public function simple(int|null $billStatus = null, int|null $memberStatus = null, int|null $teamId = null, int|null $siteId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'viewall');

        $twoYearsFromNow = date('Y-m-d', strtotime('-2 years'));

        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $siteId = $this->getPrefSession('siteId', $siteId, 1);

        $bills = $this->getBills(0, 1, $teamId, $siteId);
        $bills = $this->paginate($bills);
        $teams = $this->getTeamsActiv($siteId);
        $sites = $this->Bills->Sites->find('list');

        $result = array();

        foreach ($bills as $value) {
            // $result[$value->member->id]['Name'] = $value->member->FullName;
            // $result[$value->member->id][$value->label] = ['Status' => $value->StatusHtml, 'Amount' => $value->amount]; //['Status']

            if (!$value->paid || ($value->due_date < $twoYearsFromNow && substr($value->label, 0, 10) !== "Cotisation")) //
            {
                $result[$value->member->FullName][$value->label] = ['Status' => $value->StatusHtml, 'Amount' => $value->amount];
            }
        }
        ksort($result);
        $this->set(compact('bills', 'teamId', 'memberStatus', 'billStatus', 'teams', 'sites', 'siteId', 'result'));

    }

    /**
     * View method
     *
     * @param string|null $id Bill id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(int|null $id = null)
    {
        $bill = $this->Bills->get($id, contain: ['Members', 'Sites']);
        $this->Authorization->authorize($bill, 'view');

        $this->set('bill', $bill);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(int|null $member_id = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');
        $this->set('referer', $this->referer()); // Pour créer un lien retour
        if ($member_id == null) {
            $this->Flash->error("Nobody is assigned for this invoice");
            return $this->redirect(array("action" => 'index'));
        }
        $member = $this->Bills->Members->findById($member_id)->contain('Teams')->firstOrFail();
        $this->set('curMember', $member);

        $this->set('billTemplates', $this->Get->BillTemplates());

        $sites = $this->Bills->Sites->find('list', [
            'valueField' => function ($site) {
                return $site->city;
            }
        ]);

        $bill = $this->Bills->newEmptyEntity();
        if ($this->request->is('post')) {
            $bill = $this->Bills->patchEntity($bill, $this->request->getData());
            $bill->due_date_ori = $bill->due_date;
            $bill->tokenhash = Text::uuid();
            $bill->state_id = 0;
            if ($this->Bills->save($bill)) {
                $this->Flash->success(__('The invoice has been saved.'));

                return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', $bill->id]);
                //return $this->redirect(['controller' => 'Members', 'action' => 'view', $member->id]);
            }
            $this->Flash->error(__('The invoice could not be saved.'));
        }
        $this->set('feeLabel', $this->Bills->GetFeeLabel($this->config));
        $this->set(compact('bill', 'sites'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Bill id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(int|null $id = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');
        $bill = $this->Bills->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($bill->paid && !$this->curRole->Admin) {
                $this->Flash->error(__("The invoice is already paid and can't be edited anymore"));
            } else {
                $bill = $this->Bills->patchEntity($bill, $this->request->getData());
                $bill->printed = false;

                if ($this->Bills->save($bill)) {
                    $member = $this->Bills->Members->findById($bill->member_id)->firstOrFail();

                    $this->Flash->success(__('The invoice has been saved.'));

                    return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', $id]);
                    //        return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('The invoice could not be saved.'));
            }
        }

        $sites = $this->Bills->Sites->find('list', [
            'valueField' => function ($site) {
                return $site->city;
            }
        ]);

        $this->set(compact('bill', 'sites'));
    }

    public function testQR()
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        // Create a new instance of QrBill, containing default headers with fixed values
        $qrBill = QrBill\QrBill::create();

        // Add creditor information
// Who will receive the payment and to which bank account?
        $qrBill->setCreditor(
            QrBill\DataGroup\Element\CombinedAddress::create(
                'Robert Schneider AG',
                'Rue du Lac 1268',
                '2501 Biel',
                'CH'
            )
        );

        $qrBill->setCreditorInformation(
            QrBill\DataGroup\Element\CreditorInformation::create(
                'CH6009000000102282462' // This is a special QR-IBAN. Classic IBANs will not be valid here.
            )
        );

        // Add debtor information
// Who has to pay the invoice? This part is optional.
//
// Notice how you can use two different styles of addresses: CombinedAddress or StructuredAddress.
// They are interchangeable for creditor as well as debtor.
        $qrBill->setUltimateDebtor(
            QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
                'Pia-Maria Rutschmann-Schnyder',
                'Grosse Marktgasse',
                '28',
                '9400',
                'Rorschach',
                'CH'
            )
        );

        // Add payment amount information
// What amount is to be paid?
        $qrBill->setPaymentAmountInformation(
            QrBill\DataGroup\Element\PaymentAmountInformation::create(
                'CHF',
                2500.25
            )
        );

        $qrBill->setPaymentReference(
            QrBill\DataGroup\Element\PaymentReference::create(
                QrBill\DataGroup\Element\PaymentReference::TYPE_NON
            )
        );

        // Optionally, add some human-readable information about what the bill is for.
        $qrBill->setAdditionalInformation(
            QrBill\DataGroup\Element\AdditionalInformation::create(
                'Invoice 123456, Gardening work'
            )
        );

        // Now get the QR code image and save it as a file.
        try {
            $qrBill->getQrCode()->writeFile(WWW_ROOT . '/qr.png');
            //$qrBill->getQrCode()->writeFile(__DIR__ . '/qr.svg');
        } catch (Exception $e) {
            foreach ($qrBill->getViolations() as $violation) {
                print $violation->getMessage() . "\n";
            }
            exit;
        }
    }

    /**
     *
     * @param int|null $id bill id, or -1 will generate missing bills
     * @return \Cake\Http\Response|null
     */
    public function pdf(int|null $id = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        if ($id == null) {
            return null;
        }

        I18n::setLocale($this->config['lng']);

        $cnt = 0;
        if ($id == -1) {
            $bills = $this->Bills->find('All', contain: ['Members', 'Sites']);
            $logId = "";
            foreach ($bills as $bill) {
                // cnt to limit the execution time to less than 60 seconds
                if (!file_exists($bill->BillPath) && $cnt < 400) {
                    $this->log("PDF Generating bill " . $bill->id . ' CNT:' . $cnt, 'info');
                    $bill->member->CheckFolder();
                    $this->Set("outputType", 'F');
                    $this->Set("outputPath", $bill->BillPath);
                    $this->Set("bill", $bill);

                    $this->viewBuilder()->setLayout('pdf');
                    $this->render();
                    $cnt = $cnt + 1;
                    $this->log("PDF Genereate bill " . $bill->id . ' CNT:' . $cnt, 'info');
                    $logId = $logId . ' / ' . $bill->id;
                }
            }
            $this->Flash->success(__('Invoices have been updated') . ' ' . $logId . ' CNT:' . $cnt);
            return $this->redirect(['controller' => 'Bills', 'action' => 'index']);
        } else {
            $bill = $this->Bills->get($id, contain: ['Members', 'Sites']);

            $bill->member->CheckFolder();
            $this->Set("outputType", 'F');
            $this->Set("outputPath", $bill->BillPath);
            $this->Set("bill", $bill);
            $this->viewBuilder()->setLayout('pdf');
            $this->render();

            $this->log("PDF Genereate bill " . $id, 'info');
            return $this->redirect(['controller' => 'Members', 'action' => 'view', $bill->member->id]);
        }
    }

    public function pdfs(int|null $memberStatus = null, int|null $teamId = null, int|null $siteId = null, $markSent = false)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $siteId = $this->getPrefSession('siteId', $siteId, 1);
        $bills = $this->getBills(4, $memberStatus, $teamId, $siteId);

        if ($markSent) {
            foreach ($bills as $bill) {
                $bill->printed = 1;
                $this->Bills->save($bill);
            }
        }

        I18n::setLocale($this->config['lng']);

        $this->Set("outputType", 'D');
        $this->Set("outputPath", 'Invoices.pdf');
        $this->Set("bills", $bills);
        $this->render();
        //return $this->redirect(['controller' => 'Bills', 'action' => 'mail-print']);
    }

    public function batchValidation(int|null $siteId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');
        $siteId = $this->getPrefSession('siteId', $siteId, 1);

        if ($this->request->is('post')) {
            $result = $this->request->getData();
            foreach ($result as $billId => $value) {
                if ($value == 1) {
                    $bill = $this->Bills->get($billId, contain: ['Members', 'Sites']);
                    $bill->paid = 1;

                    $this->Bills->save($bill);
                    $bill->DeleteBillPdf();

                    $member = $this->Bills->Members->findById($bill->member_id)->firstOrFail();
                }
            }
            $this->Flash->success(__('Invoices has been validated'));
            return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
        }
        $bills = $this->Bills->find('all')
            ->where(['Bills.paid =' => 0])
            ->where(['Bills.canceled =' => 0])
            ->orderBy(['Bills.id' => 'ASC'])
            ->where(['Bills.site_id =' => $siteId])
            ->contain(['Members', 'Sites']);

        $sites = $this->Bills->Sites->find('list');
        $this->set(compact('bills', 'sites', 'siteId'));
    }

    public function generateReminder(int|null $memberStatus = null, int|null $teamId = null, int|null $siteId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');
        $siteId = $this->getPrefSession('siteId', $siteId, 1);
        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $teamId = $this->getPrefSession('teamId', $teamId, 0);

        if ($this->request->is('post')) {
            $result = $this->request->getData();
            $penalty = $result['Penalty'];

            // debug($result);
            foreach ($result['BillId'] as $billId => $value) {
                if ($value == 1) {
                    $bill = $this->Bills->get($billId, contain: ['Members', 'Sites']);

                    if ($penalty) {
                        $bill->amount += $bill->site->reminder_penalty;
                    }
                    if (empty($bill->due_date_ori)) {
                        $bill->due_date_ori = $bill->due_date;
                    }
                    $bill->reminder += 1;
                    $bill->printed = 0;
                    $this->Bills->save($bill);
                    $bill->DeleteBillPdf();
                    $this->Flash->success(__('Invoices have been updated') . ' ' . $billId . ' ' . $bill->member->FullName);
                }
            }
            return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
        }

        $bills = $this->getBills(2, $memberStatus, $teamId, $siteId);

        $teams = $this->getTeamsActiv($siteId);
        $sites = $this->Bills->Sites->find('list');
        $this->set(compact('bills', 'sites', 'siteId', 'memberStatus', 'teamId', 'teams'));
    }

    public function sendReminder(int|null $memberStatus = null, int|null $siteId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        $sitesTable = TableRegistry::getTableLocator()->get('Sites');
        $siteId = $this->getPrefSession('siteId', $siteId, 0);
        $sites = $sitesTable->find('list');
        if ($siteId > 0) {
            $site = $sitesTable->get($siteId);
        }

        $members = $this->getLateBillsByMember($memberStatus, $siteId)->all();

        if ($this->request->is('post')) {
            $result = $this->request->getData();
            $membersTable = TableRegistry::getTableLocator()->get('Members');
            $now = new \DateTime();

            foreach ($members as $member) {
                // Check if reminder_sent is null or more than 20 days ago
                $canSend = false;
                if (empty($member->reminder_sent)) {
                    $canSend = true;
                } else {
                    $lastSent = new \DateTime($member->reminder_sent->format('Y-m-d H:i:s'));
                    $interval = $now->diff($lastSent)->days;
                    if ($interval >= 20) {
                        $canSend = true;
                    }
                }

                if (!$canSend) {
                    $this->Flash->warning("Reminder not sent to {$member->first_name} {$member->last_name} ({$member->email}): last reminder sent less than 20 days ago.");
                    continue;
                }

                $lateBills = $this->Bills->getLateBillsByMemberId($member->member_id);
                $url = Router::url(['controller' => 'members', 'action' => 'myPage', $member->hash], true);
                $title = $this->config['clubName'] . ' / ' . __('Late bills Reminder');

                $mailer = new Mailer();
                $mailer->setViewVars(['url' => $url]);
                $mailer->setViewVars(['title' => $title]);
                $mailer->setViewVars(['lateBills' => $lateBills]);
                $mailer->setViewVars(['site' => $site]);

                $mailer
                    ->setEmailFormat('both')
                    ->setTo($member->email, $member->first_name . ' ' . $member->last_name)
                    ->setCc($site->sender_email, $site->sender)
                    ->setFrom($site->sender_email, $site->sender)
                    ->setSubject($title)
                    ->viewBuilder()
                    ->setTemplate('billing_reminder')
                    ->setLayout('default');

                if ($mailer->deliver()) {
                    $memberEntity = $membersTable->get($member->member_id);
                    $memberEntity->reminder_sent = $now->format('Y-m-d H:i:s');
                    $membersTable->save($memberEntity);
                }

                $this->Flash->success("Mail has been sent to " . $member->first_name . ' ' . $member->last_name . ' (' . $member->email . ')');

                // Refresh members list
                $members = $this->getLateBillsByMember($memberStatus, $siteId)->all();
            }

            $this->Flash->success(__('Remiders have been sent'));
        }

        $this->set(compact('members', 'sites', 'siteId', 'memberStatus'));
    }

    public function batchAdd(int|null $teamId = null, int|null $memberFilter = null, int|null $siteId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');
        $this->set('billTemplates', $this->Get->BillTemplates());
        $siteId = $this->getPrefSession('siteId', $siteId, 1);

        if ($this->request->is('post')) {
            if ($siteId == 0) {
                $this->Flash->error(__('A location need to be selected'));
                return;
            }

            $result = $this->request->getData();
            foreach ($result['MemberId'] as $memberId => $value) {

                if ($value == 1) {
                    $bill = $this->Bills->newEmptyEntity();
                    $bill->due_date = $result['due_date'];
                    $bill->link_membership_fee = $result['link_membership_fee'];
                    $bill->member_id = $memberId;
                    $bill->amount = $result['amount'];
                    $bill->label = $result['label'];
                    $bill->printed = false;
                    $bill->paid = false;
                    $bill->reminder = 0;
                    $bill->due_date_ori = $bill->due_date;
                    $bill->canceled = false;
                    $bill->state_id = 0;
                    $bill->tokenhash = Text::uuid();
                    $bill->confirmation = 0;
                    $bill->site_id = $siteId;

                    $this->Bills->save($bill);

                    $member = $this->Bills->Members->findById($bill->member_id)->firstOrFail();

                    $this->Flash->success(__('Invoices has been created'));
                }
            }
            return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
        }


        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $memberFilter = $this->getPrefSession('memberFilter', $memberFilter, 1);
        $members = $this->getMembers($teamId, $memberFilter);
        $teams = $this->getTeamsActiv();
        $sites = $this->Bills->Sites->find('list');

        $this->set(compact('members', 'teamId', 'memberFilter', 'teams', 'sites', 'siteId'));
    }

    public function mailSend(int|null $memberStatus = null, int|null $teamId = null, int|null $siteId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        if ($this->request->is('post')) {
            $result = $this->request->getData();

            if ($result['AcceptSend'] != 1) {
                $this->Flash->error(__('Send checkbox has not been checked'));
                return;
            }
            try {
                foreach ($result['BillId'] as $billId => $value) {
                    if ($value == 1) {
                        $bill = $this->Bills->get($billId, contain: ['Members', 'Sites']);

                        if (!$bill->BillExists) {
                            $this->Flash->error(__("The PDF file doesn't exist") . ' ' . $billId . ' ' . $bill->member->FullName);
                        } elseif (empty($bill->member->hash) || $bill->member->hash == '') {
                            $this->Flash->error(__("No token for the member") . ' ' . $billId . ' ' . $bill->member->FullName);
                        } elseif (empty($bill->member->email) || $bill->member->email == '') {
                            $this->Flash->error(__("No email for the member") . ' ' . $billId . ' ' . $bill->member->FullName);
                        } else {
                            $bill->printed = 1;

                            $url = Router::url(array('controller' => 'members', 'action' => 'myPage', $bill->member->hash), true);

                            $mailer = new Mailer();

                            if ($bill->reminder == 0) {
                                $title = $this->config['clubName'] . ' / ' . __('New Invoice');
                            } else {
                                $title = $this->config['clubName'] . ' / ' . __('Invoice Reminder');
                            }
                            $mailer->setViewVars(['url' => $url]);
                            $mailer->setViewVars(['title' => $title]);
                            $mailer->setViewVars(['bill' => $bill]);

                            $mailer
                                ->setEmailFormat('both')
                                ->setTo($bill->member->email, $bill->member->FullName)
                                ->setCc($bill->site->sender_email, $bill->site->sender)
                                ->setFrom($bill->site->sender_email, $bill->site->sender)
                                ->setSubject($title . ' - ' . $bill->Reference . ' - ' . $bill->label)
                                ->setAttachments([$bill->BillPath])
                                ->viewBuilder()
                                ->setTemplate('billing')
                                ->setLayout('default');

                            $mailer->deliver();

                            $this->Bills->save($bill);
                            $this->Flash->success(__('Invoices have been sent') . ' ' . $billId . ' ' . $bill->member->FullName);
                        }
                    }
                }
            } catch (Exception $exception) {
                $this->Flash->error(__('There were some error while sending mail. Try again'));
            }
            return $this->redirect(['controller' => 'Bills', 'action' => 'index']);
        }

        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $siteId = $this->getPrefSession('siteId', $siteId, 1);

        $bills = $this->getBills(4, $memberStatus, $teamId, $siteId);
        $bills = $this->paginate($bills);
        $teams = $this->getTeamsActiv($siteId);
        $sites = $this->Bills->Sites->find('list');

        $this->set(compact('bills', 'teamId', 'memberStatus', 'teams', 'sites', 'siteId'));
    }

    public function mailPrint(int|null $memberStatus = null, int|null $teamId = null, int|null $siteId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        if ($this->request->is('post')) {
            $result = $this->request->getData();

            /* $bills = array();

              foreach ($result['BillId'] as $billId => $value)
              {
              //if ($value == 1)
              {
              $bill = $this->Bills->get($billId, ['contain' => ['Members', 'Sites']]);

              array_push($bills, $bill);
              }
              } */
            return $this->redirect(['controller' => 'Bills', 'action' => 'pdfs', $result['memberStatus'], $result['teamId'], $result['siteId'], $result['MarkSent']]);
        }

        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $siteId = $this->getPrefSession('siteId', $siteId, 1);

        $bills = $this->getBills(4, $memberStatus, $teamId, $siteId);
        $bills = $this->paginate($bills);
        $teams = $this->getTeamsActiv($siteId);
        $sites = $this->Bills->Sites->find('list');

        $this->set(compact('bills', 'teamId', 'memberStatus', 'teams', 'sites', 'siteId'));
    }

    public function overview(int|null $memberStatus = null, int|null $teamId = null, int $year = 0, int $billTemplateId = 0, int $siteId = 1)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'viewall');

        $billTemplates = $this->Get->BillTemplates();
        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $teamId = $this->getPrefSession('teamId', $teamId, 0);
        $siteId = $this->getPrefSession('siteId', $siteId, 1);

        $startDate = $this->config['dateSeasonStart'];
        $startDate->sub(new \DateInterval('P' . $year . 'Y'));

        $bills = $this->getBills(0, $memberStatus, $teamId, $siteId)
            ->where(['Bills.created >' => $startDate, 'link_membership_fee =' => 0])
            ->contain('Members')->order('Members.first_name');
        if ($billTemplateId > 0) {
            $billFilter = $this->Get->BillTemplates($billTemplateId);
            $bills->where(['Bills.label LIKE' => $billFilter->label . '%']);
        }

        $teams = $this->getTeamsActiv($siteId);
        $sites = $this->Bills->Sites->find('list');
        $members = $this->Bills->Members->find('Members', [
            'teamId' => $teamId,
            'memberFilter' => 1
        ])->contain('Teams')->order(['first_name' => 'ASC', 'last_name' => 'ASC']);
        //$billTemplates = $this->Get->BillTemplates();

        $result = array();
        $colName = array();
        $colIndex = 0;

        $wide = 'Wide';
        $this->set(compact('wide', 'teamId', 'memberStatus', 'teams', 'billTemplates', 'billTemplateId', 'year', 'sites', 'siteId'));

        if ($this->request->is(['post'])) {
            $newBillData = $this->request->getData();
            debug($newBillData);

            $dateBill = new \DateTime();
            $dateBill->add(new \DateInterval('P30D'));

            $bill = $this->Bills->newEmptyEntity();
            $bill->printed = 0;
            $bill->paid = 0;
            $bill->reminder = 0;
            $bill->canceled = false;
            $bill->due_date = new \Cake\I18n\Date($dateBill);
            $bill->state_id = 0;
            $bill->tokenhash = Text::uuid();
            $bill->due_date_ori = new \Cake\I18n\Date($dateBill);
            $bill->link_membership_fee = 0;
            $bill->member_id = $newBillData['memberId'];
            $bill->amount = $newBillData['amount'];
            $bill->label = $newBillData['name'];
            $this->Bills->save($bill);

            $member = $this->Bills->Members->findById($bill->member_id)->firstOrFail();

            $this->Flash->success(__('Invoices has been created'));

            return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', $bill->id]);
            //return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
            //return $this->redirect(['controller' => 'Bills', 'action' => 'overview', $memberStatus, $teamId, $year, $billTemplateId, $siteId]);
            /*    */
        }

        foreach ($members as $value) {
            $result[$value->id]['Name'] = $value->FullName;

            /* foreach($billTemplates as $bt)
              {
              $result[$value->id][$bt->label] = ['Amount' => $bt->amount];
              } */
        }

        foreach ($bills as $value) {
            $result[$value->member->id]['Name'] = $value->member->FullName;

            if (empty($colName[$value->label])) {
                $colName[$value->label] = $colIndex;
                $colIndex++;
            }
            //      if(empty($result[$value->member->id]['Name'][$colName[$value->label]]))
            //     {
            $result[$value->member->id][$value->label] = ['Status' => $value->StatusHtml, 'Amount' => $value->amount]; //['Status']
            //     }
        }

        foreach ($colName as $InvoiceName => $InvoiceValue) {
            $curPrice = -1;

            foreach ($result as $memberId => $InvoiceInfo) {
                if (!empty($InvoiceInfo[$InvoiceName])) {
                    if ($curPrice == -1) {
                        $curPrice = $InvoiceInfo[$InvoiceName]['Amount'];
                    } else {
                        if ($curPrice != $InvoiceInfo[$InvoiceName]['Amount']) {
                            continue;
                        }
                    }
                }
            }
            $colName[$InvoiceName] = $curPrice;
        }

        ksort($colName);

        $this->set(compact('result', 'colName'));
    }

    public function clearDraft()
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'admin');

        $bills = $this->Bills->find('all')->contain(['Members'])->where(['printed' => 0]);

        foreach ($bills as $bill) {
            //$bill->DeleteBillPdf();
        }
    }

}
