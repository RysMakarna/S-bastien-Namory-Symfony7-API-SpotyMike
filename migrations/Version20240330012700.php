<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240330012700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album ADD create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE artist ADD create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD lastname VARCHAR(90) NOT NULL, ADD sexe VARCHAR(20) NOT NULL, ADD birthday DATE NOT NULL, CHANGE fullname firstname VARCHAR(90) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album DROP create_at');
        $this->addSql('ALTER TABLE artist ADD fullname VARCHAR(90) NOT NULL, DROP create_at, DROP firstname, DROP lastname, DROP sexe, DROP birthday');
    }
}
