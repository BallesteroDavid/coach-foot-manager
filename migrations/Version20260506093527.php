<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506093527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user_team (app_user_id INT NOT NULL, team_id INT NOT NULL, INDEX IDX_160EB5E34A3353D8 (app_user_id), INDEX IDX_160EB5E3296CD8AE (team_id), PRIMARY KEY (app_user_id, team_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE app_user_team ADD CONSTRAINT FK_160EB5E34A3353D8 FOREIGN KEY (app_user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE app_user_team ADD CONSTRAINT FK_160EB5E3296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user_team DROP FOREIGN KEY FK_160EB5E34A3353D8');
        $this->addSql('ALTER TABLE app_user_team DROP FOREIGN KEY FK_160EB5E3296CD8AE');
        $this->addSql('DROP TABLE app_user_team');
    }
}
