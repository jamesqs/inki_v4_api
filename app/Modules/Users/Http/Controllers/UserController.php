<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Models\User;
use App\Modules\Users\Http\Requests\UserRequest;
use App\Modules\Users\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = User::query();

        // If you need to search by specific fields
        if ($name = request('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        // if the get parameter raw is present, return all locations without pagination
        if (request()->has('raw')) {
            $Users = $query->get();
            return UserResource::collection($Users);
        }

        $Users = $query->paginate();
        return UserResource::collection($Users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request): UserResource
    {
        $User = User::create($request->validated());
        return new UserResource($User);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $User): UserResource
    {
        return new UserResource($User);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $User): UserResource
    {
        $User->update($request->validated());
        return new UserResource($User);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $User): Response
    {
        $User->delete();
        return response()->noContent();
    }
}