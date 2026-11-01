<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\SecondHandItem;
use App\Model\Entity\SecondHandLog;
use Cake\I18n\DateTime;
use Cake\ORM\Table;

/**
 * SecondHandLogs Model: log of all the second-hand items, without the picture
 *
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 */
class SecondHandLogsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('second_hand_logs');
        $this->setPrimaryKey('id');

        $this->belongsTo('Members', [
            'foreignKey' => 'member_id',
        ]);
    }

    /**
     * Copy of a new item
     * @param \App\Model\Entity\SecondHandItem $item saved item, with its profile and category
     */
    public function record(SecondHandItem $item): SecondHandLog
    {
        $log = $this->newEmptyEntity();
        $log->set([
            'item_id' => $item->id,
            'member_id' => $item->profile->member_id,
            'seller_name' => $item->profile->name,
            'category' => $item->category->name,
            'description' => $item->description,
            'size' => $item->size,
            'price' => $item->price,
            'status' => SecondHandLog::FOR_SALE,
            'created' => $item->created ?? DateTime::now(),
        ], ['guard' => false]);

        return $this->saveOrFail($log);
    }

    /**
     * Final status of an item: sold or deleted
     */
    public function close(int $itemId, int $status): void
    {
        $this->updateAll(['status' => $status, 'closed' => DateTime::now()], ['item_id' => $itemId]);
    }
}
