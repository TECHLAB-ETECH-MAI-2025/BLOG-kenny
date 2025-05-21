<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250521150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs updatedAt et updatedBy à l\'entité Article';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_23A0E66896DBBDE ON article (updated_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66896DBBDE');
        $this->addSql('DROP INDEX IDX_23A0E66896DBBDE ON article');
        $this->addSql('ALTER TABLE article DROP updated_at, DROP updated_by_id');
    }
}
