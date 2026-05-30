<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SteadfastService
{
    public function isConfigured(): bool
    {
        return !empty(config('services.steadfast.api_key'))
            && !empty(config('services.steadfast.secret_key'));
    }

    public function createBooking(Order $order): array
    {
        $order->loadMissing(['orderDetails.product', 'user']);

        if (!$this->isConfigured()) {
            throw new \RuntimeException('Steadfast API is not configured. Please set STEADFAST_API_KEY and STEADFAST_SECRET_KEY in .env.');
        }

        if (!empty($order->steadfast_consignment_id)) {
            return [
                'created' => false,
                'message' => 'Steadfast booking already exists for this order.',
                'consignment' => [
                    'consignment_id' => $order->steadfast_consignment_id,
                    'tracking_code' => $order->steadfast_tracking_code,
                    'status' => $order->steadfast_status,
                ],
            ];
        }

        $payload = $this->buildPayload($order);

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'Api-Key' => config('services.steadfast.api_key'),
                'Secret-Key' => config('services.steadfast.secret_key'),
                'Content-Type' => 'application/json',
            ])
            ->post(rtrim(config('services.steadfast.base_url'), '/') . '/create_order', $payload);

        $responseBody = $response->json();
        $consignment = $responseBody['consignment'] ?? [];

        if (
            !$response->successful()
            || (int) ($responseBody['status'] ?? $response->status()) !== 200
            || empty($consignment['consignment_id'])
        ) {
            $message = $responseBody['message'] ?? 'Steadfast booking request failed.';

            Log::warning('steadfast_booking_failed', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'message' => $message,
                'response_status' => $response->status(),
                'response_body' => $responseBody,
            ]);

            throw new \RuntimeException($message);
        }

        $order->shipping_method = $order->shipping_method ?: 'steadfast';
        $order->steadfast_consignment_id = (string) ($consignment['consignment_id'] ?? '');
        $order->steadfast_tracking_code = (string) ($consignment['tracking_code'] ?? '');
        $order->steadfast_status = (string) ($consignment['status'] ?? 'in_review');

        if (empty($order->tracking_code) && !empty($order->steadfast_tracking_code)) {
            $order->tracking_code = $order->steadfast_tracking_code;
        }

        $order->save();

        Log::info('steadfast_booking_created', [
            'order_id' => $order->id,
            'order_code' => $order->code,
            'consignment_id' => $order->steadfast_consignment_id,
            'tracking_code' => $order->steadfast_tracking_code,
        ]);

        return [
            'created' => true,
            'message' => $responseBody['message'] ?? 'Steadfast booking created successfully.',
            'consignment' => $consignment,
        ];
    }

    private function buildPayload(Order $order): array
    {
        $shipping = json_decode($order->shipping_address ?? '{}');
        $recipientPhone = $this->normalizePhone(
            $shipping->phone ?? $order->user->phone ?? null
        );

        $alternativePhone = null;
        if (!empty($order->user->phone)) {
            $normalizedUserPhone = $this->tryNormalizePhone($order->user->phone);
            if (!empty($normalizedUserPhone) && $normalizedUserPhone !== $recipientPhone) {
                $alternativePhone = $normalizedUserPhone;
            }
        }

        $itemDescription = $order->orderDetails
            ->map(function ($orderDetail) {
                return optional($orderDetail->product)->getTranslation('name');
            })
            ->filter()
            ->unique()
            ->implode(', ');

        $payload = [
            'invoice' => (string) $order->code,
            'recipient_name' => Str::limit((string) ($shipping->name ?? $order->user->name ?? 'Customer'), 100, ''),
            'recipient_phone' => $recipientPhone,
            'alternative_phone' => $alternativePhone,
            'recipient_email' => $shipping->email ?? $order->user->email ?? null,
            'recipient_address' => $this->buildRecipientAddress($shipping),
            'cod_amount' => $order->payment_status === 'paid' ? 0 : round((float) $order->grand_total, 2),
            'note' => !empty($order->additional_info) ? Str::limit((string) $order->additional_info, 250, '') : null,
            'item_description' => !empty($itemDescription) ? Str::limit($itemDescription, 250, '') : null,
            'total_lot' => (int) $order->orderDetails->sum('quantity'),
            'delivery_type' => $order->shipping_type === 'pickup_point' ? 1 : 0,
        ];

        return array_filter($payload, function ($value) {
            return !is_null($value) && $value !== '';
        });
    }

    private function buildRecipientAddress(object $shipping): string
    {
        $parts = array_filter([
            $shipping->address ?? null,
            $shipping->city ?? null,
            $shipping->state ?? null,
            $shipping->postal_code ?? null,
            $shipping->country ?? null,
        ]);

        $address = trim(implode(', ', $parts));
        if ($address === '') {
            throw new \RuntimeException('Recipient address is missing for this order.');
        }

        return Str::limit($address, 250, '');
    }

    private function tryNormalizePhone(?string $phone): ?string
    {
        try {
            return $this->normalizePhone($phone);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            throw new \RuntimeException('Recipient phone number is missing for Steadfast booking.');
        }

        if (Str::startsWith($digits, '880') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        if (Str::startsWith($digits, '1') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        if (strlen($digits) !== 11 || !Str::startsWith($digits, '01')) {
            throw new \RuntimeException('Recipient phone number must be a valid 11 digit Bangladesh number for Steadfast booking.');
        }

        return $digits;
    }
}

