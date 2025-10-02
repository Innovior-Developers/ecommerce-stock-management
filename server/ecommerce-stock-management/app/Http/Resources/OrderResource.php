<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->generatePublicId($this->_id), // ✅ Hash MongoDB ID
            'order_number' => $this->order_number,
            'customer' => [
                'id' => $this->customer_id ? $this->generateCustomerId($this->customer_id) : null,
                'name' => $this->customer?->first_name . ' ' . $this->customer?->last_name,
                'email_masked' => $this->maskEmail($this->customer?->user?->email ?? ''),
            ],
            'status' => $this->status,
            'payment_status' => $this->payment_status ?? 'pending',
            'payment_method' => $this->payment_method,
            'total_amount' => (float) $this->total_amount,
            'subtotal' => (float) ($this->subtotal ?? 0),
            'tax_amount' => (float) ($this->tax_amount ?? 0),
            'shipping_amount' => (float) ($this->shipping_amount ?? 0),
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'items_count' => count($this->items ?? []),
            'items' => $this->when(
                $request->route()->getName() === 'orders.show',
                collect($this->items ?? [])->map(function ($item) {
                    return [
                        'product_id' => $this->generateProductId($item['product_id'] ?? ''),
                        'product_name' => $item['product_name'] ?? '',
                        'quantity' => $item['quantity'] ?? 0,
                        'price' => (float) ($item['price'] ?? 0),
                        'subtotal' => (float) ($item['subtotal'] ?? 0),
                    ];
                })
            ),
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            // ❌ Don't expose: _id, customer_id, raw customer data
        ];
    }

    private function generatePublicId($mongoId)
    {
        return 'ord_' . substr(hash('sha256', (string)$mongoId), 0, 16);
    }

    private function generateCustomerId($mongoId)
    {
        return 'cus_' . substr(hash('sha256', (string)$mongoId), 0, 16);
    }

    private function generateProductId($mongoId)
    {
        return 'prod_' . substr(hash('sha256', (string)$mongoId), 0, 16);
    }

    private function maskEmail($email)
    {
        if (!$email) return '';

        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;

        $name = $parts[0];
        $domain = $parts[1];

        return substr($name, 0, 2) . '***@' . $domain;
    }
}
