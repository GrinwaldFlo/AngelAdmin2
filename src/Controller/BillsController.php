<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Text;
use Cake\Routing\Router;
use Cake\Mailer\Mailer;
use Cake\I18n\I18n;
use Cake\ORM\TableRegistry;
use Exception;

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
    /**
     * Fields of the invoice form: not the member nor the site themselves (associations)
     */
    private const FORM_FIELDS = [
        'label', 'amount', 'printed', 'paid', 'reminder', 'due_date', 'due_date_ori', 'canceled', 'state_id',
        'confirmation', 'link_membership_fee', 'site_id',
    ];

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
     * Site and team preferences of the bills pages (one site is always needed), teamId and teams are set to the view
     */
    private function getPref(int|null $teamId = null, int|null $memberFilter = null): PrefResult
    {
        $pref = new PrefResult($this, $teamId, $memberFilter, true);
        $this->set(['teamId' => $pref->teamId, 'teams' => $pref->teams, 'siteId' => $pref->siteId]);
        return $pref;
    }

    /**
     * Site sent by the form, so the page site is used even if the menu site changed meanwhile
     * @return int 0 if missing or unknown
     */
    private function getPostedSiteId(): int
    {
        return $this->allowedSiteId((int)$this->request->getData('siteId'));
    }

    /**
     *
     * @param int|null $billStatus 0=All, 1=Open, 2=Late, 3=Paid, 4=Draft
     * @param int|null $memberStatus
     * @param int|null $teamId
     */
    public function index(int|null $billStatus = null, int|null $memberStatus = null, int|null $teamId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'viewall');

        $billStatus = $this->getPrefSession('billStatus', $billStatus, 1);
        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $pref = $this->getPref($teamId);

        $bills = $this->getBills($billStatus, $memberStatus, $pref->teamId, $pref->siteId);
        $bills = $this->paginate($bills);

        $this->set(compact('bills', 'memberStatus', 'billStatus'));
    }

    public function simple(int|null $billStatus = null, int|null $memberStatus = null, int|null $teamId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'viewall');

        $twoYearsFromNow = date('Y-m-d', strtotime('-2 years'));

        $pref = $this->getPref($teamId);

        $bills = $this->getBills(0, 1, $pref->teamId, $pref->siteId);
        $bills = $this->paginate($bills);

        $result = array();

        foreach ($bills as $value) {
            // $result[$value->member->id]['Name'] = $value->member->FullName;
            // $result[$value->member->id][$value->label] = ['Status' => $value->StatusHtml, 'Amount' => $value->amount]; //['Status']

            if (!$value->paid || ($value->due_date < $twoYearsFromNow && substr($value->label, 0, 10) !== "Cotisation")) { //
                $result[$value->member->FullName][$value->label] = ['Status' => $value->StatusHtml, 'Amount' => $value->amount];
            }
        }
        ksort($result);
        $this->set(compact('bills', 'memberStatus', 'billStatus', 'result'));
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

        $sites = $this->Bills->Sites->find('list', valueField: function ($site) {
            return $site->city;
        });

        $bill = $this->Bills->newEmptyEntity();
        // Default site of the form: the city of the member
        $bill->site_id = (int)$member->site_id;
        if ($this->request->is('post')) {
            $bill = $this->Bills->patchEntity($bill, $this->request->getData(), ['fields' => self::FORM_FIELDS]);
            // The member of the page, whatever the form sends
            $bill->member_id = $member->id;
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
        // Maximum membership fee of the site of the form, as for the generated fees
        $this->set('feeMax', PrefResult::getSite((int)$bill->site_id)?->feeMax);
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
                $bill = $this->Bills->patchEntity($bill, $this->request->getData(), ['fields' => self::FORM_FIELDS]);
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

        $sites = $this->Bills->Sites->find('list', valueField: function ($site) {
            return $site->city;
        });

        $this->set(compact('bill', 'sites'));
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

    /**
     * @param int|null $siteId site shown by mail_print, not the menu one which may have changed meanwhile
     */
    public function pdfs(int|null $memberStatus = null, int|null $teamId = null, int|null $siteId = null, $markSent = false)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        $siteId = $this->allowedSiteId($siteId);
        if ($siteId == 0) {
            $this->Flash->error(__('A location needs to be selected'));
            return $this->redirect(['action' => 'mailPrint']);
        }
        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $teamId = PrefResult::validateTeamId($this->getPrefSession('teamId', $teamId, 0), $this->getTeamsActiv($siteId));
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

    public function batchValidation()
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');
        $siteId = $this->getSiteId(true);

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
        $this->set(compact('bills'));
    }

    public function generateReminder(int|null $memberStatus = null, int|null $teamId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');
        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $pref = $this->getPref($teamId);

        if ($this->request->is('post')) {
            $result = $this->request->getData();
            $penalty = !empty($result['Penalty']);

            // debug($result);
            foreach ((array)($result['BillId'] ?? []) as $billId => $value) {
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

        $bills = $this->getBills(2, $memberStatus, $pref->teamId, $pref->siteId);

        $this->set(compact('bills', 'memberStatus'));
    }

    public function sendReminder(int|null $memberStatus = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        if ($this->request->is('post')) {
            $siteId = $this->getPostedSiteId();
            if ($siteId == 0) {
                $this->Flash->error(__('A location needs to be selected'));
                return $this->redirect(['action' => 'sendReminder']);
            }
        } else {
            $siteId = $this->getSiteId(true);
        }
        $site = PrefResult::getSite($siteId);

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

        $this->set(compact('members', 'memberStatus', 'siteId'));
    }

    public function batchAdd(int|null $teamId = null, int|null $memberFilter = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');
        $this->set('billTemplates', $this->Get->BillTemplates());

        if ($this->request->is('post')) {
            $siteId = $this->getPostedSiteId();
            if ($siteId == 0) {
                $this->Flash->error(__('A location needs to be selected'));
                return $this->redirect(['action' => 'batchAdd']);
            }

            $result = $this->request->getData();
            $memberIds = array_keys(array_filter((array)($result['MemberId'] ?? []), fn($value) => $value == 1));
            // The bill belongs to the city of the member: only the members of the posted city
            $members = $memberIds === [] ? [] : $this->Bills->Members->find()
                ->select(['id', 'first_name', 'last_name', 'site_id'])
                ->where(['Members.id IN' => $memberIds])
                ->all()->indexBy('id')->toArray();
            $site = PrefResult::getSite($siteId);
            foreach ($memberIds as $memberId) {
                $member = $members[$memberId] ?? null;
                if ($member === null || (int)$member->site_id !== $siteId) {
                    if ($member !== null) {
                        $this->Flash->error(__('{0} is not in {1}, no invoice created.', $member->FullName, $site->city));
                    }
                    continue;
                }
                // Through the validation: a wrong amount or date is reported, not saved
                $bill = $this->Bills->newEntity([
                    'due_date' => $result['due_date'] ?? null,
                    'due_date_ori' => $result['due_date'] ?? null,
                    'link_membership_fee' => $result['link_membership_fee'] ?? 0,
                    'member_id' => $memberId,
                    'amount' => $result['amount'] ?? null,
                    'label' => $result['label'] ?? '',
                    'printed' => false,
                    'paid' => false,
                    'reminder' => 0,
                    'canceled' => false,
                    'state_id' => 0,
                    'tokenhash' => Text::uuid(),
                    'confirmation' => null,
                    'site_id' => $siteId,
                ]);

                if ($this->Bills->save($bill)) {
                    $this->Flash->success(__('Invoices has been created'));
                } else {
                    $this->Flash->error(__('The invoice could not be saved.'));
                }
            }
            return $this->redirect(['controller' => 'Bills', 'action' => 'pdf', -1]);
        }


        $pref = $this->getPref($teamId, $memberFilter);
        $memberFilter = $pref->memberFilter;
        $members = $this->getMembers($pref->teamId, $memberFilter, $pref->siteId);

        $this->set(compact('members', 'memberFilter'));
    }

    public function mailSend(int|null $memberStatus = null, int|null $teamId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        if ($this->request->is('post')) {
            $result = $this->request->getData();

            if (($result['AcceptSend'] ?? 0) != 1) {
                $this->Flash->error(__('Send checkbox has not been checked'));
                return $this->redirect(['action' => 'mailSend']);
            }
            try {
                foreach ((array)($result['BillId'] ?? []) as $billId => $value) {
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
        $pref = $this->getPref($teamId);

        $bills = $this->getBills(4, $memberStatus, $pref->teamId, $pref->siteId);
        $bills = $this->paginate($bills);

        $this->set(compact('bills', 'memberStatus'));
    }

    public function mailPrint(int|null $memberStatus = null, int|null $teamId = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'edit');

        if ($this->request->is('post')) {
            $result = $this->request->getData();

            /* $bills = array();

              foreach ($result['BillId'] as $billId => $value)
              {
              //if ($value == 1)
              {
              $bill = $this->Bills->get($billId, contain: ['Members', 'Sites']);

              array_push($bills, $bill);
              }
              } */
            return $this->redirect(['controller' => 'Bills', 'action' => 'pdfs', $result['memberStatus'], $result['teamId'], $result['siteId'], $result['MarkSent']]);
        }

        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $pref = $this->getPref($teamId);

        $bills = $this->getBills(4, $memberStatus, $pref->teamId, $pref->siteId);
        $bills = $this->paginate($bills);

        $this->set(compact('bills', 'memberStatus'));
    }

    /**
     * Seasons shown by the search page: number of seasons back from the current one, 0 = all
     */
    public const SEARCH_SEASONS = [1, 2, 3, 0];

    /**
     * Invoices list: one row per bill, filtered by member status, seasons, team and a text in the label (?label=)
     *
     * @param int|null $teamId 0 = all, null = last used
     * @param int $seasons Current season and the previous ones (1 = this season), 0 = all
     * @param int|null $memberStatus 0 = all, 1 = active, 2 = alumni, null = last used
     */
    public function search(int|null $teamId = null, int $seasons = 1, int|null $memberStatus = null)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'viewall');

        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $pref = $this->getPref($teamId);
        if (!in_array($seasons, self::SEARCH_SEASONS, true)) {
            $seasons = 1;
        }
        $label = trim((string)$this->request->getQuery('label', ''));

        $bills = $this->getBills(0, $memberStatus, $pref->teamId, $pref->siteId);
        if ($seasons > 0) {
            // From today: the configured year can be ahead (season being prepared)
            $startDate = self::seasonStart((string)$this->config['firstDaySeason'])->sub(new \DateInterval('P' . ($seasons - 1) . 'Y'));
            $bills->where(['Bills.created >=' => $startDate]);
        }
        // Each word anywhere in the label, in any order (case insensitive: the column collation and SQLite LIKE)
        // '!' escapes the LIKE wildcards typed by the user (same ESCAPE syntax in MySQL and SQLite)
        foreach (preg_split('/\s+/', $label, -1, PREG_SPLIT_NO_EMPTY) as $i => $word) {
            $bills->where("Bills.label LIKE :labelWord$i ESCAPE '!'")
                ->bind(":labelWord$i", '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word) . '%', 'string');
        }
        $bills->orderBy(['Bills.created' => 'DESC', 'Bills.id' => 'DESC']);
        $members = $this->getMembers($pref->teamId, $memberStatus, $pref->siteId);

        $rows = $this->searchRows($members, $bills);
        $this->set(compact('rows', 'memberStatus', 'seasons', 'label'));
    }

    /**
     * One row per bill, and one row without bill for the members who have none (so we see who did not get it)
     *
     * @param iterable<\App\Model\Entity\Member> $members Members of the filters
     * @param iterable<\App\Model\Entity\Bill> $bills Bills of the filters, with their member
     * @return array<array{member: \App\Model\Entity\Member, bill: \App\Model\Entity\Bill|null}> sorted by member name
     */
    private function searchRows(iterable $members, iterable $bills): array
    {
        $byMember = [];
        foreach ($members as $member) {
            $byMember[$member->id] = ['member' => $member, 'bills' => []];
        }
        // A bill can belong to a member not listed anymore (moved to another city)
        foreach ($bills as $bill) {
            $byMember[$bill->member_id] ??= ['member' => $bill->member, 'bills' => []];
            $byMember[$bill->member_id]['bills'][] = $bill;
        }
        uasort($byMember, fn($a, $b) => strcasecmp($a['member']->FullName, $b['member']->FullName));

        $rows = [];
        foreach ($byMember as $item) {
            foreach ($item['bills'] ?: [null] as $bill) {
                $rows[] = ['member' => $item['member'], 'bill' => $bill];
            }
        }

        return $rows;
    }

    public function overview(int|null $memberStatus = null, int|null $teamId = null, int $year = 0, int $billTemplateId = 0)
    {
        $this->Authorization->authorize($this->Bills->newEmptyEntity(), 'overview');

        $billTemplates = $this->Get->BillTemplates();
        $memberStatus = $this->getPrefSession('memberStatus', $memberStatus, 1);
        $pref = $this->getPref($teamId);

        $startDate = $this->config['dateSeasonStart'];
        $startDate->sub(new \DateInterval('P' . $year . 'Y'));

        $bills = $this->getBills(0, $memberStatus, $pref->teamId, $pref->siteId)
            ->where(['Bills.created >' => $startDate, 'link_membership_fee =' => 0])
            ->contain('Members')->orderBy('Members.first_name');
        if ($billTemplateId > 0) {
            $billFilter = $this->Get->BillTemplates($billTemplateId);
            $bills->where(['Bills.label LIKE' => $billFilter->label . '%']);
        }

        $members = $this->getMembers($pref->teamId, 1, $pref->siteId);
        //$billTemplates = $this->Get->BillTemplates();

        $result = array();
        $colName = array();
        $colIndex = 0;

        $wide = 'Wide';
        $this->set(compact('wide', 'memberStatus', 'billTemplates', 'billTemplateId', 'year'));

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
            //return $this->redirect(['controller' => 'Bills', 'action' => 'overview', $memberStatus, $teamId, $year, $billTemplateId]);
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
