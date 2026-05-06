<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506134431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE training_session (id INT AUTO_INCREMENT NOT NULL, training_date DATE NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, location VARCHAR(255) NOT NULL, theme VARCHAR(255) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, team_id INT NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_D7A45DA296CD8AE (team_id), INDEX IDX_D7A45DAB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE training_session ADD CONSTRAINT FK_D7A45DA296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE training_session ADD CONSTRAINT FK_D7A45DAB03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training_session DROP FOREIGN KEY FK_D7A45DA296CD8AE');
        $this->addSql('ALTER TABLE training_session DROP FOREIGN KEY FK_D7A45DAB03A8386');
        $this->addSql('DROP TABLE training_session');
    }
}
