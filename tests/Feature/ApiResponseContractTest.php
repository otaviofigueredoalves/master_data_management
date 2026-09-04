<?php

namespace Tests\Feature;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

/**
 * Contract tests for the ApiResponse helpers.
 *
 * ApiResponse::noContent() has no production caller yet, so this pins the
 * response envelope it produces (204 + success payload) — a future endpoint
 * that wants a body-less 204 can rely on this contract.
 */
class ApiResponseContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_content_returns_204_with_success_envelope(): void
    {
        $response = (new ApiResponseProbe)->respondNoContent('Recurso removido.');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(204, $response->getStatusCode());

        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame('Recurso removido.', $payload['message']);
        $this->assertNull($payload['data']);
    }

    public function test_no_content_omits_message_when_null(): void
    {
        $payload = (new ApiResponseProbe)->respondNoContent()->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertNull($payload['message']);
        $this->assertNull($payload['data']);
    }
}

/**
 * Exposes the protected trait helpers for contract testing.
 */
final class ApiResponseProbe
{
    use ApiResponse;

    public function respondNoContent(?string $message = null): JsonResponse
    {
        return $this->noContent($message);
    }
}
