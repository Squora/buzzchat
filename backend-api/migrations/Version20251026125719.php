<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251026125719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create message-related tables with unique index names and proper rollback';
    }

    public function up(Schema $schema): void
    {
        // Tables
        $this->addSql('CREATE TABLE message_attachments (
            id SERIAL NOT NULL,
            message_id INT NOT NULL,
            file_url VARCHAR(500) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_size BIGINT NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            thumbnail_url VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_MESSAGE_ATTACHMENTS_MESSAGE_ID ON message_attachments (message_id)');

        $this->addSql('CREATE TABLE message_reactions (
            id SERIAL NOT NULL,
            message_id INT NOT NULL,
            user_id INT NOT NULL,
            emoji VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_MESSAGE_REACTIONS_MESSAGE_ID ON message_reactions (message_id)');
        $this->addSql('CREATE INDEX IDX_MESSAGE_REACTIONS_USER_ID ON message_reactions (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MESSAGE_REACTIONS_MESSAGE_USER_EMOJI ON message_reactions (message_id, user_id, emoji)');

        $this->addSql('CREATE TABLE message_read_receipts (
            id SERIAL NOT NULL,
            message_id INT NOT NULL,
            user_id INT NOT NULL,
            read_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_MESSAGE_READ_RECEIPTS_MESSAGE_ID ON message_read_receipts (message_id)');
        $this->addSql('CREATE INDEX IDX_MESSAGE_READ_RECEIPTS_USER_ID ON message_read_receipts (user_id)');
        $this->addSql('CREATE INDEX IDX_MESSAGE_READ_RECEIPTS_READ_AT ON message_read_receipts (read_at)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MESSAGE_READ_RECEIPTS_MESSAGE_USER ON message_read_receipts (message_id, user_id)');

        $this->addSql('CREATE TABLE messages (
            id SERIAL NOT NULL,
            chat_id INT NOT NULL,
            user_id INT NOT NULL,
            reply_to_id INT DEFAULT NULL,
            type VARCHAR(20) NOT NULL,
            text TEXT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            edited_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            mentions JSON DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_MESSAGES_CHAT_ID ON messages (chat_id)');
        $this->addSql('CREATE INDEX IDX_MESSAGES_REPLY_TO_ID ON messages (reply_to_id)');
        $this->addSql('CREATE INDEX IDX_MESSAGES_CHAT_CREATED_AT ON messages (chat_id, created_at)');
        $this->addSql('CREATE INDEX IDX_MESSAGES_USER_ID ON messages (user_id)');
        $this->addSql('CREATE INDEX IDX_MESSAGES_TYPE ON messages (type)');
        $this->addSql('CREATE INDEX IDX_MESSAGES_DELETED_AT ON messages (deleted_at)');

        // Foreign keys
        $this->addSql('ALTER TABLE message_attachments ADD CONSTRAINT FK_MSG_ATTACHMENTS_MESSAGE_ID FOREIGN KEY (message_id) REFERENCES messages (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_reactions ADD CONSTRAINT FK_MSG_REACTIONS_MESSAGE_ID FOREIGN KEY (message_id) REFERENCES messages (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_reactions ADD CONSTRAINT FK_MSG_REACTIONS_USER_ID FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_read_receipts ADD CONSTRAINT FK_MSG_READ_RECEIPTS_MESSAGE_ID FOREIGN KEY (message_id) REFERENCES messages (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_read_receipts ADD CONSTRAINT FK_MSG_READ_RECEIPTS_USER_ID FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_MESSAGES_CHAT_ID FOREIGN KEY (chat_id) REFERENCES chats (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_MESSAGES_USER_ID FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_MESSAGES_REPLY_TO_ID FOREIGN KEY (reply_to_id) REFERENCES messages (id) ON DELETE SET NULL');

        // Chats / settings
        $this->addSql('CREATE INDEX IDX_CHATS_TYPE ON chats (type)');
        $this->addSql('ALTER TABLE departments ALTER is_active DROP DEFAULT');
        $this->addSql('ALTER TABLE user_settings ALTER theme DROP DEFAULT');
        $this->addSql('ALTER TABLE user_settings ALTER notifications_enabled DROP DEFAULT');
        $this->addSql('ALTER TABLE user_settings ALTER sound_enabled DROP DEFAULT');
        $this->addSql('ALTER TABLE user_settings ALTER email_notifications DROP DEFAULT');
        $this->addSql('ALTER TABLE user_settings ALTER show_online_status DROP DEFAULT');
        $this->addSql('ALTER TABLE user_settings ALTER language DROP DEFAULT');
        $this->addSql('ALTER INDEX uniq_user_settings_user_id RENAME TO UNIQ_USER_SETTINGS_USER_ID_FIXED');
        $this->addSql('ALTER TABLE users ALTER online_status DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // Drop constraints first
        $this->addSql('ALTER TABLE message_attachments DROP CONSTRAINT FK_MSG_ATTACHMENTS_MESSAGE_ID');
        $this->addSql('ALTER TABLE message_reactions DROP CONSTRAINT FK_MSG_REACTIONS_MESSAGE_ID');
        $this->addSql('ALTER TABLE message_reactions DROP CONSTRAINT FK_MSG_REACTIONS_USER_ID');
        $this->addSql('ALTER TABLE message_read_receipts DROP CONSTRAINT FK_MSG_READ_RECEIPTS_MESSAGE_ID');
        $this->addSql('ALTER TABLE message_read_receipts DROP CONSTRAINT FK_MSG_READ_RECEIPTS_USER_ID');
        $this->addSql('ALTER TABLE messages DROP CONSTRAINT FK_MESSAGES_CHAT_ID');
        $this->addSql('ALTER TABLE messages DROP CONSTRAINT FK_MESSAGES_USER_ID');
        $this->addSql('ALTER TABLE messages DROP CONSTRAINT FK_MESSAGES_REPLY_TO_ID');

        // Drop indexes explicitly (safe for PostgreSQL)
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGE_ATTACHMENTS_MESSAGE_ID');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGE_REACTIONS_MESSAGE_ID');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGE_REACTIONS_USER_ID');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_MESSAGE_REACTIONS_MESSAGE_USER_EMOJI');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGE_READ_RECEIPTS_MESSAGE_ID');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGE_READ_RECEIPTS_USER_ID');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGE_READ_RECEIPTS_READ_AT');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_MESSAGE_READ_RECEIPTS_MESSAGE_USER');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGES_CHAT_ID');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGES_REPLY_TO_ID');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGES_CHAT_CREATED_AT');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGES_USER_ID');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGES_TYPE');
        $this->addSql('DROP INDEX IF EXISTS IDX_MESSAGES_DELETED_AT');
        $this->addSql('DROP INDEX IF EXISTS IDX_CHATS_TYPE');

        // Drop tables
        $this->addSql('DROP TABLE IF EXISTS message_attachments');
        $this->addSql('DROP TABLE IF EXISTS message_reactions');
        $this->addSql('DROP TABLE IF EXISTS message_read_receipts');
        $this->addSql('DROP TABLE IF EXISTS messages');

        // Restore defaults
        $this->addSql("ALTER TABLE departments ALTER is_active SET DEFAULT true");
        $this->addSql("ALTER TABLE users ALTER online_status SET DEFAULT 'offline'");
        $this->addSql("ALTER TABLE user_settings ALTER theme SET DEFAULT 'auto'");
        $this->addSql("ALTER TABLE user_settings ALTER notifications_enabled SET DEFAULT true");
        $this->addSql("ALTER TABLE user_settings ALTER sound_enabled SET DEFAULT true");
        $this->addSql("ALTER TABLE user_settings ALTER email_notifications SET DEFAULT true");
        $this->addSql("ALTER TABLE user_settings ALTER show_online_status SET DEFAULT true");
        $this->addSql("ALTER TABLE user_settings ALTER language SET DEFAULT 'ru'");
        $this->addSql("ALTER INDEX UNIQ_USER_SETTINGS_USER_ID_FIXED RENAME TO uniq_user_settings_user_id");
    }
}