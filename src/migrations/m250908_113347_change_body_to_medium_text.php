<?php

namespace craftfm\iconify\migrations;

use Craft;
use craft\db\Migration;

/**
 * m250908_113347_change_body_to_medium_text migration.
 */
class m250908_113347_change_body_to_medium_text extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Place migration code here...
        $this->alterColumn('{{%iconify_icons}}', 'body', $this->mediumText());
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->alterColumn('{{%iconify_icons}}', 'body', $this->text());
        return false;
    }
}
