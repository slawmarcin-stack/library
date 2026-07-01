<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701140139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create baseline library schema: books, readers, loans';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE books (id VARCHAR(36) NOT NULL, serial_number VARCHAR(6) NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4A1B2A92D948EE2 ON books (serial_number)');

        $this->addSql('CREATE TABLE loans (id VARCHAR(36) NOT NULL, borrowed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, returned_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, book_id VARCHAR(36) NOT NULL, reader_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_82C24DBC16A2B381 ON loans (book_id)');
        $this->addSql('CREATE INDEX IDX_82C24DBC1717D737 ON loans (reader_id)');

        $this->addSql('CREATE TABLE readers (id VARCHAR(36) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, library_card_number VARCHAR(6) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_34AD8C05E7927C74 ON readers (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_34AD8C05BDF86504 ON readers (library_card_number)');

        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC16A2B381 FOREIGN KEY (book_id) REFERENCES books (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC1717D737 FOREIGN KEY (reader_id) REFERENCES readers (id) NOT DEFERRABLE');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE loans DROP CONSTRAINT FK_82C24DBC16A2B381');
        $this->addSql('ALTER TABLE loans DROP CONSTRAINT FK_82C24DBC1717D737');
        $this->addSql('DROP TABLE loans');
        $this->addSql('DROP TABLE readers');
        $this->addSql('DROP TABLE books');

    }
}
