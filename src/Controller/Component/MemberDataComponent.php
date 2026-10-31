<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

/**
 * Member Data Component
 * 
 * Handles member data processing and business logic
 */
class MemberDataComponent extends Component
{
    /**
     * Prepare member view data
     */
    public function prepareMemberViewData($member, $config)
    {
        $controller = $this->getController();
        $controller->Members->getAssociation('Fields')->AddMissing($member);

        $member->late_bills_sum = $this->lateBillsSum($member->id);
        $files = $this->memberFiles($member);

        $attendance = $this->buildAttendance($member, $config);

        usort($member->fields, fn($a, $b) => $a->field_type->sort <=> $b->field_type->sort);

        return [
            'member' => $member,
            'files' => $files,
            'title' => $member->fullName,
            'attendance' => $attendance
        ];
    }

    /**
     * Sum of the late bills (unpaid, not canceled, due date passed)
     */
    private function lateBillsSum(int $memberId)
    {
        $bills = $this->getController()->Members->Bills;
        $query = $bills->find();
        $row = $query
            ->select(['late_bills_sum' => $query->func()->coalesce([$query->func()->sum('Bills.amount'), 0])])
            ->where([
                'Bills.member_id' => $memberId,
                'Bills.paid' => false,
                'Bills.canceled' => false,
                'Bills.due_date <' => \Cake\I18n\Date::today()
            ])
            ->first();

        return $row ? $row->late_bills_sum : 0;
    }

    /**
     * Documents of the member that exist, by name
     * @return array<string, array{url: string, title: string}>
     */
    private function memberFiles($member): array
    {
        $files = [];
        foreach ($this->getController()->fetchTable('MemberDocs')->find('all', order: ['title' => 'asc']) as $doc) {
            if ($member->DocExists($doc->name)) {
                $files[$doc->name] = ['url' => $member->GetDocUrl($doc->name), 'title' => $doc->title];
            }
        }

        return $files;
    }

    /**
     * Group the member presences by season, newest meeting first
     */
    private function buildAttendance($member, $config): array
    {
        $presences = $member->presences ?? [];
        usort($presences, fn($a, $b) => $b->meeting->meeting_date <=> $a->meeting->meeting_date);

        $attendance = [];
        foreach ($presences as $item) {
            $attendance[$item->Season($config['firstDaySeason'])][$item->id] = $item;
        }

        return $attendance;
    }

    /**
     * Prepare my page data
     */
    public function prepareMyPageData($hash, $config)
    {
        $controller = $this->getController();

        if (empty($hash)) {
            return ['error' => 'empty_hash'];
        }

        try {
            $member = $controller->Members->findByHash($hash)
                ->contain(['Teams', 'Bills', 'Presences', 'Users', 'Presences.Meetings', 'Bills.Members', 'Fields' => ['FieldTypes'], 'Bills.Sites'])
                ->FirstOrFail();

            $member->late_bills_sum = $this->lateBillsSum($member->id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            return [
                'error' => 'hash_not_found',
                'hashError' => true,
                'contactEmail' => $config['email'] ?? 'not@found.com'
            ];
        }

        $attendance = $this->buildAttendance($member, $config);

        return [
            'member' => $member,
            'files' => $this->memberFiles($member),
            'title' => $member->FullName,
            'attendance' => $attendance,
            'contactEmail' => $config['email'] ?? 'not@found.com'
        ];
    }

    /**
     * Migrate member field data
     */
    public function migrateMemberFields()
    {
        $controller = $this->getController();
        $mfReg = TableRegistry::getTableLocator()->get('MemberField1s');
        $fieldReg = TableRegistry::getTableLocator()->get('Fields');

        $mfs = $mfReg->find('all');

        foreach ($mfs as $mf) {
            $this->createField($fieldReg, $mf->member_id, 1, trim($mf->contact1_first_name . " " . $mf->contact1_last_name));
            $this->createField($fieldReg, $mf->member_id, 2, trim((string)$mf->contact1_natel));
            $this->createField($fieldReg, $mf->member_id, 3, trim((string)$mf->contact1_email));
            $this->createField($fieldReg, $mf->member_id, 4, trim($mf->contact2_first_name . " " . $mf->contact2_last_name));
            $this->createField($fieldReg, $mf->member_id, 5, trim((string)$mf->contact2_natel));
            $this->createField($fieldReg, $mf->member_id, 6, trim((string)$mf->contact2_email));
            $this->createField($fieldReg, $mf->member_id, 7, trim((string)$mf->facebook));
            $this->createField($fieldReg, $mf->member_id, 8, trim((string)$mf->problemes_medicaux));
            $this->createField($fieldReg, $mf->member_id, 9, trim((string)$mf->remarque));
            $this->createField($fieldReg, $mf->member_id, 10, trim((string)$mf->a_connu_le_club_de));
        }
    }

    /**
     * Create a field entry if value is not empty
     */
    private function createField($fieldReg, $memberId, $fieldTypeId, $value)
    {
        if (!empty($value)) {
            $nf = $fieldReg->newEmptyEntity();
            $nf->member_id = $memberId;
            $nf->field_type_id = $fieldTypeId;
            $nf->value = $value;
            $fieldReg->save($nf);
        }
    }
}
