<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401194715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename placing column to final_placing to avoid PostgreSQL reserved word conflict';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_tournament_result RENAME COLUMN "placing" TO final_placing');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player_tournament_result RENAME COLUMN final_placing TO "placing"');
    }
}
