<?php

namespace steroids\types;

use steroids\base\Type;
use yii\db\Schema;

class PrimaryKeyType extends Type
{
    /**
     * @inheritdoc
     */
    public function prepareFieldProps($modelClass, $attribute, &$props, &$import = null)
    {
        $props = array_merge(
            [
                'component' => 'InputField',
                'attribute' => $attribute,
                'type' => 'hidden',
            ],
            $props
        );
    }

    /**
     * @inheritdoc
     */
    public function giiDbType($metaItem)
    {
        return Schema::TYPE_PK;
    }

    /**
     * @inheritdoc
     */
    public function giiRules($metaItem, &$useClasses = [])
    {
        return false;
    }
}