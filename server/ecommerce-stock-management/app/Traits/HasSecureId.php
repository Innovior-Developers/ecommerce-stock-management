<?php

namespace App\Traits;

trait HasSecureId
{
    /**
     * Generate a hashed public ID from MongoDB ObjectId
     */
    public function getHashedIdAttribute(): string
    {
        $prefix = $this->getIdPrefix();
        return $prefix . substr(hash('sha256', (string)$this->_id), 0, 16);
    }

    /**
     * Get the prefix for the hashed ID
     */
    protected function getIdPrefix(): string
    {
        return match ($this->getTable()) {
            'users' => 'usr_',
            'customers' => 'cus_',
            'products' => 'prod_',
            'categories' => 'cat_',
            'orders' => 'ord_',
            default => 'id_'
        };
    }

    /**
     * Find model by hashed ID
     */
    public static function findByHashedId(string $hashedId)
    {
        // Remove prefix
        $hash = preg_replace('/^[a-z]+_/', '', $hashedId);

        // Search through all records (inefficient but secure)
        return static::all()->first(function ($model) use ($hash) {
            $modelHash = substr(hash('sha256', (string)$model->_id), 0, 16);
            return $modelHash === $hash;
        });
    }

    /**
     * Mask email address
     */
    public static function maskEmail(?string $email): string
    {
        if (!$email) return '';

        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;

        $name = $parts[0];
        $domain = $parts[1];

        $maskedName = substr($name, 0, 2) . str_repeat('*', max(3, strlen($name) - 2));
        return $maskedName . '@' . $domain;
    }

    /**
     * Mask phone number
     */
    public static function maskPhone(?string $phone): string
    {
        if (!$phone) return '';

        $length = strlen($phone);
        if ($length <= 4) return $phone;

        return substr($phone, 0, 3) . str_repeat('*', $length - 5) . substr($phone, -2);
    }
}
