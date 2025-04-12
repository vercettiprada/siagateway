<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException; // <-- Import added
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse; // <-- Import added
use Illuminate\Http\Response; // <-- Import added
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use GuzzleHttp\Exception\ClientException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        AuthenticationException::class, // <-- Added
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $exception)
    {
        // You can add custom logging here (e.g., to Sentry, Flare, etc.)
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
{
    if ($request->expectsJson()) {
        // ... (your existing JSON error handling code) ...

        // --- Handle Guzzle ClientException (for errors from external APIs) ---
        if ($exception instanceof ClientException) {
            $response = $exception->getResponse();
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true); // Decode the JSON error

            \Log::error("External API Error ({$statusCode}): " . $body);

            return new JsonResponse([
                'message' => 'Failed to process request due to an error with an external service.',
                'external_error' => $responseData,
                'code' => $statusCode,
            ], $statusCode);
        }

        // --- Default handling for other Exceptions (500 errors) ---
        elseif (env('APP_DEBUG', false)) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $responseData = [
                'error' => 'Internal Server Error',
                'code' => $statusCode,
                'debug_details' => [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
            ];
            return new JsonResponse($responseData, $statusCode);
        } else {
            return new JsonResponse(['error' => 'Internal Server Error', 'code' => Response::HTTP_INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    return parent::render($request, $exception);
}

