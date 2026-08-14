<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('display_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('is_admin', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('is_approved', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('email', ['unique' => true])
            ->create();
    }
}
