<?php

namespace Drewlabs\LaravExists\Tests;

use Drewlabs\LaravExists\ExistanceVerifier;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Drewlabs\LaravExists\Proxy\Exists;

class ExistsTest extends TestCase
{

    #[AllowMockObjectsWithoutExpectations]
    public function test_exists_validate_calls_callback_on_failure()
    {
        // Initialize
        $count = 0;
        $verifier = $this->createMock(ExistanceVerifier::class);
        $verifier->method('exists')
            ->willReturn(false);
        $rule = Exists($verifier);

        // Act
        $rule->validate('attribute', 'value', function () use (&$count) {
            $count++;
        });

        // Assert
        $this->assertEquals(1, $count);
    }


    #[AllowMockObjectsWithoutExpectations]
    public function test_exists_validate_does_not_calls_callback_on_success()
    {
        // Initialize
        $count = 0;
        $verifier = $this->createMock(ExistanceVerifier::class);
        $verifier->method('exists')
            ->willReturn(true);
        $rule = Exists($verifier);

        // Act
        $rule->validate('attribute', 'value', function () use (&$count) {
            $count++;
        });

        // Assert
        $this->assertEquals(0, $count);
    }


    public function test_exists_throws_runtime_exception_if_class_is_not_supported()
    {
        // assert
        $this->expectException(InvalidArgumentException::class);

        // Initialize
        $count = 0;
        $rule = Exists(new stdClass);


        // Act
        $rule->validate('attribute', 'value', function () use (&$count) {
            $count++;
        });
    }
}
