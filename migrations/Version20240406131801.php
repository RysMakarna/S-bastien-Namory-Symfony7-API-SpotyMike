<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240406131801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist_has_label (id INT AUTO_INCREMENT NOT NULL, id_label_id INT NOT NULL, id_artist_id INT NOT NULL, INDEX IDX_E9FA2BDE6362C3AC (id_label_id), INDEX IDX_E9FA2BDE37A2B0DF (id_artist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE artist_has_label ADD CONSTRAINT FK_E9FA2BDE6362C3AC FOREIGN KEY (id_label_id) REFERENCES label (id)');
        $this->addSql('ALTER TABLE artist_has_label ADD CONSTRAINT FK_E9FA2BDE37A2B0DF FOREIGN KEY (id_artist_id) REFERENCES artist (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist_has_label DROP FOREIGN KEY FK_E9FA2BDE6362C3AC');
        $this->addSql('ALTER TABLE artist_has_label DROP FOREIGN KEY FK_E9FA2BDE37A2B0DF');
        $this->addSql('DROP TABLE artist_has_label');
    }
}
