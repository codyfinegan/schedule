<?php declare(strict_types=1);

namespace Schedule\Contracts;

interface IEntity {

    /**
     * @param array $row
     * @return IEntity
     */
    public static function fromRow(array $row): IEntity;

}
