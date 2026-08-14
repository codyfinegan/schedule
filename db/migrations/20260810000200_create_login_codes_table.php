<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLoginCodesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('login_codes')
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('code_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('expires_at', 'timestamp', ['null' => false])
            ->addColumn('consumed_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->addIndex('user_id')
            ->create();
    }
}
