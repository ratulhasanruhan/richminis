<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Services\CountryService;
use App\Http\Requests\CountryRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Resources\CountryResource;
use App\Models\Country;


class CountryController extends AdminController
{

    private CountryService $countryService;

    public function __construct(CountryService $country)
    {
        parent::__construct();
        $this->countryService = $country;
        $this->middleware(['permission:settings'])->only('store', 'update', 'destroy', 'show');
    }

    public function index(PaginateRequest $request)  {
        try {
            return CountryResource::collection($this->countryService->list($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function store(CountryRequest $request) 
    {
        try {
            return new CountryResource($this->countryService->store($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(CountryRequest $request, Country $country) {
        try {
            return new CountryResource($this->countryService->update($request, $country));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(Country $country) {
        try {
            $this->countryService->destroy($country);
            return response('', 202);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
