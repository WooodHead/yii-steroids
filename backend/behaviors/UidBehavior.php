<?php

namespace steroids\behaviors;

use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;

class UidBehavior extends AttributeBehavior
{
    public $attribute = 'uid';

    public static function generate()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->attributes = [
            BaseActiveRecord::EVENT_INIT => $this->attribute,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        return static::generate();
    }

}
