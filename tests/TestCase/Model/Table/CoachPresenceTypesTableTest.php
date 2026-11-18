<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CoachPresenceTypesTable;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Table\CoachPresenceTypesTable
 */
class CoachPresenceTypesTableTest extends TestCase
{
    use CoachPaymentDataTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    protected CoachPresenceTypesTable $CoachPresenceTypes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CoachPresenceTypes = $this->getTableLocator()->get('CoachPresenceTypes');
    }

    public function testValidation(): void
    {
        $this->assertTrue($this->CoachPresenceTypes->newForSite(1, ['label' => '', 'amount' => 5])->hasErrors());
        $this->assertTrue($this->CoachPresenceTypes->newForSite(1, ['label' => 'Main', 'amount' => -1])->hasErrors());
        $this->assertTrue($this->CoachPresenceTypes->newForSite(1, ['label' => 'Main', 'amount' => 'abc'])->hasErrors());
        $this->assertFalse($this->CoachPresenceTypes->newForSite(1, ['label' => 'Main', 'amount' => 10.5])->hasErrors());
    }

    public function testSiteCanNotBeSetFromTheData(): void
    {
        $type = $this->CoachPresenceTypes->newForSite(1, ['label' => 'Main', 'amount' => 10, 'site_id' => 2]);

        $this->assertSame(1, $type->site_id);
    }

    public function testFindOfSiteSortedAndActive(): void
    {
        $this->addSite(2);
        $second = $this->addPresenceType(1, 'Second', 5, true, 2);
        $first = $this->addPresenceType(1, 'First', 10, true, 1);
        $inactive = $this->addPresenceType(1, 'Old', 3, false, 0);
        $this->addPresenceType(2, 'Other', 7);

        $active = $this->CoachPresenceTypes->find('ofSite', siteId: 1)->all()->extract('id')->toList();
        $all = $this->CoachPresenceTypes->find('ofSite', siteId: 1, activeOnly: false)->all()->extract('id')->toList();

        $this->assertSame([$first, $second], $active);
        $this->assertSame([$inactive, $first, $second], $all);
    }

    public function testUsedTypeCanNotBeDeleted(): void
    {
        $this->addCoach(1, 1);
        $used = $this->addPresenceType(1, 'Main', 10);
        $unused = $this->addPresenceType(1, 'Secondary', 5);
        $this->addCoachPresence($this->addMeeting(1), 1, $used);

        $this->assertFalse($this->CoachPresenceTypes->delete($this->CoachPresenceTypes->get($used)));
        $this->assertTrue($this->CoachPresenceTypes->exists(['id' => $used]));
        $this->assertTrue($this->CoachPresenceTypes->delete($this->CoachPresenceTypes->get($unused)));
    }
}
