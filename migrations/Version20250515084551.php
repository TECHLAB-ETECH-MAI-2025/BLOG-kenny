<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250515084551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category MODIFY id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category DROP FOREIGN KEY FK_53A4EDAA12469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category DROP FOREIGN KEY FK_53A4EDAA7294869C
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX `primary` ON article_category
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category DROP id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category ADD CONSTRAINT FK_53A4EDAA12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category ADD CONSTRAINT FK_53A4EDAA7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category ADD PRIMARY KEY (article_id, category_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category DROP FOREIGN KEY FK_53A4EDAA7294869C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category DROP FOREIGN KEY FK_53A4EDAA12469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category ADD id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category ADD CONSTRAINT FK_53A4EDAA7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_category ADD CONSTRAINT FK_53A4EDAA12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
    }
}
