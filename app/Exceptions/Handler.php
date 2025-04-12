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
        // --- If the request expects JSON (typical for APIs) ---
        if ($request->expectsJson()) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR; // Default to 500
            $responseData = [
                'error' => 'Internal Server Error',
                'code' => $statusCode
            ];

            // --- Handle ModelNotFoundException ---
            if ($exception instanceof ModelNotFoundException) {
                $statusCode = Response::HTTP_NOT_FOUND; // 404
                // Extract model name for a slightly more specific message (optional)
                // $modelName = strtolower(class_basename($exception->getModel()));
                $responseData = [
                    'error' => 'The requested resource was not found.', // Generic message
                    // 'error' => "Does not exist any instance of {$modelName} with the given id", // More specific
                    // 'site' => 1, // Your custom fields if needed
                    'code' => $statusCode
                ];
            }
            // --- Handle ValidationException ---
            elseif ($exception instanceof ValidationException) {
                $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY; // 422
                $responseData = [
                    'message' => 'The given data was invalid.',
                    'errors' => $exception->validator->errors()->getMessages(), // Get validation messages
                    'code' => $statusCode
                ];
            }
            // --- Handle AuthenticationException ---
            elseif ($exception instanceof AuthenticationException) {
                $statusCode = Response::HTTP_UNAUTHORIZED; // 401
                $responseData = ['error' => 'Unauthenticated.', 'code' => $statusCode];
            }
            // --- Handle AuthorizationException ---
            elseif ($exception instanceof AuthorizationException) {
                $statusCode = Response::HTTP_FORBIDDEN; // 403
                // Use the exception message if provided, otherwise a generic one
                $responseData = ['error' => $exception->getMessage() ?: 'This action is unauthorized.', 'code' => $statusCode];
            }
            // --- Handle generic HttpException (used by abort()) ---
            elseif ($exception instanceof HttpException) {
                $statusCode = $exception->getStatusCode();
                 // Use the exception message if provided (> 400), otherwise use standard status text
                $responseData = [
                    'error' => $statusCode >= 400 && $exception->getMessage() ? $exception->getMessage() : Response::$statusTexts[$statusCode],
                    'code' => $statusCode
                ];
            }
            // --- Add more 'elseif' blocks here for other custom exceptions ---

            // --- Default handling for other Exceptions (500 errors) ---
            // Only add debug details if APP_DEBUG is true and it's likely a 500 error
            elseif (env('APP_DEBUG', false) && $statusCode === 500) {
                $responseData['debug_details'] = [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    // Be careful about exposing the full trace in production environments
                    // 'trace' => $exception->getTraceAsString(),
                ];
            }

            // Return the JSON response
            return new JsonResponse($responseData, $statusCode);
        }
        // --- End JSON response handling ---


        // For non-JSON requests, fallback to the default Lumen/Laravel rendering
        return parent::render($request, $exception);
    }
}
