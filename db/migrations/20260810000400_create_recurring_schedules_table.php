<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRecurringSchedulesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('recurring_schedules')
            ->addColumn('day_of_week', 'integer', ['null' => false])
            ->addColumn('start_time', 'string', ['limit' => 8, 'null' => false])
            ->addColumn('duration_minutes', 'integer', ['null' => false])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('created_by_user_id', 'integer', ['null' => false])
            ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('created_by_user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
