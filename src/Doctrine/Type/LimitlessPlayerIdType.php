<?php

namespace App\Doctrine\Type;

use App\ValueObject\LimitlessPlayerId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class LimitlessPlayerIdType extends Type
{
    public const NAME = 'limitless_player_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?LimitlessPlayerId
    {
        return null === $value ? null : LimitlessPlayerId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof LimitlessPlayerId ? (string) $value : null;
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
