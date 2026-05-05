<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505074506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE football_match ADD match_type VARCHAR(30) NOT NULL, ADD first_match_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE football_match ADD CONSTRAINT FK_8CE33ACE9EA69E8D FOREIGN KEY (first_match_id) REFERENCES football_match (id)');
        $this->addSql('CREATE INDEX IDX_8CE33ACE9EA69E8D ON football_match (first_match_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE football_match DROP FOREIGN KEY FK_8CE33ACE9EA69E8D');
        $this->addSql('DROP INDEX IDX_8CE33ACE9EA69E8D ON football_match');
        $this->addSql('ALTER TABLE football_match DROP match_type, DROP first_match_id');
    }
}
