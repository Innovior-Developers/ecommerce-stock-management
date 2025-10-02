<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->public_id ?? 'cus_' . substr(md5($this->_id), 0, 16),
            'name' => $this->user ? $this->user->name : 'Unknown',
            'email_masked' => $this->maskEmail($this->user->email ?? ''),
            'phone_masked' => $this->maskPhone($this->phone ?? ''),
            'orders_count' => $this->orders_count ?? 0,
            'total_spent' => $this->total_spent ?? 0.00,
            'status' => $this->user->status ?? 'unknown',
            'joined_date' => $this->created_at->format('Y-m-d'),
            'last_order_date' => $this->last_order_date
                ? $this->last_order_date->format('Y-m-d')
                : null,
            // ❌ DON'T expose: _id, user_id, full email, full phone
        ];
    }

    private function maskEmail($email)
    {
        if (!$email) return '';
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        return substr($name, 0, 2) . '***@' . $domain;
    }

    private function maskPhone($phone)
    {
        if (!$phone) return '';
        return substr($phone, 0, 3) . '***' . substr($phone, -2);
    }
}