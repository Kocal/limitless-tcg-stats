<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401204208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tournament details (organizer, structure, isOnline) and deck icons to player results';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player_tournament_result ADD deck_icons JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE tournament ADD organizer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tournament ADD organizer_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tournament ADD organizer_logo VARCHAR(512) DEFAULT NULL');
        $this->addSql('ALTER TABLE tournament ADD is_online BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE tournament ADD structure JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player_tournament_result DROP deck_icons');
        $this->addSql('ALTER TABLE tournament DROP organizer_id');
        $this->addSql('ALTER TABLE tournament DROP organizer_name');
        $this->addSql('ALTER TABLE tournament DROP organizer_logo');
        $this->addSql('ALTER TABLE tournament DROP is_online');
        $this->addSql('ALTER TABLE tournament DROP structure');
    }
}
