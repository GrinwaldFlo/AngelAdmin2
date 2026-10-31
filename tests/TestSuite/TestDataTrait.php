<?php

declare(strict_types=1);

namespace App\Test\TestSuite;

use App\Controller\AppController;
use App\Model\Entity\TeamsCoach;
use App\Model\Entity\User;
use Authentication\Identity as AuthenticationIdentity;
use Authorization\AuthorizationService;
use Authorization\Identity;
use Authorization\IdentityInterface;
use Authorization\Policy\OrmResolver;
use Cake\Http\ServerRequest;
use Cake\Http\Session;

/**
 * Helpers to add test rows (teams, members, coaches, roles) on top of the fixtures
 *
 * Needs the fixtures of the used tables (Teams, TeamsMembers, Members, Users, Roles, TeamsCoaches).
 */
trait TestDataTrait
{
    /**
     * All the rights of a role, set to false by setRole() unless given
     */
    private static array $roleRights = [
        'MemberViewAll', 'MemberEditAll', 'MemberEditOwn', 'BillViewAll', 'BillEditAll',
        'BillValidate', 'Admin', 'Editor', 'TeamScoped',
    ];

    protected function addTeam(int $id, string $name = '', bool $active = true, int $siteId = 1): void
    {
        $this->getTableLocator()->get('Teams')->insertQuery()
            ->insert(['id', 'name', 'membership_fee', 'active', 'site_id'])
            ->values(['id' => $id, 'name' => $name ?: 'Team' . $id, 'membership_fee' => 1, 'active' => $active, 'site_id' => $siteId])
            ->execute();
    }

    /**
     * @param int[] $teamIds teams of the member
     * @param array $fields columns overriding the default values
     */
    protected function addMember(int $id, array $teamIds = [], array $fields = []): void
    {
        $this->getTableLocator()->get('Members')->insertQuery()
            ->insert(['id', 'first_name', 'last_name', 'gender_id', 'email', 'email_valid', 'date_arrival', 'hash', 'active', 'language', ...array_keys($fields)])
            ->values($fields + [
                'id' => $id, 'first_name' => 'First' . $id, 'last_name' => 'Last' . $id, 'gender_id' => 1, 'email' => "m$id@example.com",
                'email_valid' => 1, 'date_arrival' => '2026-01-01', 'hash' => sha1('member' . $id), 'active' => true, 'language' => 'en',
            ])
            ->execute();
        foreach ($teamIds as $teamId) {
            $this->getTableLocator()->get('TeamsMembers')->insertQuery()
                ->insert(['team_id', 'member_id'])
                ->values(['team_id' => $teamId, 'member_id' => $id])
                ->execute();
        }
    }

    protected function addUser(int $id, int $memberId, int $roleId = 1): void
    {
        $this->getTableLocator()->get('Users')->insertQuery()
            ->insert(['id', 'username', 'password', 'role_id', 'member_id', 'pass_key', 'tokenhash'])
            ->values(['id' => $id, 'username' => 'user' . $id, 'password' => 'x', 'role_id' => $roleId, 'member_id' => $memberId, 'pass_key' => '', 'tokenhash' => ''])
            ->execute();
    }

    /**
     * Open bill (not printed, not paid) of 10.-
     * @param array $fields columns overriding the default values
     */
    protected function addBill(int $id, int $memberId, array $fields = []): void
    {
        $values = $fields + [
            'id' => $id, 'member_id' => $memberId, 'label' => 'Bill' . $id, 'amount' => 10, 'printed' => false, 'paid' => false,
            'reminder' => 0, 'due_date' => '2026-01-01', 'due_date_ori' => '2026-01-01', 'canceled' => false, 'state_id' => 1,
            'tokenhash' => sha1('bill' . $id), 'site_id' => 1,
        ];
        $this->getTableLocator()->get('Bills')->insertQuery()
            ->insert(array_keys($values))
            ->values($values)
            ->execute();
    }

