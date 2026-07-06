<?php

namespace App\Core;

class PayFast
{
    public static function settings(): object
    {
        $config = require __DIR__ . '/../../config/config.php';

        $mode = $config['payfast']['mode'] ?? 'sandbox';

        if (!in_array($mode, ['sandbox', 'live'], true)) {
            $mode = 'sandbox';
        }

    $settings = $config['payfast'][$mode];

    return (object) [
        'mode' => $mode,
        'merchant_id' => $settings['merchant_id'] ?? '',
        'merchant_key' => $settings['merchant_key'] ?? '',
        'passphrase' => $settings['passphrase'] ?? '',
        'process_url' => $settings['process_url'] ?? '',
        'validate_url' => $settings['validate_url'] ?? '',
        ];
}

public static function checkoutFieldOrder(): array
{
    return [
        'merchant_id',
        'merchant_key',
        'return_url',
        'cancel_url',
        'notify_url',

        'name_first',
        'name_last',
        'email_address',
        'cell_number',

        'm_payment_id',
        'amount',
        'item_name',
        'item_description',

        'custom_int1',
        'custom_int2',
        'custom_int3',
        'custom_int4',
        'custom_int5',

        'custom_str1',
        'custom_str2',
        'custom_str3',
        'custom_str4',
        'custom_str5',

        'email_confirmation',
        'confirmation_address',

        'payment_method',
        ];
}

public static function generateSignature(array $data, string $passphrase = ''): string
{
    unset($data['signature']);

    $orderedData = [];

    foreach (self::checkoutFieldOrder() as $key) {
        if (!array_key_exists($key, $data)) {
            continue;
        }

    $value = trim((string) $data[$key]);

    if ($value === '') {
        continue;
    }

$orderedData[$key] = $value;
}

$pairs = [];

foreach ($orderedData as $key => $value) {
    $pairs[] = $key . '=' . urlencode($value);
}

$signatureString = implode('&', $pairs);

if ($passphrase !== '') {
    $signatureString .= '&passphrase=' . urlencode(trim($passphrase));
}

return md5($signatureString);
}

public static function generateNotifySignature(array $data, string $passphrase = ''): string
{
    $pairs = [];

    foreach ($data as $key => $value) {
        if ($key === 'signature') {
            continue;
        }

    $value = trim((string) stripslashes($value));

                /*
                 * Important:
                 * For PayFast notify/ITN, include blank custom fields too:
                 * custom_str2=, custom_int3= etc.
                 */
    $pairs[] = $key . '=' . urlencode($value);
}

$signatureString = implode('&', $pairs);

if ($passphrase !== '') {
    $signatureString .= '&passphrase=' . urlencode(trim($passphrase));
}

return md5($signatureString);
}

public static function validateItnWithPayFast(array $data): bool
{
    $settings = self::settings();

    if (empty($settings->validate_url)) {
        return false;
    }

$payload = http_build_query($data);

$ch = curl_init();

curl_setopt_array($ch, [
        CURLOPT_URL => $settings->validate_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

$response = curl_exec($ch);

if ($response === false) {
    curl_close($ch);
    return false;
}

curl_close($ch);

return trim($response) === 'VALID';
}
}
