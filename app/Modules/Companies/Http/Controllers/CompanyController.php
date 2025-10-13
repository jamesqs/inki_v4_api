<?php

namespace App\Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Companies\Models\Company;
use App\Modules\Companies\Http\Requests\CompanyRequest;
use App\Modules\Companies\Http\Resources\CompanyResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Company::query();

        // If you need to search by specific fields
        if ($name = request('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        // if the get parameter raw is present, return all locations without pagination
        if (request()->has('raw')) {
            $Companies = $query->get();
            return CompanyResource::collection($Companies);
        }

        $Companies = $query->paginate();
        return CompanyResource::collection($Companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CompanyRequest $request): CompanyResource
    {
        $Company = Company::create($request->validated());
        return new CompanyResource($Company);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $Company): CompanyResource
    {
        return new CompanyResource($Company);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyRequest $request, Company $Company): CompanyResource
    {
        $Company->update($request->validated());
        return new CompanyResource($Company);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $Company): Response
    {
        $Company->delete();
        return response()->noContent();
    }
}