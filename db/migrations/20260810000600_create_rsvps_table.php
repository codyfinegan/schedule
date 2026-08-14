<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRsvpsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('rsvps')
            ->addColumn('game_block_id', 'integer', ['null' => false])
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('game_block_id', 'game_blocks', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->addIndex(['game_block_id', 'user_id'], ['unique' => true])
            ->create();
    }
}
