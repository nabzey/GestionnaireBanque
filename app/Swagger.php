<?php

namespace App;

/**
 * @OA\Info(
 *     title="Banque API Zeynab-Ba",
 *     version="1.0.0",
 *     description="API documentation for Banque application Zeynab-Ba"
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
 *
 * @OA\Schema(
 *     schema="ComptesResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="_links", ref="#/components/schemas/Links"),
 *     @OA\Property(property="_embedded", type="object",
 *         @OA\Property(property="comptes", type="array", @OA\Items(ref="#/components/schemas/CompteWithLinks"))
 *     ),
 *     @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
 * )
 *
 * @OA\Schema(
 *     schema="CompteWithLinks",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *     @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
 *     @OA\Property(property="type", type="string", enum={"cheque", "courant", "epargne"}, example="epargne"),
 *     @OA\Property(property="solde", type="number", format="float", example=1250000),
 *     @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="bloque"),
 *     @OA\Property(property="motifBlocage", type="string", nullable=true, example="Inactivité de 30+ jours"),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     ),
 *     @OA\Property(property="_links", type="object",
 *         @OA\Property(property="self", type="object",
 *             @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes/{id}"),
 *             @OA\Property(property="method", type="string", example="GET"),
 *             @OA\Property(property="rel", type="string", example="self")
 *         ),
 *         @OA\Property(property="update", type="object",
 *             @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes/{id}"),
 *             @OA\Property(property="method", type="string", example="PUT"),
 *             @OA\Property(property="rel", type="string", example="update")
 *         ),
 *         @OA\Property(property="delete", type="object",
 *             @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes/{id}"),
 *             @OA\Property(property="method", type="string", example="DELETE"),
 *             @OA\Property(property="rel", type="string", example="delete")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *     @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
 *     @OA\Property(property="type", type="string", enum={"cheque", "courant", "epargne"}, example="epargne"),
 *     @OA\Property(property="solde", type="number", format="float", example=1250000),
 *     @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="bloque"),
 *     @OA\Property(property="motifBlocage", type="string", nullable=true, example="Inactivité de 30+ jours"),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     @OA\Property(property="currentPage", type="integer", example=1),
 *     @OA\Property(property="totalPages", type="integer", example=3),
 *     @OA\Property(property="totalItems", type="integer", example=25),
 *     @OA\Property(property="itemsPerPage", type="integer", example=10),
 *     @OA\Property(property="hasNext", type="boolean", example=true),
 *     @OA\Property(property="hasPrevious", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="Links",
 *     type="object",
 *     @OA\Property(property="self", type="string", example="/api/v1/zeynab-ba/comptes?page=1&limit=10"),
 *     @OA\Property(property="next", type="string", nullable=true, example="/api/v1/zeynab-ba/comptes?page=2&limit=10"),
 *     @OA\Property(property="previous", type="string", nullable=true, example=null),
 *     @OA\Property(property="first", type="string", example="/api/v1/zeynab-ba/comptes?page=1&limit=10"),
 *     @OA\Property(property="last", type="string", example="/api/v1/zeynab-ba/comptes?page=3&limit=10")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Erreur de validation"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 */
class Swagger {}