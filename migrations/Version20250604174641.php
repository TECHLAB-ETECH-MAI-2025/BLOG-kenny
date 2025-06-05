<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250604174641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE category ADD created_at DATETIME NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        
        // Mise à jour des enregistrements existants avec la date actuelle
        $this->addSql(<<<'SQL'
            UPDATE category SET created_at = NOW() WHERE created_at IS NULL
        SQL);
        
        // Rendre la colonne NOT NULL après la mise à jour
        $this->addSql(<<<'SQL'
            ALTER TABLE category MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE category DROP created_at
        SQL);
    }
}
