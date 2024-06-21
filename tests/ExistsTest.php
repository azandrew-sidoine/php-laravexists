<?php

use Drewlabs\LaravExists\ExistanceVerifier;
use PHPUnit\Framework\TestCase;

use function Drewlabs\LaravExists\Proxy\Exists;

class ExistsTest extends TestCase
{
    public function test_exists_validate_calls_callback_on_failure()
    {
        // Initialize
        $count = 0;
        $verifier = $this->createMock(ExistanceVerifier::class);
        $verifier->method('exists')
                ->willReturn(false);
        $rule = Exists($verifier);

        // Act
        $rule->validate('attribute', 'value', function() use (&$count) {
            $count++;
        });

        // Assert
        $this->assertEquals(1, $count);
    }

    
    public function test_exists_validate_does_not_calls_callback_on_success()
    {
        // Initialize
        $count = 0;
        $verifier = $this->createMock(ExistanceVerifier::class);
        $verifier->method('exists')
                ->willReturn(true);
        $rule = Exists($verifier);

        // Act
        $rule->validate('attribute', 'value', function() use (&$count) {
            $count++;
        });

        // Assert
        $this->assertEquals(0, $count);
    }
}