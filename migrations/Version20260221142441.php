<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221142441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, order_ref_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_52EA1F09E238517C (order_ref_id), INDEX IDX_52EA1F094584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09E238517C FOREIGN KEY (order_ref_id) REFERENCES `order` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE game CHANGE cover_image cover_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE guide CHANGE difficulty difficulty VARCHAR(255) DEFAULT NULL, CHANGE cover_image cover_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE guide_step CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE video_url video_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD payment_method VARCHAR(30) NOT NULL, ADD payment_status VARCHAR(30) NOT NULL, CHANGE customer_first_name customer_first_name VARCHAR(100) DEFAULT NULL, CHANGE customer_last_name customer_last_name VARCHAR(100) DEFAULT NULL, CHANGE customer_email customer_email VARCHAR(180) DEFAULT NULL, CHANGE customer_phone customer_phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE product CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE category category VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE team CHANGE logo logo VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE tournament CHANGE prize prize VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE phone phone VARCHAR(20) DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE roles roles JSON NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09E238517C');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('ALTER TABLE game CHANGE cover_image cover_image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE guide CHANGE difficulty difficulty VARCHAR(255) DEFAULT \'NULL\', CHANGE cover_image cover_image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE guide_step CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE video_url video_url VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE `order` DROP payment_method, DROP payment_status, CHANGE customer_first_name customer_first_name VARCHAR(100) DEFAULT \'NULL\', CHANGE customer_last_name customer_last_name VARCHAR(100) DEFAULT \'NULL\', CHANGE customer_email customer_email VARCHAR(180) DEFAULT \'NULL\', CHANGE customer_phone customer_phone VARCHAR(20) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE product CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE category category VARCHAR(100) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE team CHANGE logo logo VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE tournament CHANGE prize prize VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user CHANGE phone phone VARCHAR(20) DEFAULT \'NULL\', CHANGE address address VARCHAR(255) DEFAULT \'NULL\', CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE created_at created_at DATETIME DEFAULT \'NULL\'');
    }
}
