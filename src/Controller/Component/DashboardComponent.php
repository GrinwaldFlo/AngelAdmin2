<?php
namespace App\Controller\Component;

use App\Model\Entity\Member;
use Cake\Controller\Component;
use Cake\I18n\Date;
use Cake\ORM\TableRegistry;

/**
 * Dashboard Component
 *
 * Collects the statistics of the admin dashboard, filtered by site (0 = all sites)
 */
class DashboardComponent extends Component
{
    /**
     * Age brackets: minimum age => label
     */
    public const AGE_BRACKETS = [0 => '0-7', 8 => '8-11', 12 => '12-15', 16 => '16-17', 18 => '18+'];

    public const BIRTHDAY_DAYS = 7;
    public const ATTENDANCE_DAYS = 30;
    public const UPCOMING_DAYS = 7;
    public const MONTHS = 12;

    /**
     * Members statistics
     */
    public function members(int $siteId, \DateTimeInterface $seasonStart, int $year): array
    {
        $membersTable = TableRegistry::getTableLocator()->get('Members');
        $active = $membersTable->CountActiveMembers($siteId);

        $registrations = TableRegistry::getTableLocator()->get('Registrations')->find()
            ->where([
                'year =' => $year,
                'member_id IN' => $membersTable->queryInSite($siteId)->select(['Members.id']),
            ]);
        $signValidated = (clone $registrations)->where(['validation_id >' => 0])->count();

        $list = $membersTable->queryInSite($siteId)
            ->select(['Members.id', 'Members.first_name', 'Members.last_name', 'Members.date_birth', 'Members.gender_id'])
            ->all();

        return [
            'active' => $active,
            'registered' => $active - $membersTable->CountNotRegistered($siteId),
            'feesPaid' => $membersTable->CountActiveFeesPaid($siteId),
            'new' => $membersTable->queryInSite($siteId)->where(['Members.date_arrival >=' => $seasonStart])->count(),
            'left' => $membersTable->queryInSite($siteId, false)->where(['Members.date_fin >=' => $seasonStart])->count(),
            'signValidated' => $signValidated,
            'signPending' => $registrations->count() - $signValidated,
            // Members without team are not in any site
            'withoutTeam' => $siteId == 0 ? $membersTable->CountActiveWithoutTeam() : null,
            'genders' => $this->genders($list),
            'ages' => $this->ages($list),
            'birthdays' => $this->birthdays($list),
            'birthdayDays' => self::BIRTHDAY_DAYS,
        ];
    }

    /**
     * Finances statistics
     */
    public function finances(int $siteId, \DateTimeInterface $seasonStart): array
    {
        $billsTable = TableRegistry::getTableLocator()->get('Bills');
        $open = ['Bills.paid =' => 0, 'Bills.canceled =' => 0];
        $late = $billsTable->GetLateCondition();
        $monthsFrom = (new \DateTime('first day of this month'))->modify('-' . (self::MONTHS - 1) . ' months')->setTime(0, 0);

        return [
            'openCount' => $billsTable->GetCount($open, $siteId),
            'openSum' => (int)$billsTable->GetSum($open, $siteId),
            'lateCount' => $billsTable->GetCount($late, $siteId),
            'lateSum' => (int)$billsTable->GetSum($late, $siteId),
            'reminderCount' => $billsTable->GetCount($open + ['Bills.reminder >' => 0], $siteId),
            'draftCount' => $billsTable->GetCount($open + ['Bills.printed =' => 0], $siteId),
            'invoiced' => (int)$billsTable->GetSumInvoicedFrom($seasonStart, $siteId),
            'invoicedPaid' => (int)$billsTable->GetSumInvoicedPaidFrom($seasonStart, $siteId),
            'fees' => (int)$billsTable->GetSumFeesFrom($seasonStart, $siteId),
            'feesPaid' => (int)$billsTable->GetSumPaidFees($seasonStart, $siteId),
            'monthly' => $billsTable->GetMonthlyFrom($monthsFrom, $siteId),
        ];
    }

