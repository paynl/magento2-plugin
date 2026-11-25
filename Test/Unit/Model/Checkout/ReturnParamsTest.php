<?php

namespace Paynl\Payment\Test\Unit\Model\Checkout;

use Paynl\Payment\Model\Checkout\ReturnParams;
use PHPUnit\Framework\TestCase;

class ReturnParamsTest extends TestCase
{
    private const TRANSACTION_ID = '51234567890X1a2b';

    public function testMatchingTransactionIdIsAccepted(): void
    {
        $this->assertTrue(ReturnParams::transactionMatches(self::TRANSACTION_ID, self::TRANSACTION_ID));
    }

    /**
     * @return array
     */
    public static function invalidPayOrderIdProvider(): array
    {
        return [
            'boolean true' => [true],
            'boolean false' => [false],
            'integer' => [1],
            'zero' => [0],
            'float' => [1.0],
            'array' => [[self::TRANSACTION_ID]],
            'empty array' => [[]],
            'null' => [null],
            'empty string' => [''],
            'wrong string' => ['wrong'],
            'prefix of the id' => [substr(self::TRANSACTION_ID, 0, 5)],
            'string "1"' => ['1'],
            'string "true"' => ['true'],
        ];
    }

    /**
     * @dataProvider invalidPayOrderIdProvider
     * @param mixed $payOrderId
     */
    public function testInvalidPayOrderIdIsRejected($payOrderId): void
    {
        $this->assertFalse(ReturnParams::transactionMatches(self::TRANSACTION_ID, $payOrderId));
    }

    /**
     * @return array
     */
    public static function invalidStoredTransactionIdProvider(): array
    {
        return [
            'missing' => [null],
            'empty string' => [''],
            'boolean true' => [true],
            'array' => [[self::TRANSACTION_ID]],
        ];
    }

    /**
     * @dataProvider invalidStoredTransactionIdProvider
     * @param mixed $storedTransactionId
     */
    public function testOrderWithoutValidStoredTransactionIdIsRejected($storedTransactionId): void
    {
        $this->assertFalse(ReturnParams::transactionMatches($storedTransactionId, self::TRANSACTION_ID));
        $this->assertFalse(ReturnParams::transactionMatches($storedTransactionId, ''));
        $this->assertFalse(ReturnParams::transactionMatches($storedTransactionId, true));
    }

    public function testGetStringOnlyReturnsStrings(): void
    {
        $params = [
            'string' => 'abc',
            'empty' => '',
            'bool' => true,
            'int' => 100,
            'array' => ['abc'],
            'null' => null,
        ];

        $this->assertSame('abc', ReturnParams::getString($params, 'string'));
        $this->assertSame('', ReturnParams::getString($params, 'empty'));
        $this->assertNull(ReturnParams::getString($params, 'bool'));
        $this->assertNull(ReturnParams::getString($params, 'int'));
        $this->assertNull(ReturnParams::getString($params, 'array'));
        $this->assertNull(ReturnParams::getString($params, 'null'));
        $this->assertNull(ReturnParams::getString($params, 'missing'));
    }

    public function testGetScalarAcceptsStringsAndIntegers(): void
    {
        $params = [
            'string' => '100',
            'int' => 100,
            'bool' => true,
            'float' => 1.5,
            'array' => ['100'],
            'null' => null,
        ];

        $this->assertSame('100', ReturnParams::getScalar($params, 'string'));
        $this->assertSame('100', ReturnParams::getScalar($params, 'int'));
        $this->assertNull(ReturnParams::getScalar($params, 'bool'));
        $this->assertNull(ReturnParams::getScalar($params, 'float'));
        $this->assertNull(ReturnParams::getScalar($params, 'array'));
        $this->assertNull(ReturnParams::getScalar($params, 'null'));
        $this->assertNull(ReturnParams::getScalar($params, 'missing'));
    }

    public function testJsonBooleanIdFromAttackPayloadIsRejected(): void
    {
        $params = json_decode('{"entityid": 2, "id": true, "statusCode": 100}', true);

        $payOrderId = ReturnParams::getString($params, 'id');

        $this->assertNull($payOrderId);
        $this->assertFalse(ReturnParams::transactionMatches(self::TRANSACTION_ID, $payOrderId));
        $this->assertSame('2', ReturnParams::getScalar($params, 'entityid'));
    }

    public function testRegularGetReturnIsAccepted(): void
    {
        $params = ['entityid' => '2', 'id' => self::TRANSACTION_ID, 'statusCode' => '100', 'statusAction' => 'PAID'];

        $payOrderId = ReturnParams::getString($params, 'id');

        $this->assertTrue(ReturnParams::transactionMatches(self::TRANSACTION_ID, $payOrderId));
        $this->assertSame('PAID', ReturnParams::getString($params, 'statusAction'));
        $this->assertSame('100', ReturnParams::getScalar($params, 'statusCode'));
    }
}
