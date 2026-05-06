<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506114709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE convocation (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(30) NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, football_match_id INT NOT NULL, player_id INT NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_C03B3F5FE1DA134D (football_match_id), INDEX IDX_C03B3F5F99E6F5DF (player_id), INDEX IDX_C03B3F5FB03A8386 (created_by_id), UNIQUE INDEX UNIQ_CONVOCATION_MATCH_PLAYER (football_match_id, player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE convocation ADD CONSTRAINT FK_C03B3F5FE1DA134D FOREIGN KEY (football_match_id) REFERENCES football_match (id)');
        $this->addSql('ALTER TABLE convocation ADD CONSTRAINT FK_C03B3F5F99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE convocation ADD CONSTRAINT FK_C03B3F5FB03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE convocation DROP FOREIGN KEY FK_C03B3F5FE1DA134D');
        $this->addSql('ALTER TABLE convocation DROP FOREIGN KEY FK_C03B3F5F99E6F5DF');
        $this->addSql('ALTER TABLE convocation DROP FOREIGN KEY FK_C03B3F5FB03A8386');
        $this->addSql('DROP TABLE convocation');
    }
}
