<?php
namespace App\Http\Controllers;

use Illuminate\Http\Response;        // Response Components
use App\Traits\ApiResponser;        // <-- use to standardize our code for API response
use Illuminate\Http\Request;        // <-- handling HTTP requests in Lumen
use App\Services\User1Service;      // User1 Services

class User1Controller extends Controller 
{
    // Use to add your Traits ApiResponser
    use ApiResponser;

    /**
     * The service to consume the User1 Microservice
     * @var User1Service
     */
    public $user1Service;

    /**
     * Create a new controller instance
     * @return void
     */
    public function __construct(User1Service $user1Service)
    {
        $this->user1Service = $user1Service;
    }

    /**
     * Return the list of users
     * @return Illuminate\Http\Response
     */
    public function index()
    {
        return $this->successResponse($this->user1Service->obtainUsers1());
    }

    /**
     * Create a new user record
     * @param Request $request
     * @return Illuminate\Http\Response
     */
    public function add(Request $request)
    {
        return $this->successResponse(
            $this->user1Service->createUser1($request->all()),
            Response::HTTP_CREATED
        );
    }

    /**
     * Obtain and show one user by ID
     * @param int $id
     * @return Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->successResponse($this->user1Service->getUser1($id));
    }

    /**
     * Update an existing user record
     * @param Request $request
     * @param int $id
     * @return Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        return $this->successResponse(
            $this->user1Service->updateUser1($id, $request->all())
        );
    }

    /**
     * Remove an existing user
     * @param int $id
     * @return Illuminate\Http\Response
     */
    public function delete($id)
    {
        return $this->successResponse($this->user1Service->deleteUser1($id));
    }
}
