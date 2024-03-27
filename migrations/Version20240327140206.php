<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240327140206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE album (id INT AUTO_INCREMENT NOT NULL, id_album INT NOT NULL, name VARCHAR(90) NOT NULL, category VARCHAR(50) NOT NULL, cover VARCHAR(135) NOT NULL, year INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE artist (id INT AUTO_INCREMENT NOT NULL, user_id_user_id INT NOT NULL, fullname VARCHAR(90) NOT NULL, label VARCHAR(90) NOT NULL, description LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_1599687DE94BC09 (user_id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist (id INT AUTO_INCREMENT NOT NULL, id_playlist INT NOT NULL, title VARCHAR(50) NOT NULL, public TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song (id INT AUTO_INCREMENT NOT NULL, album_id_album_id INT NOT NULL, id_song VARCHAR(50) NOT NULL, title VARCHAR(45) NOT NULL, url VARCHAR(145) NOT NULL, cover VARCHAR(125) NOT NULL, visibility TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_33EDEEA1AE0930E (album_id_album_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song_artist (song_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_722870DA0BDB2F3 (song_id), INDEX IDX_722870DB7970CF8 (artist_id), PRIMARY KEY(song_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(80) NOT NULL, name VARCHAR(55) NOT NULL, encrypte VARCHAR(90) NOT NULL, tel VARCHAR(15) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, id_user VARCHAR(55) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE artist ADD CONSTRAINT FK_1599687DE94BC09 FOREIGN KEY (user_id_user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT FK_33EDEEA1AE0930E FOREIGN KEY (album_id_album_id) REFERENCES album (id)');
        $this->addSql('ALTER TABLE song_artist ADD CONSTRAINT FK_722870DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_artist ADD CONSTRAINT FK_722870DB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist DROP FOREIGN KEY FK_1599687DE94BC09');
        $this->addSql('ALTER TABLE song DROP FOREIGN KEY FK_33EDEEA1AE0930E');
        $this->addSql('ALTER TABLE song_artist DROP FOREIGN KEY FK_722870DA0BDB2F3');
        $this->addSql('ALTER TABLE song_artist DROP FOREIGN KEY FK_722870DB7970CF8');
        $this->addSql('DROP TABLE album');
        $this->addSql('DROP TABLE artist');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE song');
        $this->addSql('DROP TABLE song_artist');
        $this->addSql('DROP TABLE `user`');
    }
}
