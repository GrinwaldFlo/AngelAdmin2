<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCoachPayments extends BaseMigration
{
    /**
     * Coach presence and payment module (docs/coach-payment-implementation.md)
     * Only new tables: the existing tables are not altered
     *
     * @return void
     */
    public function up(): void
    {
        // Presence types of a site (label and amount paid to the coach)
        $this->table('coach_presence_types')
            ->addColumn('site_id', 'integer')
            ->addColumn('label', 'string', ['limit' => 50])
            ->addColumn('amount', 'decimal', ['precision' => 8, 'scale' => 2, 'default' => 0])
            ->addColumn('sort', 'integer', ['default' => 0])
            ->addColumn('active', 'boolean', ['default' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['site_id'])
            ->addForeignKey('site_id', 'sites', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // IBAN of a coach for a site
        $this->table('coach_accounts')
            ->addColumn('member_id', 'integer')
            ->addColumn('site_id', 'integer')
            ->addColumn('iban', 'string', ['limit' => 34])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['member_id', 'site_id'], ['unique' => true])
            ->addIndex(['site_id'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('site_id', 'sites', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Payments of a site to a coach
        $this->table('coach_payments')
            ->addColumn('member_id', 'integer')
            ->addColumn('site_id', 'integer')
            ->addColumn('amount', 'decimal', ['precision' => 8, 'scale' => 2, 'default' => 0])
            ->addColumn('paid_date', 'date')
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['member_id', 'site_id'])
            ->addIndex(['site_id'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->addForeignKey('site_id', 'sites', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();

        // Presence type of a coach at a meeting, amount copied when paid
        $this->table('coach_presences')
            ->addColumn('meeting_id', 'integer')
            ->addColumn('member_id', 'integer')
            ->addColumn('coach_presence_type_id', 'integer')
            ->addColumn('coach_payment_id', 'integer', ['null' => true, 'default' => null])
            ->addColumn('amount', 'decimal', ['precision' => 8, 'scale' => 2, 'null' => true, 'default' => null])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['meeting_id', 'member_id'], ['unique' => true])
            ->addIndex(['member_id'])
            ->addIndex(['coach_presence_type_id'])
            ->addIndex(['coach_payment_id'])
            ->addForeignKey('meeting_id', 'meetings', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('coach_presence_type_id', 'coach_presence_types', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->addForeignKey('coach_payment_id', 'coach_payments', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        $this->table('coach_presences')->drop()->save();
        $this->table('coach_payments')->drop()->save();
        $this->table('coach_accounts')->drop()->save();
        $this->table('coach_presence_types')->drop()->save();
    }
}
