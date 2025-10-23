<?php

namespace App\Traits;

use MongoDB\BSON\ObjectId;

trait MongoIdHelper
{
    /**
     * Boot the trait - ensure _id is always a string
     */
    protected static function bootMongoIdHelper()
    {
        // After model is retrieved from database
        static::retrieved(function ($model) {
            if (isset($model->_id) && $model->_id instanceof ObjectId) {
                $model->_id = (string) $model->_id;
            }
        });

        // After model is created
        static::created(function ($model) {
            if (isset($model->_id) && $model->_id instanceof ObjectId) {
                $model->_id = (string) $model->_id;
            }
        });

        // After model is saved
        static::saved(function ($model) {
            if (isset($model->_id) && $model->_id instanceof ObjectId) {
                $model->_id = (string) $model->_id;
            }
        });
    }

    /**
     * Get the primary key for the model.
     */
    public function getKeyName()
    {
        return '_id';
    }

    /**
     * Get the value of the model's primary key.
     */
    public function getKey()
    {
        return $this->getAttribute('_id');
    }

    /**
     * Get the value of the model's route key.
     */
    public function getRouteKey()
    {
        return (string) $this->getAttribute('_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return '_id';
    }

    /**
     * Validate if a given ID is a valid MongoDB ObjectID
     */
    public static function isValidMongoId($id): bool
    {
        if (!is_string($id)) {
            return false;
        }
        return preg_match('/^[a-f\d]{24}$/i', $id) === 1;
    }

    /**
     * Convert string to ObjectID if needed
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
     * Find by ID with automatic _id handling
     */
    public static function findByMongoId($id)
    {
        $sanitizedId = self::isValidMongoId($id) ? $id : null;

        if (!$sanitizedId) {
            return null;
        }

        return static::where('_id', $sanitizedId)->first();
    }
}