<?php

namespace App\Doctrine\Type;

use App\ValueObject\LimitlessTournamentId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class LimitlessTournamentIdType extends Type
{
    public const NAME = 'limitless_tournament_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?LimitlessTournamentId
    {
        return null === $value ? null : LimitlessTournamentId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof LimitlessTournamentId ? (string) $value : null;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
