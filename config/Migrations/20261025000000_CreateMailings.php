<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateMailings extends AbstractMigration
{
    /**
     * Emails to teams: a mailing is the log line of a batch, its items are the queue (one per recipient)
     * so a batch stopped by the SMTP server can be continued later
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('mailings')
            ->addColumn('title', 'string', ['limit' => 100])
            ->addColumn('content', 'text')
            ->addColumn('teams', 'string', ['limit' => 1000, 'default' => ''])
            ->addColumn('status', 'integer', ['default' => 0])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addColumn('sentDate', 'datetime', ['null' => true])
            ->addColumn('team_id_list', 'string', ['limit' => 255, 'default' => ''])
            ->create();

        $this->table('mailing_items')
            ->addColumn('mailing_id', 'integer')
            ->addColumn('member_id', 'integer')
            ->addColumn('email', 'string', ['limit' => 255, 'default' => ''])
            ->addColumn('status', 'integer', ['default' => 0])
            ->addColumn('attempts', 'integer', ['default' => 0])
            ->addColumn('error', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('sent', 'datetime', ['null' => true])
            ->addIndex(['mailing_id', 'status'])
            ->addForeignKey('mailing_id', 'mailings', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
