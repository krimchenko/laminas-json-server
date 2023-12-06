<?php

declare(strict_types=1);

namespace LaminasTest\Json\Server;

use Laminas\Json;
use Laminas\Json\Server;
use PHPUnit\Framework\TestCase;
use stdClass;

use function range;

class ErrorTest extends TestCase
{
    /** @var Server\Error */
    protected $error;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->error = new Server\Error();
    }

    public function testCodeShouldBeErrOtherByDefault(): void
    {
        self::assertEquals(Server\Error::ERROR_OTHER, $this->error->getCode());
    }

    public function testSetCodeShouldCastToInteger(): void
    {
        $this->error->setCode('-32700');
        self::assertEquals(-32700, $this->error->getCode());
    }

    public function testCodeShouldBeLimitedToStandardIntegers(): void
    {
        foreach ([null, true, 'foo', [], new stdClass(), 2.0] as $code) {
            $this->error->setCode($code);
            self::assertEquals(Server\Error::ERROR_OTHER, $this->error->getCode());
        }
    }

    public function testCodeShouldAllowArbitraryAppErrorCodesInXmlRpcErrorCodeRange(): void
    {
        foreach (range(-32099, -32000) as $code) {
            $this->error->setCode($code);
            self::assertEquals($code, $this->error->getCode());
        }
    }

    public static function arbitraryErrorCodes(): array
    {
        return [
            '1000'  => [1000],
            '404'   => [404],
            '-3000' => [-3000],
        ];
    }

    /**
     * @dataProvider arbitraryErrorCodes
     */
    public function testCodeShouldAllowArbitraryErrorCode(int $code): void
    {
        $this->error->setCode($code);
        self::assertEquals($code, $this->error->getCode());
    }

    public function testMessageShouldBeNullByDefault(): void
    {
        self::assertNull($this->error->getMessage());
    }

    public function testSetMessageShouldCastToString(): void
    {
        foreach ([true, 2.0, 25] as $message) {
            $this->error->setMessage($message);
            self::assertEquals((string) $message, $this->error->getMessage());
        }
    }

    public function testSetMessageToNonScalarShouldSilentlyFail(): void
    {
        foreach ([[], new stdClass()] as $message) {
            $this->error->setMessage($message);
            self::assertNull($this->error->getMessage());
        }
    }

    public function testDataShouldBeNullByDefault(): void
    {
        self::assertNull($this->error->getData());
    }

    public function testShouldAllowArbitraryData(): void
    {
        foreach ([true, 'foo', 2, 2.0, [], new stdClass()] as $datum) {
            $this->error->setData($datum);
            self::assertEquals($datum, $this->error->getData());
        }
    }

    public function testShouldBeAbleToCastToArray(): void
    {
        $this->setupError();
        $array = $this->error->toArray();
        $this->validateArray($array);
    }

    public function testShouldBeAbleToCastToJSON(): void
    {
        $this->setupError();
        $json = $this->error->toJSON();
        $this->validateArray(Json\Json::decode($json, Json\Json::TYPE_ARRAY));
    }

    public function testCastingToStringShouldCastToJSON(): void
    {
        $this->setupError();
        $json = $this->error->__toString();
        $this->validateArray(Json\Json::decode($json, Json\Json::TYPE_ARRAY));
    }

    public function setupError(): void
    {
        $this->error->setCode(Server\Error::ERROR_OTHER)
                    ->setMessage('Unknown Error')
                    ->setData(['foo' => 'bar']);
    }

    public function validateArray(array $error): void
    {
        self::assertIsArray($error);
        self::assertArrayHasKey('code', $error);
        self::assertArrayHasKey('message', $error);
        self::assertArrayHasKey('data', $error);

        self::assertIsInt($error['code']);
        self::assertIsString($error['message']);
        self::assertIsArray($error['data']);

        self::assertEquals($this->error->getCode(), $error['code']);
        self::assertEquals($this->error->getMessage(), $error['message']);
        self::assertSame($this->error->getData(), $error['data']);
    }
}
