<?php

namespace steroids\modules\gii\enums\meta;

use Yii;
use steroids\base\Enum;

abstract class RelationTypeMeta extends Enum
{
    const HAS_ONE = 'has_one';
    const HAS_MANY = 'has_many';
    const MANY_MANY = 'many_many';

    public static function getLabels()
    {
        return [
            self::HAS_ONE => Yii::t('app', 'Has One'),
            self::HAS_MANY => Yii::t('app', 'Has Many'),
            self::MANY_MANY => Yii::t('app', 'Many-Many')
        ];
    }
}