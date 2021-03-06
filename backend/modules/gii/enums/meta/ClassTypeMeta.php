<?php

namespace steroids\modules\gii\enums\meta;

use Yii;
use steroids\base\Enum;

abstract class ClassTypeMeta extends Enum
{
    const MODEL = 'model';
    const FORM = 'form';
    const ENUM = 'enum';
    const CRUD = 'crud';
    const WIDGET = 'widget';

    public static function getLabels()
    {
        return [
            self::MODEL => Yii::t('steroids', 'Model ActiveRecord'),
            self::FORM => Yii::t('steroids', 'Model Form'),
            self::ENUM => Yii::t('steroids', 'Enum'),
            self::CRUD => Yii::t('steroids', 'Crud Controller'),
            self::WIDGET => Yii::t('steroids', 'Widget')
        ];
    }

    public static function getDirData()
    {
        return [
            self::MODEL => 'models',
            self::FORM => 'forms',
            self::ENUM => 'enums',
            self::CRUD => 'controllers',
            self::WIDGET => 'widgets/*',
        ];
    }

    public static function getDir($id)
    {
        return static::getDataValue('dir', $id);
    }
}
