<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     title="AssetForge Enterprise ITAM API",
 *     version="1.0.0",
 *     description="API documentation for the AssetForge Enterprise ITAM platform.",
 *     @OA\Contact(email="support@assetforge.com")
 * )
 * @OA\Server(url="http://localhost:8000/api/v1")
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum"
 * )
 */
#[OA\Info(
    title: 'AssetForge Enterprise ITAM API',
    version: '1.0.0',
    description: 'API documentation for the AssetForge Enterprise ITAM platform.',
    contact: new OA\Contact(email: 'support@assetforge.com')
)]
#[OA\Server(url: 'http://localhost:8000/api/v1')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum'
)]
class OpenApiDoc
{
}
