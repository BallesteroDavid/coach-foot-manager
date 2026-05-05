<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504130549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE football_match (id INT AUTO_INCREMENT NOT NULL, match_date DATE NOT NULL, start_time TIME NOT NULL, location VARCHAR(255) NOT NULL, location_type VARCHAR(30) NOT NULL, opponent VARCHAR(255) NOT NULL, competition VARCHAR(100) DEFAULT NULL, home_score INT DEFAULT NULL, away_score INT DEFAULT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, team_id INT NOT NULL, INDEX IDX_8CE33ACE296CD8AE (team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE football_match ADD CONSTRAINT FK_8CE33ACE296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE football_match DROP FOREIGN KEY FK_8CE33ACE296CD8AE');
        $this->addSql('DROP TABLE football_match');
    }
}
