<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateInitialTables extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // Create sites table first (referenced by other tables)
        $this->table('sites')
            ->addColumn('city', 'string', ['limit' => 100])
            ->addColumn('address', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('account_designation', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('postcode', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('iban', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('bic', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('feeMax', 'integer')
            ->addColumn('reminder_penalty', 'integer', ['default' => 0])
            ->addColumn('sender_email', 'string', ['limit' => 100])
            ->addColumn('sender', 'string', ['limit' => 100])
            ->addColumn('sender_phone', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('add_invoice_num', 'integer', ['default' => 0])
            ->create();

        // Create roles table
        $this->table('roles', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 50])
            ->addColumn('MemberViewAll', 'boolean')
            ->addColumn('MemberEditAll', 'boolean')
            ->addColumn('MemberEditOwn', 'boolean')
            ->addColumn('BillViewAll', 'boolean')
            ->addColumn('BillEditAll', 'boolean')
            ->addColumn('Admin', 'boolean')
            ->addColumn('BillValidate', 'boolean', ['default' => false])
            ->addColumn('Editor', 'boolean', ['default' => false])
            ->create();

        // Create members table
        $this->table('members')
            ->addColumn('first_name', 'string', ['limit' => 200])
            ->addColumn('last_name', 'string', ['limit' => 200])
            ->addColumn('date_birth', 'date', ['null' => true])
            ->addColumn('gender_id', 'integer')
            ->addColumn('address', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('postcode', 'integer', ['default' => 0, 'null' => true])
            ->addColumn('city', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('phone_mobile', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('phone_home', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('email_valid', 'boolean')
            ->addColumn('nationality', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('date_arrival', 'date')
            ->addColumn('multi_payment', 'integer', ['default' => 1])
            ->addColumn('membership_fee_paid', 'integer', ['default' => 0])
            ->addColumn('discount', 'integer', ['default' => 0])
            ->addColumn('date_fin', 'date', ['null' => true])
            ->addColumn('communication_method_id', 'integer', ['default' => 0])
            ->addColumn('active', 'boolean', ['default' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addColumn('coach', 'boolean', ['default' => false])
            ->addColumn('registered', 'boolean', ['default' => false])
            ->addColumn('bvr', 'boolean', ['default' => false])
            ->addColumn('hash', 'string', ['limit' => 50])
            ->addColumn('language', 'string', ['limit' => 5, 'default' => 'fr'])
            ->addColumn('leaving_comment', 'string', ['limit' => 1000, 'null' => true])
            ->addColumn('checked', 'boolean', ['default' => false])
            ->addColumn('reminder_sent', 'datetime', ['null' => true])
            ->addIndex(['hash'], ['unique' => true])
            ->addIndex(['first_name', 'last_name'], ['unique' => true])
            ->create();

        // Create teams table
        $this->table('teams')
            ->addColumn('name', 'string', ['limit' => 200])
            ->addColumn('membership_fee', 'integer')
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true])
            ->addColumn('description', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('site_id', 'integer', ['default' => 1])
            ->addForeignKey('site_id', 'sites', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();

        // Create teams_members junction table
        $this->table('teams_members', ['id' => false, 'primary_key' => ['team_id', 'member_id']])
            ->addColumn('team_id', 'integer')
            ->addColumn('member_id', 'integer')
            ->addForeignKey('team_id', 'teams', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Create users table
        $this->table('users', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('username', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('role_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addColumn('member_id', 'integer')
            ->addColumn('pass_key', 'string', ['limit' => 255])
            ->addColumn('tokenhash', 'string', ['limit' => 255])
            ->addColumn('lastLogin', 'datetime', ['null' => true])
            ->addIndex(['username'], ['unique' => true])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();

        // Create bills table
        $this->table('bills')
            ->addColumn('member_id', 'integer')
            ->addColumn('label', 'string', ['limit' => 200])
            ->addColumn('amount', 'integer')
            ->addColumn('printed', 'boolean')
            ->addColumn('paid', 'boolean')
            ->addColumn('reminder', 'integer')
            ->addColumn('due_date', 'date', ['default' => '1970-01-01'])
            ->addColumn('due_date_ori', 'date')
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addColumn('link_membership_fee', 'boolean', ['default' => false])
            ->addColumn('canceled', 'boolean')
            ->addColumn('state_id', 'integer')
            ->addColumn('tokenhash', 'string', ['limit' => 255])
            ->addColumn('confirmation', 'datetime', ['null' => true])
            ->addColumn('site_id', 'integer', ['default' => 1])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('site_id', 'sites', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();

        // Create bill_templates table
        $this->table('bill_templates')
            ->addColumn('label', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('amount', 'integer')
            ->addColumn('membership_fee', 'boolean', ['default' => false])
            ->addColumn('site_id', 'integer', ['default' => 1])
            ->addForeignKey('site_id', 'sites', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();

        // Create configurations table
        $this->table('configurations')
            ->addColumn('label', 'string', ['limit' => 100])
            ->addColumn('value', 'string', ['limit' => 255])
            ->create();

        // Create contents table
        $this->table('contents')
            ->addColumn('text', 'text', ['null' => true])
            ->addColumn('location', 'integer', ['default' => 0])
            ->addColumn('url', 'string', ['limit' => 1000, 'default' => ''])
            ->addColumn('team_id', 'integer', ['default' => 0])
            ->addColumn('sort', 'integer', ['default' => 0])
            ->create();

        // Create data table
        $this->table('data', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true])
            ->addColumn('param', 'integer', ['default' => 0])
            ->addColumn('value', 'string', ['limit' => 1000, 'null' => true])
            ->addColumn('data_type', 'string', ['limit' => 100])
            ->create();

        // Create field_types table
        $this->table('field_types')
            ->addColumn('label', 'string', ['limit' => 100])
            ->addColumn('style', 'integer', ['default' => 0, 'comment' => '0: Text, 1: Mail, 2:Phone, 3:Number, 4:YesNo, 5:Date'])
            ->addColumn('member_edit', 'boolean', ['default' => true])
            ->addColumn('sort', 'integer')
            ->addColumn('hidden', 'boolean', ['default' => false])
            ->addColumn('mandatory', 'boolean', ['default' => false])
            ->create();

        // Create fields table
        $this->table('fields', ['id' => false, 'primary_key' => ['member_id', 'field_type_id']])
            ->addColumn('member_id', 'integer')
            ->addColumn('field_type_id', 'integer')
            ->addColumn('value', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['member_id', 'field_type_id'], ['unique' => true])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('field_type_id', 'field_types', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Create mailings table
        $this->table('mailings')
            ->addColumn('title', 'string', ['limit' => 100])
            ->addColumn('content', 'text')
            ->addColumn('attachement1', 'string', ['limit' => 255])
            ->addColumn('attachement2', 'string', ['limit' => 255])
            ->addColumn('attachement3', 'string', ['limit' => 255])
            ->addColumn('status', 'integer')
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addColumn('sentDate', 'datetime')
            ->create();

        // Create mailing_items table
        $this->table('mailing_items')
            ->addColumn('mailing_id', 'integer')
            ->addColumn('member_id', 'integer')
            ->addColumn('status', 'integer')
            ->addColumn('tokenhash', 'string', ['limit' => 255])
            ->addColumn('confirmation', 'datetime')
            ->create();

        // Create meetings table
        $this->table('meetings')
            ->addColumn('meeting_date', 'datetime')
            ->addColumn('team_id', 'integer')
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addColumn('present', 'integer', ['default' => -1])
            ->addColumn('absent', 'integer', ['default' => -1])
            ->addColumn('excused', 'integer', ['default' => -1])
            ->addColumn('late', 'integer', ['default' => -1])
            ->addColumn('max_members', 'integer', ['default' => 0])
            ->addColumn('big_event', 'boolean', ['default' => false])
            ->addColumn('url', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('doodle', 'boolean', ['default' => false])
            ->addForeignKey('team_id', 'teams', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Create presences table
        $this->table('presences')
            ->addColumn('meeting_id', 'integer')
            ->addColumn('member_id', 'integer')
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addColumn('state', 'integer', ['default' => 1])
            ->addForeignKey('meeting_id', 'meetings', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Create registrations table
        $this->table('registrations')
            ->addColumn('signature_member', 'blob', ['null' => true])
            ->addColumn('signature_parent', 'blob', ['null' => true])
            ->addColumn('member_id', 'integer')
            ->addColumn('validation_id', 'integer', ['default' => 0])
            ->addColumn('year', 'integer', ['default' => 0])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Create sessions table
        $this->table('sessions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 40, 'collation' => 'ascii_bin'])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('data', 'blob', ['null' => true])
            ->addColumn('expires', 'integer', ['signed' => false, 'null' => true])
            ->create();

        // Create shop_items table
        $this->table('shop_items')
            ->addColumn('label', 'string', ['limit' => 255])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('category', 'integer', ['default' => 1])
            ->addColumn('active', 'boolean', ['default' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['active'])
            ->addIndex(['label'])
            ->addIndex(['category'])
            ->create();

        // Create member_orders table
        $this->table('member_orders')
            ->addColumn('shop_item_id', 'integer')
            ->addColumn('member_id', 'integer')
            ->addColumn('bill_id', 'integer', ['null' => true])
            ->addColumn('quantity', 'integer', ['default' => 1])
            ->addColumn('delivered', 'boolean', ['default' => false])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['shop_item_id'])
            ->addIndex(['member_id'])
            ->addIndex(['bill_id'])
            ->addIndex(['delivered'])
            ->addForeignKey('shop_item_id', 'shop_items', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('bill_id', 'bills', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        // Create members_special_fields table
        $this->table('members_special_fields')
            ->addColumn('special_field_id', 'integer')
            ->addColumn('value', 'string', ['limit' => 255])
            ->addColumn('member_id', 'integer')
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Create member_docs table
        $this->table('member_docs')
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addColumn('name', 'string', ['limit' => 20])
            ->addColumn('title', 'string', ['limit' => 100])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // Create member_field1s table
        $this->table('member_field1s')
            ->addColumn('member_id', 'integer')
            ->addColumn('facebook', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('problemes_medicaux', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact1_first_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact1_last_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact1_natel', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact1_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact2_first_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact2_last_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact2_natel', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact2_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('remarque', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('a_connu_le_club_de', 'string', ['limit' => 200, 'default' => ''])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('member_field1s')->drop()->save();
        $this->table('member_docs')->drop()->save();
        $this->table('members_special_fields')->drop()->save();
        $this->table('member_orders')->drop()->save();
        $this->table('shop_items')->drop()->save();
        $this->table('sessions')->drop()->save();
        $this->table('registrations')->drop()->save();
        $this->table('presences')->drop()->save();
        $this->table('meetings')->drop()->save();
        $this->table('mailing_items')->drop()->save();
        $this->table('mailings')->drop()->save();
        $this->table('fields')->drop()->save();
        $this->table('field_types')->drop()->save();
        $this->table('data')->drop()->save();
        $this->table('contents')->drop()->save();
        $this->table('configurations')->drop()->save();
        $this->table('bill_templates')->drop()->save();
        $this->table('bills')->drop()->save();
        $this->table('users')->drop()->save();
        $this->table('teams_members')->drop()->save();
        $this->table('teams')->drop()->save();
        $this->table('members')->drop()->save();
        $this->table('roles')->drop()->save();
        $this->table('sites')->drop()->save();
    }
}
