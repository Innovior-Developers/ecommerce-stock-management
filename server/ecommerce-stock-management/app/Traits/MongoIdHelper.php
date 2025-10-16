<?php

namespace App\Traits;

use MongoDB\BSON\ObjectId;

trait MongoIdHelper
{
    /**
     * Get the ID regardless of format (_id or id)
     * ✅ FIX: Make compatible with MongoDB\Laravel\Auth\User signature
     */
    public function getIdAttribute($value = null)
    {
        // If value is passed (from parent), use it
        if ($value !== null) {
            return $value;
        }

        // Otherwise, return _id or fallback to id
        return $this->attributes['_id'] ?? $this->attributes['id'] ?? null;
    }

    /**
     * Ensure _id is always set when model is retrieved
     */
    protected static function bootMongoIdHelper()
    {
        static::retrieved(function ($model) {
            if (!isset($model->_id) && isset($model->id)) {
                $model->_id = $model->id;
            }
        });
    }

    /**
     * Override primary key handling for MongoDB
     */
    public function getRouteKeyName()
    {
        return '_id';
    }

    /**
     * Get the primary key for the model.
     */
    public function getKeyName()
    {
        return '_id';
    }

    /**
     * Get the primary key type
     */
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Indicates if the IDs are auto-incrementing
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * ✅ Validate if a given ID is a valid MongoDB ObjectID
     */
    public static function isValidMongoId($id): bool
    {
        if (!is_string($id)) {
            return false;
        }

        // MongoDB ObjectID is 24 character hex string
        return preg_match('/^[a-f\d]{24}$/i', $id) === 1;
    }

    /**
     * ✅ Convert string to ObjectID if needed
     */
    public static function toObjectId($id): ?ObjectId
    {
        if ($id instanceof ObjectId) {
            return $id;
        }

        if (!self::isValidMongoId($id)) {
            return null;
        }

        try {
            return new ObjectId($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ✅ Find by ID with automatic _id handling
     */
    public static function findByMongoId($id)
    {
        $sanitizedId = self::isValidMongoId($id) ? $id : null;

        if (!$sanitizedId) {
            return null;
        }

        return static::where('_id', $sanitizedId)->first();
    }

    /**
     * ✅ Find multiple by IDs
     */
    public static function findManyByMongoIds(array $ids)
    {
        $sanitizedIds = array_filter($ids, [self::class, 'isValidMongoId']);

        if (empty($sanitizedIds)) {
            return collect([]);
        }

        return static::whereIn('_id', $sanitizedIds)->get();
    }
}