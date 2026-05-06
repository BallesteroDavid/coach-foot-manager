<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506142709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE training_attendance (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(30) NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, training_session_id INT NOT NULL, player_id INT NOT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_D75DB7F7DB8156B9 (training_session_id), INDEX IDX_D75DB7F799E6F5DF (player_id), INDEX IDX_D75DB7F7896DBBDE (updated_by_id), UNIQUE INDEX UNIQ_TRAINING_ATTENDANCE_SESSION_PLAYER (training_session_id, player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE training_attendance ADD CONSTRAINT FK_D75DB7F7DB8156B9 FOREIGN KEY (training_session_id) REFERENCES training_session (id)');
        $this->addSql('ALTER TABLE training_attendance ADD CONSTRAINT FK_D75DB7F799E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE training_attendance ADD CONSTRAINT FK_D75DB7F7896DBBDE FOREIGN KEY (updated_by_id) REFERENCES app_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training_attendance DROP FOREIGN KEY FK_D75DB7F7DB8156B9');
        $this->addSql('ALTER TABLE training_attendance DROP FOREIGN KEY FK_D75DB7F799E6F5DF');
        $this->addSql('ALTER TABLE training_attendance DROP FOREIGN KEY FK_D75DB7F7896DBBDE');
        $this->addSql('DROP TABLE training_attendance');
    }
}
