<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Services\CityService;
use App\Http\Requests\CityRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Support\Facades\Log;


class CityController extends AdminController
{

    private CityService $cityService;

    public function __construct(CityService $city)
    {
        parent::__construct();
        $this->cityService = $city;
        $this->middleware(['permission:settings'])->only('store', 'update', 'destroy', 'show');
    }

    public function index(PaginateRequest $request)  {
        try {
            return CityResource::collection($this->cityService->list($request));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function store(CityRequest $request) 
    {
        try {
            return new CityResource($this->cityService->store($request));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(CityRequest $request, City $city) {
        try {
            return new CityResource($this->cityService->update($request, $city));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(City $city) {
        try {
            $this->cityService->destroy($city);
            return response('', 202);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function citiesByState($stateName)
    {
        try {
            return CityResource::collection($this->cityService->citiesByState($stateName));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }
}
