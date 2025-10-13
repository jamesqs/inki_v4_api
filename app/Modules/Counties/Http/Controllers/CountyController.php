<?php

namespace App\Modules\Counties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Counties\Models\County;
use App\Modules\Counties\Http\Resources\CountyResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Modules\Counties\Http\Requests\CountyRequest;
use Illuminate\Http\Response;

class CountyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = County::query();

        // If you need to search by specific fields
        if ($name = request('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        // if the get parameter raw is present, return all locations without pagination
        if (request()->has('raw')) {
            $counties = $query->get();
            return CountyResource::collection($counties);
        }

        // Check if 'raw' parameter exists
        if (request()->has('raw')) {
            $counties = $query->get();
        } else {
            $counties = $query->paginate();
        }

        return CountyResource::collection($counties);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CountyRequest $request): CountyResource
    {
        $county = County::create($request->validated());
        return new CountyResource($county);
    }
}
