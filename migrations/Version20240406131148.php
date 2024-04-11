<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240406131148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artis_has_label_artist DROP FOREIGN KEY FK_3E87B52A1EF25E2');
        $this->addSql('ALTER TABLE artis_has_label_artist DROP FOREIGN KEY FK_3E87B52AB7970CF8');
        $this->addSql('ALTER TABLE artis_has_label_label DROP FOREIGN KEY FK_6582204E33B92F39');
        $this->addSql('ALTER TABLE artis_has_label_label DROP FOREIGN KEY FK_6582204E1EF25E2');
        $this->addSql('DROP TABLE artis_has_label');
        $this->addSql('DROP TABLE artis_has_label_artist');
        $this->addSql('DROP TABLE artis_has_label_label');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artis_has_label (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE artis_has_label_artist (artis_has_label_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_3E87B52A1EF25E2 (artis_has_label_id), INDEX IDX_3E87B52AB7970CF8 (artist_id), PRIMARY KEY(artis_has_label_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE artis_has_label_label (artis_has_label_id INT NOT NULL, label_id INT NOT NULL, INDEX IDX_6582204E33B92F39 (label_id), INDEX IDX_6582204E1EF25E2 (artis_has_label_id), PRIMARY KEY(artis_has_label_id, label_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE artis_has_label_artist ADD CONSTRAINT FK_3E87B52A1EF25E2 FOREIGN KEY (artis_has_label_id) REFERENCES artis_has_label (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artis_has_label_artist ADD CONSTRAINT FK_3E87B52AB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artis_has_label_label ADD CONSTRAINT FK_6582204E33B92F39 FOREIGN KEY (label_id) REFERENCES label (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artis_has_label_label ADD CONSTRAINT FK_6582204E1EF25E2 FOREIGN KEY (artis_has_label_id) REFERENCES artis_has_label (id) ON DELETE CASCADE');
    }
}
