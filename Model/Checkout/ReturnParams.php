<?php

namespace Paynl\Payment\Model\Checkout;

/**
 * Reads and validates the parameters of the payment return page.
 *
 * The return page also accepts JSON POST bodies (see PayAction), so params can be
 * booleans, numbers, arrays or null. Loose comparison against such values is unsafe.
 */
class ReturnParams
{
    /**
     * Returns the param only when it is a string.
     * @param array $params
     * @param string $key
     * @return string|null
     */
    public static function getString(array $params, string $key): ?string
    {
        $value = $params[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    /**
     * Returns string and integer params as string. Any other type is ignored.
     * @param array $params
     * @param string $key
     * @return string|null
     */
    public static function getScalar(array $params, string $key): ?string
    {
        $value = $params[$key] ?? null;
        return (is_string($value) || is_int($value)) ? (string)$value : null;
    }

    /**
     * @param mixed $storedTransactionId
     * @param mixed $payOrderId
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     * @return boolean
     */
    public static function transactionMatches($storedTransactionId, $payOrderId): bool
    {
        if (!is_string($payOrderId) || $payOrderId === '') {
            return false;
        }
        if (!is_string($storedTransactionId) && !is_int($storedTransactionId)) {
            return false;
        }
        $storedTransactionId = (string)$storedTransactionId;
        if ($storedTransactionId === '') {
            return false;
        }
        return hash_equals($storedTransactionId, $payOrderId);
    }
}
