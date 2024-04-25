<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240418143655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist ADD create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD update_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE artist_has_label ADD added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD quitted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP reset_password, DROP date_rest');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist DROP create_at, DROP update_at');
        $this->addSql('ALTER TABLE artist_has_label DROP added_at, DROP quitted_at');
        $this->addSql('ALTER TABLE user ADD reset_password INT NOT NULL, ADD date_rest DATETIME DEFAULT NULL');
    }
}
