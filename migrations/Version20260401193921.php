<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401193921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE player (id UUID NOT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, country VARCHAR(2) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A659F75D7B0 ON player (external_id)');
        $this->addSql('CREATE INDEX idx_player_name ON player (name)');
        $this->addSql('CREATE TABLE player_tournament_result (id UUID NOT NULL, "placing" INT DEFAULT NULL, wins INT NOT NULL, losses INT NOT NULL, ties INT NOT NULL, deck_name VARCHAR(255) DEFAULT NULL, deck_id VARCHAR(255) DEFAULT NULL, dropped BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, player_id UUID NOT NULL, tournament_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_10FCA20D99E6F5DF ON player_tournament_result (player_id)');
        $this->addSql('CREATE INDEX IDX_10FCA20D33D1A3E7 ON player_tournament_result (tournament_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_player_tournament ON player_tournament_result (player_id, tournament_id)');
        $this->addSql('CREATE TABLE tournament (id UUID NOT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, game VARCHAR(50) NOT NULL, format VARCHAR(50) DEFAULT NULL, date DATE NOT NULL, player_count INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BD5FB8D99F75D7B0 ON tournament (external_id)');
        $this->addSql('CREATE INDEX idx_tournament_date ON tournament (date)');
        $this->addSql('CREATE INDEX idx_tournament_game ON tournament (game)');
        $this->addSql('ALTER TABLE player_tournament_result ADD CONSTRAINT FK_10FCA20D99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE player_tournament_result ADD CONSTRAINT FK_10FCA20D33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player_tournament_result DROP CONSTRAINT FK_10FCA20D99E6F5DF');
        $this->addSql('ALTER TABLE player_tournament_result DROP CONSTRAINT FK_10FCA20D33D1A3E7');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE player_tournament_result');
        $this->addSql('DROP TABLE tournament');
    }
}
