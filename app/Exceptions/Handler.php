<?php
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\ClientException;

try {
    $response = Http::post('https://sia-production.up.railway.app/api/users', $userData);
    $response->throw(); // This will throw an exception for 4xx/5xx errors
    // Process your successful response here
} catch (ClientException $e) {
    // Dump the raw error response to see what you're getting
    dd($e->getResponse()->getBody()->getContents());
    // At this point, you've confirmed a ClientException with the 422 response
    // Now, let the exception bubble up to your Handler
    throw $e;
} catch (\Exception $e) {
    // Handle other potential exceptions
    dd("An unexpected error occurred: " . $e->getMessage());
}
