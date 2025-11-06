<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

/**
 * Member List Component
 * 
 * Handles various member list views and filtering operations
 */
class MemberListComponent extends Component
{
    /**
     * Get members with pagination and filtering
     */
    public function getMembersList($pref, $finder = 'Members', $contain = ['Teams'])
    {
        $controller = $this->getController();
        
        return $controller->paginate($controller->Members->find(
            $finder,
            teamId: $pref->teamId,
            memberFilter: $pref->memberFilter,
            siteId: $pref->siteId
        )->contain($contain));
    }

    /**
     * Get birthday list
     */
    public function getBirthdaysList($teamId, $memberFilter)
    {
        $controller = $this->getController();
        
        return $controller->Members->find(
            'Members',
            teamId: $teamId,
            memberFilter: $memberFilter
        )->contain('Teams')->orderBy(['DAYOFYEAR(date_birth)' => 'ASC']);
    }

    /**
     * Get new members without teams
     */
    public function getNewMembersList()
    {
        $controller = $this->getController();
        
        return $controller->Members->find('All')
            ->leftJoinWith('Teams')
            ->where(['Members.active =' => 1])
            ->where(['Teams.name IS' => NULL])
            ->orderBy(['first_name' => 'ASC']);
    }

    /**
     * Get members for email list
     */
    public function getEmailList($teamId, $memberFilter)
    {
        $controller = $this->getController();
        
        return $controller->paginate($controller->Members->find(
            'Members',
            teamId: $teamId,
            memberFilter: $memberFilter,
            contain: ['Teams', 'Fields', 'Fields.FieldTypes']
        )->contain('Teams'));
    }

    /**
     * Get family reduction list
     */
    public function getFamilyReductionList()
    {
        $controller = $this->getController();
        $members = $controller->paginate($controller->Members->find('Members', memberFilter: 1)->contain('Teams'));

        $members2 = array();
        foreach ($members as $member) {
            $member['FullAddress'] = $member['city'] . ', ' . $this->cleanAddress($member['address']);
            array_push($members2, $member);
        }

        usort($members2, function ($a, $b) {
            return $a['FullAddress'] > $b['FullAddress'];
        });

        return $members2;
    }

    /**
     * Get members for pictures view
     */
    public function getPicturesList($teamId, $memberFilter)
    {
        $controller = $this->getController();
        
        return $controller->paginate($controller->Members->find(
            'Members',
            teamId: $teamId,
            memberFilter: $memberFilter
        )->contain('Teams'));
    }

    /**
     * Get subventions list with filtering
     */
    public function getSubventionsList($pref, $ageFilter)
    {
        $controller = $this->getController();
        
        $members = $controller->paginate($controller->Members->find(
            'Members',
            teamId: $pref->teamId,
            memberFilter: $pref->memberFilter,
            siteId: $pref->siteId
        )->contain('Teams'));

        $membersFilter = [];
        if ($pref->siteId == null) {
            $membersFilter = $members;
        } else {
            foreach ($members as $member) {
                if ($controller->memberIsInSite($member, $pref->siteId) && 
                    $controller->memberYoungerThan($member, $ageFilter)) {
                    array_push($membersFilter, $member);
                }
            }
        }

        return $this->groupMembersByCity($membersFilter);
    }

    /**
     * Group members by city
     */
    private function groupMembersByCity($members)
    {
        $membersCity = [];
        $membersCityList = [];
        
        foreach ($members as $member) {
            if (!isset($membersCity[$member->city])) {
                $membersCity[$member->city] = [];
                array_push($membersCityList, $member->city);
            }
            array_push($membersCity[$member->city], $member);
        }

        sort($membersCityList);

        return [
            'membersFilter' => $members,
            'membersCity' => $membersCity,
            'membersCityList' => $membersCityList
        ];
    }

    /**
     * Get batch register list
     */
    public function getBatchRegisterList($pref)
    {
        $controller = $this->getController();
        $pref->memberFilter = 4; // Not registered for this year

        return $controller->paginate($controller->Members->find(
            'Members',
            teamId: $pref->teamId,
            memberFilter: $pref->memberFilter,
            siteId: $pref->siteId
        )->contain('Teams'));
    }

    /**
     * Clean address by removing punctuation and common road prefixes
     * 
     * @param string $address The address to clean
     * @return string The cleaned address
     */
    private function cleanAddress($address)
    {
        // Remove dots and commas
        $cleaned = str_replace(['.', ',', '-'], ' ', $address);
        
        // Convert to lowercase
        $cleaned = strtolower($cleaned);
        
        // Remove common road prefixes (as whole words)
        $wordsToRemove = ['rte', 'de', 'route', 'ch', 'chemin', 'av', 'la', 'du', 'l\'', 'des', 'rue'];
        $pattern = '/\b(' . implode('|', $wordsToRemove) . ')\b/';
        $cleaned = preg_replace($pattern, '', $cleaned);
        
        // Remove extra spaces
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        // Trim leading/trailing spaces
        $cleaned = trim($cleaned);
        
        return $cleaned;
    }

}
