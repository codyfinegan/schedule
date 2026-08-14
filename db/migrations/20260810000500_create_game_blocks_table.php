<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGameBlocksTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('game_blocks')
            ->addColumn('starts_at', 'timestamp', ['null' => false])
            ->addColumn('ends_at', 'timestamp', ['null' => false])
            ->addColumn('botc_app_code', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('recurring_schedule_id', 'integer', ['null' => true])
            ->addColumn('created_by_user_id', 'integer', ['null' => false])
            ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('recurring_schedule_id', 'recurring_schedules', 'id', ['delete' => 'SET_NULL'])
            ->addForeignKey('created_by_user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->addIndex('starts_at')
            ->addIndex('ends_at')
            // Prevents the recurring-materialization job from creating duplicate
            // blocks for the same schedule occurrence when it re-runs.
            ->addIndex(['recurring_schedule_id', 'starts_at'], ['unique' => true])
            ->create();
    }
}
