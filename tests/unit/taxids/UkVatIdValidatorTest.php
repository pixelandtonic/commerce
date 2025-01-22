<?php

namespace tests\unit;

use craft\commerce\ukvatidvalidator\taxidvalidators\UkVatIdValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class UkVatIdValidatorTest extends TestCase
{
    private UkVatIdValidator $validator;
    private Client $guzzleClientMock;

    protected function setUp(): void
    {
        // Create a mock for the Guzzle client
        $this->guzzleClientMock = $this->createMock(Client::class);

        // Create the GbVatIdValidator instance with the mocked Guzzle client
        $this->validator = new UkVatIdValidator($this->guzzleClientMock);
    }

    public function testDisplayName(): void
    {
        $this->assertEquals('UK VAT ID', $this->validator::displayName());
    }

    public function testValidateFormat(): void
    {
        $this->assertTrue($this->validator->validateFormat('123456789')); // Valid 9 characters
        $this->assertTrue($this->validator->validateFormat('123456789012')); // Valid 12 characters
        $this->assertFalse($this->validator->validateFormat('12345')); // Invalid
        $this->assertFalse($this->validator->validateFormat('')); // Invalid
    }

    public function testValidateExistence(): void
    {
        // Mock the token retrieval
        $this->guzzleClientMock->method('post')
            ->willReturn(new Response(200, [], json_encode(['access_token' => 'mock_access_token'])));

        // Mock the response for the VAT number lookup
        $this->guzzleClientMock->method('get')
            ->willReturn(new Response(200));

        $this->assertTrue($this->validator->validateExistence('123456789'));
    }

    public function testValidateExistenceFails(): void
    {
        // Mock the token retrieval
        $this->guzzleClientMock->method('post')
            ->willReturn(new Response(200, [], json_encode(['access_token' => 'mock_access_token'])));

        // Mock the response for the VAT number lookup with a 404 error
        $this->guzzleClientMock->method('get')
            ->willReturn(new Response(404));

        $this->assertFalse($this->validator->validateExistence('invalid_vat_id'));
    }
}
