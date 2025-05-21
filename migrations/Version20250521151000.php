<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250521151000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs updatedAt et updatedBy aux entités Comment et Category';
    }

    public function up(Schema $schema): void
    {
        // Comment
        $this->addSql('ALTER TABLE comment ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9474526C896DBBDE ON comment (updated_by_id)');

        // Category
        $this->addSql('ALTER TABLE category ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_64C19C1896DBBDE ON category (updated_by_id)');
    }

    public function down(Schema $schema): void
    {
        // Comment
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C896DBBDE');
        $this->addSql('DROP INDEX IDX_9474526C896DBBDE ON comment');
        $this->addSql('ALTER TABLE comment DROP updated_at, DROP updated_by_id');

        // Category
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1896DBBDE');
        $this->addSql('DROP INDEX IDX_64C19C1896DBBDE ON category');
        $this->addSql('ALTER TABLE category DROP updated_at, DROP updated_by_id');
    }
}
