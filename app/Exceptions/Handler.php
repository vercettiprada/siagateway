<?php

namespace App\Exceptions;

use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use GuzzleHttp\Exception\ClientException; // Import the Guzzle exception

class Handler extends ExceptionHandler
{
    use ApiResponser;
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
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
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
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
            if ($exception instanceof HttpException) {
                $code = $exception->getStatusCode();
                $message = Response::$statusTexts[$code];
                return $this->errorResponse($message, $code);
            }

            if ($exception instanceof ModelNotFoundException) {
                $model = strtolower(class_basename($exception->getModel()));
                return $this->errorResponse("Does not exist any instance of {$model} with the given id", Response::HTTP_NOT_FOUND);
            }

            if ($exception instanceof ValidationException) {
                $errors = $exception->validator->errors()->getMessages();
                return $this->errorResponse($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($exception instanceof AuthorizationException) {
                return $this->errorResponse($exception->getMessage(), Response::HTTP_FORBIDDEN);
            }

            // Handle the ClientException from the external API
            if ($exception instanceof ClientException) {
                $response = $exception->getResponse();
                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                $errorData = json_decode($body, true); // Decode the JSON error

                \Log::error("External API Error ({$statusCode}): " . $body);

                return $this->errorResponse(
                    'Failed to process request due to an error with an external service.',
                    $statusCode,
                    $errorData // Optionally include the external error details
                );
            }

            if (env('APP_DEBUG', false)) {
                return parent::render($request, $exception);
            }

            return $this->errorResponse('Unexpected error. Try later', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return parent::render($request, $exception);
    }
}
