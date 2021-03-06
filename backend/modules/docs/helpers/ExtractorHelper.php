<?php

namespace steroids\modules\docs\helpers;

use Doctrine\Common\Annotations\TokenParser;
use yii\base\Model;
use yii\helpers\ArrayHelper;

abstract class ExtractorHelper
{
    /**
     * @param string $type
     * @param string $inClassName
     * @return string
     * @throws \ReflectionException
     */
    public static function resolveType($type, $inClassName)
    {
        return !static::isPrimitiveType($type)
            ? static::resolveClassName($type, $inClassName)
            : static::normalizePrimitiveType($type);
    }

    /**
     * @param string $shortName
     * @param string $inClassName
     * @return string
     * @throws \ReflectionException
     */
    public static function resolveClassName($shortName, $inClassName)
    {
        // Check name with namespace
        if (strpos($shortName, '\\') !== false) {
            return $shortName;
        }

        // Fetch use statements
        $controllerInfo = new \ReflectionClass($inClassName);
        $controllerNamespace = $controllerInfo->getNamespaceName();
        $tokenParser = new TokenParser(file_get_contents($controllerInfo->getFileName()));
        $useStatements = $tokenParser->parseUseStatements($controllerNamespace);

        $className = ArrayHelper::getValue($useStatements, strtolower($shortName), $shortName);
        $className = '\\' . ltrim($className, '\\');
        return $className;
    }

    public static function normalizePrimitiveType($string)
    {
        $map = [
            'int' => 'integer',
            'bool' => 'boolean',
            'double' => 'float',
            'true' => 'boolean',
            'false' => 'boolean',
        ];
        return ArrayHelper::getValue($map, $string, $string);
    }

    public static function isPrimitiveType($string)
    {
        $list = [
            'string',
            'integer',
            'float',
            'boolean',
            'array',
            'resource',
            'null',
            'callable',
            'mixed',
            'void',
            'object',
        ];
        return in_array(static::normalizePrimitiveType($string), $list);
    }

    public static function isModelAttribute($model, $attribute)
    {
        $className = is_object($model) ? get_class($model) : $model;
        if (is_subclass_of($className, Model::class)) {
            /** @var Model $modelInstance */
            $modelInstance = new $className();
            return in_array($attribute, $modelInstance->attributes());
        }
        return false;
    }

    /**
     * @param string|object $object
     * @param string $attribute
     * @return null
     * @throws \ReflectionException
     */
    public static function findPhpDocType($object, $attribute)
    {
        $className = is_object($object) ? get_class($object) : $object;

        // Find is class php doc
        $classInfo = new \ReflectionClass($className);
        if (preg_match('/@property(-[a-z]+)? +([^ \n]+) \$' . preg_quote($attribute) . '/', $classInfo->getDocComment(), $matchClass)) {
            return static::resolveType($matchClass[1], $className);
        }

        // Find in class property php doc
        $propertyInfo = $classInfo->hasProperty($attribute) ? $classInfo->getProperty($attribute) : null;
        if ($propertyInfo && preg_match('/@(var|type) +([^ \n]+)/', $propertyInfo->getDocComment(), $matchProperty)) {
            return static::resolveType($matchProperty[2], $className);
        }

        // Find in getter method
        $getter = 'get' . ucfirst($attribute);
        $methodInfo = $classInfo->hasMethod($getter) ? $classInfo->getMethod($getter) : null;
        if ($methodInfo && preg_match('/@return +([^ \n]+)/', $methodInfo->getDocComment(), $matchMethod)) {
            return static::resolveType($matchMethod[1], $className);
        }

        return null;
    }
}