<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'E-commerce API',
    description: 'API REST segura para gestión de productos, órdenes y pagos mediante Stripe.'
)]

#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
class OpenApi
{
}