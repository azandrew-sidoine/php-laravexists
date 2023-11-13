<?php

use Drewlabs\LaravExists\ExistanceVerifier;
use Drewlabs\LaravExists\Exists;
use PHPUnit\Framework\TestCase;

class ExistsTest extends TestCase
{
    public function test_exists_validate_calls_callback_on_failure()
    {
        // Initialize
        $verifier = $this->createMock(ExistanceVerifier::class);
        $verifier->method('exists')
                ->willReturn(false);
        $rule = Exists::create($verifier);

        // Act
        $result = $rule->passes('attribute', 'value');

        // Assert
        $this->assertEquals(false, $result);
    }

    
    public function test_exists_validate_does_not_calls_callback_on_success()
    {
        // Initialize
        $verifier = $this->createMock(ExistanceVerifier::class);
        $verifier->method('exists')
                ->willReturn(true);
        $rule = Exists::create($verifier);

        // Act
        $result = $rule->passes('attribute', 'value');

        // Assert
        $this->assertEquals(true, $result);
    }
}