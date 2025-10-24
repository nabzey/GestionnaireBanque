<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/user",
     *     summary="Get authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User data",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     )
     * )
     */
    public function getUser(Request $request)
    {
        return $request->user();
    }
}