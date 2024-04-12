<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240412091303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album ADD actif INT NOT NULL');
        $this->addSql('ALTER TABLE artist ADD actif INT NOT NULL');
        $this->addSql('ALTER TABLE song ADD actif INT NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE actif actif INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album DROP actif');
        $this->addSql('ALTER TABLE artist DROP actif');
        $this->addSql('ALTER TABLE song DROP actif');
        $this->addSql('ALTER TABLE user CHANGE actif actif TINYINT(1) NOT NULL');
    }
}
