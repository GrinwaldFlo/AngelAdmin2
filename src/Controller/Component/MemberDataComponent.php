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

        $memberDocs = $controller->fetchTable('MemberDocs');
        $docs = $memberDocs->find('all', order: ['title' => 'asc']);

        $files = [];
        foreach ($docs as $key => $value) {
            if ($member->DocExists($value->name)) {
                $files[$value->name]['url'] = $member->GetDocUrl($value->name);
                $files[$value->name]['title'] = $value->title;
            }
        }

        $attendance = [];
        foreach ($member->presences as $item) {
            $attendance[$item->Season($config['firstDaySeason'])][$item->id] = $item;
        }

        usort($member->fields, fn($a, $b) => $a->field_type->sort <=> $b->field_type->sort);

        return [
            'member' => $member,
            'files' => $files,
            'title' => $member->fullName,
            'attendance' => $attendance
        ];
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
                ->contain(['Teams', 'Bills', 'Presences', 'Users', 'Presences.Meetings', 'Bills.Members', 'Fields', 'Fields.FieldTypes', 'Bills.Sites'])
                ->FirstOrFail();
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            return [
                'error' => 'hash_not_found',
                'hashError' => true,
                'contactEmail' => $config['email'] ?? 'not@found.com'
            ];
        }

        $memberDocs = $controller->fetchTable('MemberDocs');
        $docs = $memberDocs->find('all', order: ['title' => 'asc']);

        $files = array();
        foreach ($docs as $key => $value) {
            if ($member->DocExists($value->name)) {
                $files[$value->name]['url'] = $member->GetDocUrl($value->name);
                $files[$value->name]['title'] = $value->title;
            }
        }

        $attendance = array();
        foreach ($member->presences as $item) {
            $attendance[$item->Season($config['firstDaySeason'])][$item->id] = $item;
        }

        return [
            'member' => $member,
            'files' => $files,
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
            $this->createField($fieldReg, $mf->member_id, 2, trim($mf->contact1_natel));
            $this->createField($fieldReg, $mf->member_id, 3, trim($mf->contact1_email));
            $this->createField($fieldReg, $mf->member_id, 4, trim($mf->contact2_first_name . " " . $mf->contact2_last_name));
            $this->createField($fieldReg, $mf->member_id, 5, trim($mf->contact2_natel));
            $this->createField($fieldReg, $mf->member_id, 6, trim($mf->contact2_email));
            $this->createField($fieldReg, $mf->member_id, 7, trim($mf->facebook));
            $this->createField($fieldReg, $mf->member_id, 8, trim($mf->problemes_medicaux));
            $this->createField($fieldReg, $mf->member_id, 9, trim($mf->remarque));
            $this->createField($fieldReg, $mf->member_id, 10, trim($mf->a_connu_le_club_de));
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

    /**
     * Get subventions content
     */
    public function getSubventionsContent()
    {
        $contentsTable = TableRegistry::getTableLocator()->get('Contents');
        $contentsTmp = $contentsTable->find('all')->where(['location =' => 11]);
        $contents = [];
        foreach ($contentsTmp as $value) {
            $contents[$value->sort] = $value->text;
        }
        return $contents;
    }
}
