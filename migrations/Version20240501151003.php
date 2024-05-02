<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240501151003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE featuring DROP FOREIGN KEY FK_73A30F0CDF749768');
        $this->addSql('ALTER TABLE featuring DROP FOREIGN KEY FK_73A30F0CFF0DA6A1');
        $this->addSql('DROP TABLE featuring');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE featuring (id INT AUTO_INCREMENT NOT NULL, albumfeat_id INT NOT NULL, artistfeat_id INT NOT NULL, INDEX IDX_73A30F0CDF749768 (albumfeat_id), INDEX IDX_73A30F0CFF0DA6A1 (artistfeat_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE featuring ADD CONSTRAINT FK_73A30F0CDF749768 FOREIGN KEY (albumfeat_id) REFERENCES album (id)');
        $this->addSql('ALTER TABLE featuring ADD CONSTRAINT FK_73A30F0CFF0DA6A1 FOREIGN KEY (artistfeat_id) REFERENCES artist (id)');
    }
}