    protected function addCoach(int $teamId, int $memberId, int $type = TeamsCoach::COACH): void
    {
        $this->getTableLocator()->get('TeamsCoaches')->insertQuery()
            ->insert(['team_id', 'member_id', 'type'])
            ->values(['team_id' => $teamId, 'member_id' => $memberId, 'type' => $type])
            ->execute();
    }

    /**
     * Coach scenario: teams 2 (coached by member 1) and 3 (not coached),
     * members 100 in team 2, 101 in team 3, 102 in teams 2 and 3, 103 inactive in team 2
     */
    protected function addCoachedTeams(): void
    {
        $this->addTeam(2, 'Juniors');
        $this->addTeam(3, 'Seniors');
        $this->addMember(100, [2]);
        $this->addMember(101, [3]);
        $this->addMember(102, [2, 3]);
        $this->addMember(103, [2], ['active' => false]);
        $this->addCoach(2, 1);
    }

    /**
     * Entity built as if loaded from the database (not new), all fields set
     * @template T of \Cake\Datasource\EntityInterface
     * @param class-string<T> $class
     * @return T
     */
    protected function entity(string $class, array $data = [], bool $new = false): object
    {
        return new $class($data, ['markNew' => $new, 'guard' => false]);
    }

    /**
     * Give a role only the given rights
     * @param string[] $rights e.g. ['MemberViewAll', 'TeamScoped']
     */
    protected function setRole(array $rights, int $roleId = 1): void
    {
        $values = [];
        foreach (self::$roleRights as $right) {
            $values[$right] = in_array($right, $rights, true);
        }
        $this->getTableLocator()->get('Roles')->updateAll($values, ['id' => $roleId]);
    }

    /**
     * Identity of user 1 (member 1 by default) with only the given rights, as given to the policies
     * Wrapped as by the middlewares, its can() goes through the real authorization service
     * @param string[] $rights
     */
    protected function identity(array $rights, int $memberId = 1, int $userId = 1): IdentityInterface
    {
        $this->setRole($rights);
        $user = new User(['role_id' => 1, 'member_id' => $memberId]);
        $user->id = $userId;

        return new Identity(new AuthorizationService(new OrmResolver()), new AuthenticationIdentity($user));
    }

    /**
     * Data posted by the member form
     */
    protected function memberForm(array $values = []): array
    {
        return $values + [
            'first_name' => 'New', 'last_name' => 'Member', 'gender_id' => 1, 'email' => 'new@example.com', 'date_birth' => '2015-01-01',
            'address' => 'Rue 1', 'city' => 'Renens', 'phone_mobile' => '', 'phone_home' => '', 'email_valid' => 1, 'nationality' => '',
            'date_arrival' => '2026-01-01', 'multi_payment' => 1, 'membership_fee_paid' => 0, 'discount' => 0, 'date_fin' => '',
            'active' => 1, 'registered' => 0, 'bvr' => 0, 'communication_method_id' => 1,
        ];
    }

    /**
     * Controller built outside of the middlewares, to call its methods directly
     * @template T of \App\Controller\AppController
     * @param class-string<T> $class
     * @param \Authorization\IdentityInterface|null $identity null = not logged in
     * @param array $request ServerRequest config (params, cookies, session, ...)
     * @return T
     */
    protected function controller(string $class = AppController::class, ?IdentityInterface $identity = null, array $request = []): AppController
    {
        $serverRequest = (new ServerRequest($request + ['session' => new Session()]))->withAttribute('identity', $identity);

        return new $class($serverRequest);
    }

    /**
     * Log in user 1 (member 1) with only the given rights, texts are checked in English
     * @param string[]|null $rights null = keep the fixture role (all rights)
     */
    protected function loginWith(?array $rights = null, array $session = []): void
    {
        if ($rights !== null) {
            $this->setRole($rights);
        }
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'en'], ['id' => 1]);
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)] + $session);
    }
}
