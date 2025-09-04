<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ErrorController extends Controller
{
    public function handleError(Request $request)
    {

        $errorMessage = $request->get('errorMessage', 'An error occurred');

        // Log the error message
        Log::info('Error occurred: ' . $errorMessage);

        return view('userpanel.wentWrong', compact('errorMessage'));
    }

     public function badRequest($message = "The request was invalid or malformed."): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 400,
                'type' => 'BAD_REQUEST',
                'message' => $message,
            ]
        ], 400);
    }

    public function internalServerError($message = "An unexpected error occurred on the server."): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 500,
                'type' => 'INTERNAL_SERVER_ERROR',
                'message' => $message,
            ]
        ], 500);
    }

    public function unauthorized($message = "Authentication failed or invalid credentials."): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 401,
                'type' => 'UNAUTHORIZED',
                'message' => $message,
            ]
        ], 401);
    }

    public function accessDenied($message = "You do not have permission to perform this action."): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 403,
                'type' => 'ACCESS_DENIED',
                'message' => $message,
            ]
        ], 403);
    }

    public function conflict($message = "The request could not be completed due to a conflict with the current state of the resource."): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 409,
                'type' => 'CONFLICT',
                'message' => $message,
            ]
        ], 409);
    }

     public function recordNotFound($message = "The requested resource or record was not found."): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 404,
                'type' => 'RECORD_NOT_FOUND',
                'message' => $message,
            ]
        ], 404);
    }

    public function unprocessableEntity($message = "The request was valid but could not be processed due to semantic errors."): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 422,
                'type' => 'UNPROCESSABLE_ENTITY',
                'message' => $message,
            ]
        ], 422);
    }
}
