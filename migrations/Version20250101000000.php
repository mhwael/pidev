<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add firstName, lastName, phone, address, createdAt to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD first_name VARCHAR(100) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE user ADD last_name VARCHAR(100) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE user ADD phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP first_name');
        $this->addSql('ALTER TABLE user DROP last_name');
        $this->addSql('ALTER TABLE user DROP phone');
        $this->addSql('ALTER TABLE user DROP address');
        $this->addSql('ALTER TABLE user DROP created_at');
    }
}
