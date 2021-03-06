<?php

namespace steroids\modules\gii\forms;

use steroids\base\Model;
use steroids\modules\gii\enums\ClassType;
use steroids\modules\gii\enums\MigrateMode;
use steroids\modules\gii\forms\meta\ModelEntityMeta;
use steroids\modules\gii\GiiModule;
use steroids\modules\gii\helpers\GiiHelper;
use steroids\modules\gii\models\MigrationMethods;
use steroids\modules\gii\models\ValueExpression;
use steroids\modules\gii\traits\EntityTrait;
use steroids\types\RelationType;
use steroids\validators\RecordExistValidator;
use yii\helpers\ArrayHelper;

/**
 * @property-read ModelAttributeEntity[] $attributeItems
 * @property-read ModelAttributeEntity[] $publicAttributeItems
 * @property-read ModelRelationEntity[] $relationItems
 * @property-read ModelRelationEntity[] $publicRelationItems
 */
class ModelEntity extends ModelEntityMeta implements IEntity
{
    use EntityTrait;

    /**
     * @return static[]
     * @throws \ReflectionException
     */
    public static function findAll()
    {
        $items = [];
        foreach (GiiHelper::findClasses(ClassType::MODEL) as $item) {
            $className = GiiHelper::getClassName(ClassType::MODEL, $item['moduleId'], $item['name']);
            $items[] = static::findOne($className);
        }

        ArrayHelper::multisort($items, 'name');
        return $items;
    }

    public static function findOne($className)
    {
        $entity = new static();
        $entity->className = $className;
        $entity->attributes = GiiHelper::parseClassName($className);

        $entity->validate('migrateMode');

        /** @var Model $className */
        $entity->tableName = $className::tableName();

        $entity->populateRelation('attributeItems', ModelAttributeEntity::findAll($entity));
        $entity->populateRelation('relationItems', ModelRelationEntity::findAll($entity));

        return $entity;
    }

    public function load($data, $formName = null)
    {
        if (parent::load($data, $formName)) {
            // Set modelEntity link
            foreach ($this->attributeItems as $attributeEntity) {
                $attributeEntity->modelEntity = $this;
            }
            foreach ($this->relationItems as $relationEntity) {
                $relationEntity->modelEntity = $this;
            }

            return true;
        }
        return false;
    }

    public function fields()
    {
        return array_merge(
            $this->attributes(),
            [
                'attributeItems',
                'relationItems',
            ]
        );
    }

    public function save()
    {
        if ($this->validate()) {
            $prevModelEntity = class_exists($this->getClassName()) ? static::findOne($this->getClassName()) : null;

            // Lazy create module
            ModuleEntity::findOrCreate($this->moduleId);
            
            // Create/update meta information
            if (GiiHelper::isOverWriteClass($this->getClassName()) && GiiModule::getInstance()->showSteroidsEntries) {
                // TODO Save lib class
            }
            GiiHelper::renderFile('model/meta', $this->getMetaPath(), [
                'modelEntity' => $this,
            ]);
            GiiHelper::renderFile('model/meta_js', $this->getMetaJsPath(), [
                'modelEntity' => $this,
            ]);
            \Yii::$app->session->addFlash('success', 'Meta  info model ' . $this->name . 'Meta updated');

            // Create model, if not exists
            if (!file_exists($this->getPath())) {
                GiiHelper::renderFile('model/model', $this->getPath(), [
                    'modelEntity' => $this,
                ]);
                \Yii::$app->session->addFlash('success', 'Added model ' . $this->name);
            }

            // Create migration
            $migrationMethods = new MigrationMethods([
                'prevModelEntity' => $prevModelEntity,
                'nextModelEntity' => $this,
                'migrateMode' => !empty($this->migrateMode)
                    ? $this->migrateMode
                    : MigrateMode::UPDATE,
            ]);
            if (!$migrationMethods->isEmpty()) {
                $name = $migrationMethods->generateName();
                $path = GiiHelper::getModuleDir($this->moduleId) . '/migrations/' . $name . '.php';

                GiiHelper::renderFile('model/migration', $path, [
                    'modelEntity' => $this,
                    'name' => $name,
                    'namespace' => 'app\\' . implode('\\', explode('.', $this->moduleId)) . '\\migrations',
                    'migrationMethods' => $migrationMethods,
                ]);
                \Yii::$app->session->addFlash('success', 'Added migration ' . $name);
            }

            return true;
        }
        return false;
    }

