<?php

namespace craftfm\iconify\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable('{{%iconify_icons}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(), // icon name
            'set' => $this->string()->notNull(),  // icon set (e.g. mdi-light)
            'filename' => $this->string(),
            'body' => $this->text(),
            'prefixId' => $this->integer()->null(),
            'suffixId' => $this->integer()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%iconify_affixes}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'slug' => $this->string()->notNull(),
            'iconSet' => $this->string()->notNull(),
            'type' => $this->enum('type', ['prefix', 'suffix'])->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(
            null,
            '{{%iconify_icons}}',
            ['name', 'set'],
            true
        );
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%iconify_icons}}');
        $this->dropTableIfExists('{{%iconify_affixes}}');
        return true;
    }
}
