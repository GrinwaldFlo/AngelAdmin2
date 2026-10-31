<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\MailingItem;
use App\Model\Table\MailingItemsTable;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Table\MailingItemsTable
 */
class MailingItemsTableTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Mailings', 'app.MailingItems', 'app.Teams', 'app.TeamsMembers', 'app.Sites'];

    private MailingItemsTable $MailingItems;

    protected function setUp(): void
    {
        parent::setUp();
        $this->MailingItems = $this->getTableLocator()->get('MailingItems');
        foreach ([100, 101, 102] as $id) {
            $this->addMember($id);
        }
    }

    private function addMailing(): int
    {
        $mailings = $this->getTableLocator()->get('Mailings');

        return $mailings->saveOrFail($mailings->newEntity(['title' => 'Hello', 'content' => 'Hi', 'status' => 0]))->id;
    }

    private function addItem(int $mailingId, int $memberId, int $status, int $attempts = 0): int
    {
        return $this->MailingItems->saveOrFail($this->MailingItems->newEntity(
            ['mailing_id' => $mailingId, 'member_id' => $memberId, 'email' => "m$memberId@example.com", 'status' => $status, 'attempts' => $attempts],
            ['accessibleFields' => ['*' => true]]
        ))->id;
    }

    public function testBuildRules(): void
    {
        $item = $this->MailingItems->newEntity(['mailing_id' => 999, 'member_id' => 999, 'status' => 0], ['accessibleFields' => ['*' => true]]);

        $this->assertFalse($this->MailingItems->save($item));
        $this->assertArrayHasKey('mailing_id', $item->getErrors());
        $this->assertArrayHasKey('member_id', $item->getErrors());
    }

    public function testNextPendingLeastAttemptsFirst(): void
    {
        $mailingId = $this->addMailing();
        $otherMailingId = $this->addMailing();
        $retried = $this->addItem($mailingId, 100, MailingItem::PENDING, 2);
        $this->addItem($mailingId, 101, MailingItem::SENT);
        $first = $this->addItem($mailingId, 102, MailingItem::PENDING);
        $second = $this->addItem($mailingId, 1, MailingItem::PENDING);
        $this->addItem($otherMailingId, 100, MailingItem::PENDING);

        $items = $this->MailingItems->nextPending($mailingId, 10);
        $this->assertSame([$first, $second, $retried], $items->extract('id')->toList());
        $this->assertSame('First102', $items->first()->member->first_name);

        $this->assertCount(2, $this->MailingItems->nextPending($mailingId, 2));
    }

    public function testCountsByMailing(): void
    {
        $mailingId = $this->addMailing();
        $emptyMailingId = $this->addMailing();
        $this->addItem($mailingId, 100, MailingItem::PENDING);
        $this->addItem($mailingId, 101, MailingItem::SENT);
        $this->addItem($mailingId, 102, MailingItem::SENT);
        $this->addItem($mailingId, 1, MailingItem::INVALID);

        $this->assertSame([
            $mailingId => ['total' => 4, 'pending' => 1, 'sent' => 2, 'invalid' => 1],
            $emptyMailingId => ['total' => 0, 'pending' => 0, 'sent' => 0, 'invalid' => 0],
        ], $this->MailingItems->countsByMailing([$mailingId, $emptyMailingId]));
        $this->assertSame([], $this->MailingItems->countsByMailing([]));
    }
}