    public function getClassName()
    {
        return $this->className ?: GiiHelper::getClassName(ClassType::MODEL, $this->moduleId, $this->name);
    }

    public function getOverWriteEntity()
    {
        $libClassName = str_replace('app\\', 'steroids\\modules\\', $this->getClassName());
        if (class_exists($libClassName)) {
            return static::findOne($libClassName);
        }
        return null;
    }

    public function getModelsDir()
    {
        return $this->className && class_exists($this->className)
            ? dirname((new \ReflectionClass($this->className))->getFileName())
            : GiiHelper::getModuleDir($this->moduleId) . '/models';
    }

    public function getPath()
    {
        return $this->getModelsDir() . '/' . $this->name . '.php';
    }

    public function getMetaPath()
    {
        return $this->getModelsDir() . '/meta/' . $this->name . 'Meta.php';
    }

    public function getMetaJsPath()
    {
        return $this->getModelsDir() . '/meta/' . $this->name . 'Meta.js';
    }

    /**
     * @param string $name
     * @return null|ModelAttributeEntity
     */
    public function getAttributeEntity($name)
    {
        foreach ($this->attributeItems as $item) {
            if ($item->name === $name) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return null|ModelRelationEntity
     */
    public function getRelationEntity($name)
    {
        foreach ($this->relationItems as $item) {
            if ($item->name === $name) {
                return $item;
            }
        }
        return null;
    }

    public function getPublicAttributeItems()
    {
        return array_filter($this->attributeItems, function (ModelAttributeEntity $item) {
            return !$item->isProtected;
        });
    }

    public function getPublicRelationItems()
    {
        return array_filter($this->relationItems, function (ModelRelationEntity $item) {
            return !$item->isProtected;
        });
    }

    /**
     * @param string $indent
     * @param array $useClasses
     * @return mixed|string
     */
    public function renderMeta($indent = '', &$useClasses = [])
    {
        $category = strpos($this->getClassName(), 'steroids\\') === 0 ? 'steroids' : 'app';
        $meta = static::exportMeta($this->publicAttributeItems, $useClasses);
        foreach ($meta as $name => $item) {
            foreach ($item as $key => $value) {
                // Localization
                if (in_array($key, ['label', 'hint'])) {
                    $meta[$name][$key] = new ValueExpression('Yii::t(\'' . $category . '\', ' . GiiHelper::varExport($value) . ')');
                    $useClasses[] = '\Yii';
                }

                if ($key === 'enumClassName') {
                    $enumEntity = EnumEntity::findOne($value);
                    $meta[$name][$key] = new ValueExpression($enumEntity->name . '::class');
                    $useClasses[] = $enumEntity->getClassName();
                }
            }
        }
        return GiiHelper::varExport($meta, $indent);
    }

    /**
     * @param string $indent
     * @param array $import
     * @return mixed|string
     */
    public function renderJsFields($indent = '', &$import = [])
    {
        $result = [];

        foreach (static::exportMeta($this->publicAttributeItems) as $attribute => $item) {
            $props = [];
            $type = \Yii::$app->types->getType($this->getAttributeEntity($attribute)->appType);

            // Add label and hint
            foreach (['label', 'hint'] as $key) {
                if (empty($item[$key])) {
                    continue;
                }

                $text = ArrayHelper::getValue($item, $key);
                if ($text) {
                    $props[$key] = GiiHelper::locale($text);
                    $import[] = 'import {locale} from \'components\';';
                }
            }

            // Add required
            if (ArrayHelper::getValue($item, 'isRequired')) {
                $props['required'] = true;
            }

            // Add other props
            $type->prepareFieldProps($this->getClassName(), $attribute, $props, $import);

            $result[$attribute] = $props;
        }

        return GiiHelper::varJsExport($result, $indent);
    }

    /**
     * @param string $indent
     * @param array $import
     * @return mixed|string
     */
    public function renderJsFormatters($indent = '', &$import = [])
    {
        $result = [];
        foreach (static::exportMeta($this->publicAttributeItems) as $attribute => $item) {
            $props = [];
            $type = \Yii::$app->types->getType($this->getAttributeEntity($attribute)->appType);

            // Add label and hint
            foreach (['label', 'hint'] as $key) {
                if (empty($item[$key])) {
                    continue;
                }

                $text = ArrayHelper::getValue($item, $key);
                if ($text) {
                    $props[$key] = GiiHelper::locale($text);
                    $import[] = 'import {locale} from \'components\';';
                }
            }

            // Add other props
            $type->prepareFormatterProps($this->getClassName(), $attribute, $props, $import);

            $result[$attribute] = $props;
        }

        return GiiHelper::varJsExport($result, $indent);
    }

    /**
     * @param ModelAttributeEntity[] $attributeEntries
     * @param $useClasses
     * @return array
     */
    public static function exportMeta($attributeEntries, &$useClasses = [])
    {
        $meta = [];
        foreach ($attributeEntries as $attributeEntry) {
            $meta[$attributeEntry->name] = [];

            $properties = array_merge(
                $attributeEntry->getAttributes(),
                $attributeEntry->getCustomProperties()
            );
            foreach ($properties as $key => $value) {
                // Skip defaults
                if ($key === 'appType' && $value === 'string') {
                    continue;
                }
                if ($key === 'stringType' && $value === 'text') {
                    continue;
                }
                if ($key === 'isRequired' && $value === false) {
                    continue;
                }

                // Skip array key
                if ($key === 'name' || $key === 'prevName' || $key === 'isProtected') {
                    continue;
                }

                // Skip service postgres-specific key
                if ($key === 'customMigrationColumnType') {
                    continue;
                }

                // Skip self link
                if ($key === 'modelEntity') {
                    continue;
                }

                // Skip null values
                if ($value === '' || $value === null) {
                    continue;
                }

                // Items process
                if ($key === 'items') {
                    $value = static::exportMeta($value, $useClasses);
                }

                $meta[$attributeEntry->name][$key] = $value;
            }
        }
        return $meta;
    }

    public function renderRules(&$useClasses = [])
    {
        return static::exportRules($this->publicAttributeItems, $this->publicRelationItems, $useClasses);
    }

    /**
     * @param ModelAttributeEntity[] $attributeEntities
     * @param ModelRelationEntity[] $relationEntities
     * @param array $useClasses
     * @return array
     */
    public static function exportRules($attributeEntities, $relationEntities, &$useClasses = [])
    {
        $types = [];
        foreach ($attributeEntities as $attributeEntity) {
            $type = \Yii::$app->types->getType($attributeEntity->appType);
            if (!$type) {
                continue;
            }

            $rules = $type->giiRules($attributeEntity, $useClasses) ?: [];
            foreach ($rules as $rule) {
                /** @var array $rule */
                $attributes = (array)ArrayHelper::remove($rule, 0);
                $name = ArrayHelper::remove($rule, 1);
                $validatorRaw = GiiHelper::varExport($name);
                if (!empty($rule)) {
                    $validatorRaw .= ', ' . substr(GiiHelper::varExport($rule, '', true), 1, -1);
                }

                foreach ($attributes as $attribute) {
                    $types[$validatorRaw][] = $attribute;
                }
            }

            if ($attributeEntity->isRequired) {
                $types["'required'"][] = $attributeEntity->name;
            }
        }

        $rules = [];
        foreach ($types as $validatorRaw => $attributes) {
            $attributesRaw = "'" . implode("', '", $attributes) . "'";
            if (count($attributes) > 1) {
                $attributesRaw = "[$attributesRaw]";
            }

            $rules[] = "[$attributesRaw, $validatorRaw]";
        }

        $primaryKey = null;

        foreach ($attributeEntities as $attributeEntity) {
            if ($attributeEntity->appType === 'primaryKey') {
                $primaryKey = $attributeEntity->name;
            }
        }

        // Exist rules for foreign keys
        foreach ($relationEntities as $relationEntity) {
            // Checking for existence of the relations with self key == primary key might fail when run on save
            $isSelfExistCheck = $primaryKey !== null && $relationEntity->isHasOne && $relationEntity->selfKey === $primaryKey;

            if (!$relationEntity->isHasOne || $isSelfExistCheck) {
                continue;
            }

            $attribute = $relationEntity->name;
            $relationModelEntity = ModelEntity::findOne($relationEntity->relationModel);
            $refClassName = $relationModelEntity->name;
            $useClasses[] = $relationModelEntity->getClassName();
            $useClasses[] = RecordExistValidator::class;
            $targetAttributes = "'{$relationEntity->selfKey}' => '{$relationEntity->relationKey}'";

            $rules[] = "[" . implode(', ', [
                    "'$attribute'",
                    "RecordExistValidator::class",
                    "'skipOnError' => true",
                    "'targetClass' => $refClassName::class",
                    "'targetAttribute' => [$targetAttributes]",
                ]) . "]";
        }

        return $rules;
    }

    public function renderBehaviors($indent = '', &$useClasses = [])
    {
        return static::exportBehaviors($this->publicAttributeItems, $indent, $useClasses);
    }

    /**
     * @param ModelAttributeEntity[] $attributeEntities
     * @param string $indent
     * @param array $useClasses
     * @return string
     */
    public static function exportBehaviors($attributeEntities, $indent = '', &$useClasses = [])
    {
        $behaviors = [];
        foreach ($attributeEntities as $attributeEntity) {
            $appType = \Yii::$app->types->getType($attributeEntity->appType);
            if (!$appType) {
                continue;
            }

            foreach ($appType->giiBehaviors($attributeEntity) as $behaviour) {
                if (is_string($behaviour)) {
                    $behaviour = ['class' => $behaviour];
                }

                $className = ArrayHelper::remove($behaviour, 'class');
                if (!isset($behaviors[$className])) {
                    $behaviors[$className] = [];
                }
                $behaviors[$className] = ArrayHelper::merge($behaviors[$className], $behaviour);
            }
        }
        if (empty($behaviors)) {
            return '';
        }

        $items = [];
        foreach ($behaviors as $className => $params) {
            $nameParts = explode('\\', $className);
            $name = array_slice($nameParts, -1)[0];
            $useClasses[] = $className;

            if (empty($params)) {
                $items[] = "$name::class,";
            } else {
                $params = array_merge([
                    'class' => new ValueExpression("$name::class"),
                ], $params);
                $items[] = GiiHelper::varExport($params, $indent) . ",";
            }
        }
        return implode("\n" . $indent, $items) . "\n";
    }

    public function getPhpDocProperties()
    {
        $properties = [];
        foreach ($this->publicAttributeItems as $attributeEntity) {
            if ($attributeEntity->getDbType()) {
                $properties[$attributeEntity->name] = $attributeEntity->getPhpDocType();
            }
        }
        return $properties;
    }

    public function getProperties()
    {
        $properties = [];
        foreach ($this->publicAttributeItems as $attributeEntity) {
            $appType = \Yii::$app->types->getType($attributeEntity->appType);
            if (!$appType) {
                continue;
            }

            if ($appType instanceof RelationType && $attributeEntity->modelEntity instanceof ModelEntity && !$appType->giiDbType($attributeEntity)) {
                $relation = $attributeEntity->modelEntity->getRelationEntity($attributeEntity->relationName);
                $properties[$attributeEntity->name] = $relation && !$relation->isHasOne ? '[]' : null;
            }
        }
        return $properties;
    }

    public function getRequestParamName()
    {
        /** @var Model $className */
        $className = $this->getClassName();
        return $className::getRequestParamName();
    }

    public function getCanRules()
    {
        $names = [];
        $methods = (new \ReflectionClass($this->getClassName()))->getMethods();
        foreach ($methods as $method) {
            if ($method->isPublic() && $method->getNumberOfRequiredParameters() === 1
                && strpos($method->name, 'can') === 0 && $method->getParameters()[0]->name === 'user') {
                $names[] = lcfirst(substr($method->name, 3));
            }
        }
        return $names;
    }
}
