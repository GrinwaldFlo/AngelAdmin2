<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class ClearPasswordResetTokens extends AbstractMigration
{
    /**
     * The old reset links were easy to guess (sha1 of the username and a number up to 100) and never expired:
     * they are removed, a new link has to be asked
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute("UPDATE users SET tokenhash = ''");
    }

    public function down(): void
    {
    }
}
