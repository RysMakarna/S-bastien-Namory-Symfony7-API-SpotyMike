<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240501205129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album DROP FOREIGN KEY FK_39986E43FF0DA6A1');
        $this->addSql('DROP INDEX IDX_39986E43FF0DA6A1 ON album');
        $this->addSql('ALTER TABLE album DROP artistfeat_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album ADD artistfeat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E43FF0DA6A1 FOREIGN KEY (artistfeat_id) REFERENCES artist (id)');
        $this->addSql('CREATE INDEX IDX_39986E43FF0DA6A1 ON album (artistfeat_id)');
    }
}
