<?php

declare(strict_types=1);

namespace App\Test\TestSuite;

use Cake\I18n\DateTime;

/**
 * Helpers of the coach payment module tests, on top of TestDataTrait
 *
 * Needs the fixtures of the coach tables (CoachPresenceTypes, CoachAccounts, CoachPresences, CoachPayments)
 * and of TestDataTrait (Teams, Members, TeamsCoaches, Meetings, Sites, ...).
 */
trait CoachPaymentDataTrait
{
    /** Valid classic Swiss IBAN */
    protected const IBAN = 'CH9300762011623852957';

    /** Fixtures of the coach payment tests */
    protected const COACH_FIXTURES = [
        'app.CoachPresenceTypes', 'app.CoachPresenceLimits', 'app.CoachAccounts', 'app.CoachPresences',
        'app.CoachPayments',
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Meetings', 'app.Presences', 'app.Configurations', 'app.Bills', 'app.Fields', 'app.FieldTypes',
        'app.Registrations', 'app.Contents', 'app.MemberDocs',
    ];

    /**
     * @return int id of the type
     */
    protected function addPresenceType(int $siteId, string $label, float $amount, bool $active = true, int $sort = 0): int
    {
        $types = $this->getTableLocator()->get('CoachPresenceTypes');
        $type = $types->newForSite($siteId, ['label' => $label, 'amount' => $amount, 'active' => $active, 'sort' => $sort]);

        return $types->saveOrFail($type)->id;
    }

    /**
     * Maximum number of coaches with a type at one meeting of a team
     */
    protected function addPresenceLimit(int $teamId, int $typeId, int $max): void
    {
        $limits = $this->getTableLocator()->get('CoachPresenceLimits');
        $limit = $limits->newEntity(['max_count' => $max]);
        $limit->team_id = $teamId;
        $limit->coach_presence_type_id = $typeId;
        $limits->saveOrFail($limit);
    }

    /**
     * @param string $date e.g. '-2 days' (due) or '+2 days' (planned)
     * @return int id of the meeting
     */
    protected function addMeeting(int $teamId, string $date = '-2 days', string $name = 'Training'): int
    {
        $meetings = $this->getTableLocator()->get('Meetings');
        $meeting = $meetings->newEntity(['meeting_date' => new DateTime($date), 'team_id' => $teamId, 'name' => $name]);

        return $meetings->saveOrFail($meeting)->id;
    }

    /**
     * Presence of a coach, without the checks of setType()
     * @return int id of the presence
     */
    protected function addCoachPresence(int $meetingId, int $memberId, int $typeId): int
    {
        $presences = $this->getTableLocator()->get('CoachPresences');
        $presence = $presences->newEmptyEntity();
        $presence->meeting_id = $meetingId;
        $presence->member_id = $memberId;
        $presence->coach_presence_type_id = $typeId;

        return $presences->saveOrFail($presence)->id;
    }

    /**
     * Payment row without presences and without the events (e.g. a concurrent payment inside a callback)
     * @return int id of the payment
     */
    protected function addCoachPayment(int $memberId, int $siteId, float $amount = 10): int
    {
        $payments = $this->getTableLocator()->get('CoachPayments');
        $payments->insertQuery()
            ->insert(['member_id', 'site_id', 'amount', 'paid_date'])
            ->values(['member_id' => $memberId, 'site_id' => $siteId, 'amount' => $amount, 'paid_date' => date('Y-m-d')])
            ->execute();

        return (int)$payments->find()->select(['id'])->orderBy(['id' => 'DESC'])->firstOrFail()->id;
    }

    protected function addCoachAccount(int $memberId, int $siteId, string $iban = self::IBAN): void
    {
        $this->getTableLocator()->get('CoachAccounts')->setIban($memberId, $siteId, $iban);
    }

    /**
     * Address used by the QR code (the fixture member has a postcode and a city)
     */
    protected function setAddress(int $memberId): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['address' => 'Rue du Lac 1', 'postcode' => 1000, 'city' => 'Lausanne'], ['id' => $memberId]);
    }

    /**
     * Site 1 with a complete address (the fixture has placeholder texts)
     */
    protected function setSiteAddress(int $siteId = 1): void
    {
        $this->getTableLocator()->get('Sites')->updateAll(
            ['account_designation' => 'Club Lausanne', 'address' => 'Rue du Club 2', 'postcode' => '1000', 'city' => 'Lausanne'],
            ['id' => $siteId]
        );
    }
}
