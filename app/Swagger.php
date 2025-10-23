<?php

namespace App;

/**
 * @OA\Info(
 *     title="Banque API",
 *     version="1.0.0",
 *     description="API documentation for Banque application"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8081",
 *     description="API server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class Swagger {}