<?php
// filepath: server/ecommerce-stock-management/app/Services/QuerySanitizer.php

namespace App\Services;

class QuerySanitizer
{
    /**
     * Dangerous MongoDB operators that should be removed
     */
    private static array $dangerousOperators = [
        '$where',
        '$regex',
        '$ne',
        '$gt',
        '$gte',
        '$lt',
        '$lte',
        '$in',
        '$nin',
        '$exists',
        '$or',
        '$and',
        '$not',
        '$nor',
        '$expr',
        '$function',
        '$accumulator',
        '$jsonSchema',
    ];

    /**
     * Sanitize a single value or array of values
     * 
     * @param mixed $value
     * @return mixed
     */
    public static function sanitize($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }

        if (!is_string($value)) {
            return $value;
        }

        // Remove MongoDB operators
        $value = str_replace(['$', '{', '}'], '', $value);

        // Remove NoSQL injection patterns
        foreach (self::$dangerousOperators as $operator) {
            $value = str_ireplace($operator, '', $value);
        }

        // Basic XSS prevention
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        // Remove null bytes
        $value = str_replace("\0", '', $value);

        return trim($value);
    }

    /**
     * Sanitize search query specifically
     * 
     * @param string|null $search
     * @return string|null
     */
    public static function sanitizeSearch(?string $search): ?string
    {
        if (empty($search)) {
            return null;
        }

        // Sanitize the search string
        $search = self::sanitize($search);

        // Limit length to prevent DoS
        $search = substr($search, 0, 100);

        return $search;
    }

    /**
     * Sanitize MongoDB ID
     * 
     * @param mixed $id
     * @return string|null
     */
    public static function sanitizeMongoId($id): ?string
    {
        if (!is_string($id) && !is_numeric($id)) {
            return null;
        }

        $id = (string) $id;

        // MongoDB ObjectID is 24 character hex string
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return null;
        }

        return $id;
    }

    /**
     * Sanitize array of IDs
     * 
     * @param array $ids
     * @return array
     */
    public static function sanitizeMongoIds(array $ids): array
    {
        return array_filter(
            array_map([self::class, 'sanitizeMongoId'], $ids),
            fn($id) => $id !== null
        );
    }
}