    /**
     * Attendance statistics
     */
    public function attendance(int $siteId, \DateTimeInterface $seasonStart): array
    {
        $now = new \DateTime();
        $meetingsTable = TableRegistry::getTableLocator()->get('Meetings');
        $byTeam = TableRegistry::getTableLocator()->get('Presences')->CountStatesByTeam(
            (new \DateTime('today'))->modify('-' . self::ATTENDANCE_DAYS . ' days'),
            $now,
            $siteId
        );

        $states = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        foreach ($byTeam as $teamStates) {
            foreach ($teamStates as $state => $nb) {
                $states[$state] += $nb;
            }
        }

        $upcoming = $meetingsTable->find()->contain('Teams')
            ->where(['Meetings.meeting_date >=' => $now, 'Meetings.meeting_date <=' => (new \DateTime())->modify('+' . self::UPCOMING_DAYS . ' days')])
            ->orderBy(['Meetings.meeting_date' => 'ASC']);
        $seasonEvents = $meetingsTable->find()->contain('Teams')
            ->where(['Meetings.meeting_date >=' => $seasonStart, 'Meetings.meeting_date <=' => $now]);
        if ($siteId > 0) {
            $upcoming->where(['Teams.site_id =' => $siteId]);
            $seasonEvents->where(['Teams.site_id =' => $siteId]);
        }

        return [
            'states' => $states,
            'rate' => self::attendanceRate($states),
            'teamRates' => array_map([self::class, 'attendanceRate'], $byTeam),
            'upcoming' => $upcoming->all(),
            'seasonEvents' => $seasonEvents->count(),
            'days' => self::ATTENDANCE_DAYS,
            'upcomingDays' => self::UPCOMING_DAYS,
        ];
    }

    /**
     * Active teams of the site
     */
    public function teams(int $siteId): array
    {
        $teams = TableRegistry::getTableLocator()->get('Teams')->find('all')
            ->where(['active =' => 1])
            ->orderBy(['name' => 'ASC']);
        if ($siteId > 0) {
            $teams->where(['site_id =' => $siteId]);
        }
        return $teams->toArray();
    }

    /**
     * Present or late among the recorded presences, in percent
     * @param array $states [state => count]
     * @return int|null null if nothing recorded
     */
    public static function attendanceRate(array $states): ?int
    {
        $total = array_sum($states);
        if ($total == 0) {
            return null;
        }
        return (int)round((($states[1] ?? 0) + ($states[3] ?? 0)) * 100 / $total);
    }

    private function genders(iterable $members): array
    {
        $labels = (new Member())->GenderList;
        $result = array_fill_keys(array_keys($labels), 0);
        foreach ($members as $member) {
            $result[$member->gender_id] = ($result[$member->gender_id] ?? 0) + 1;
        }
        $named = [];
        foreach ($result as $genderId => $nb) {
            if ($nb > 0) {
                // Unexpected ids are added to the unknown ones
                $label = $labels[$genderId] ?? $labels[0];
                $named[$label] = ($named[$label] ?? 0) + $nb;
            }
        }
        return $named;
    }

    private function ages(iterable $members): array
    {
        $result = array_fill_keys(self::AGE_BRACKETS, 0);
        $unknown = 0;
        $today = Date::today();
        foreach ($members as $member) {
            if (empty($member->date_birth)) {
                $unknown++;
                continue;
            }
            $age = $member->date_birth->diff($today)->y;
            foreach (array_reverse(self::AGE_BRACKETS, true) as $minAge => $label) {
                if ($age >= $minAge) {
                    $result[$label]++;
                    break;
                }
            }
        }
        if ($unknown > 0) {
            $result[__('Unknown')] = $unknown;
        }
        return $result;
    }

    /**
     * Next birthdays
     * @return array [['member' => Member, 'date' => \DateTime, 'age' => int]] sorted by date
     */
    private function birthdays(iterable $members): array
    {
        $today = new \DateTime('today');
        $limit = (new \DateTime('today'))->modify('+' . self::BIRTHDAY_DAYS . ' days');
        $result = [];
        foreach ($members as $member) {
            if (empty($member->date_birth)) {
                continue;
            }
            $next = (new \DateTime('today'))->setDate((int)$today->format('Y'), (int)$member->date_birth->format('m'), (int)$member->date_birth->format('d'));
            if ($next < $today) {
                $next->modify('+1 year');
            }
            if ($next <= $limit) {
                $result[] = ['member' => $member, 'date' => $next, 'age' => (int)$next->format('Y') - (int)$member->date_birth->format('Y')];
            }
        }
        usort($result, fn($a, $b) => $a['date'] <=> $b['date']);
        return $result;
    }
}
