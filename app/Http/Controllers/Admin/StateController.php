<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\StateSimpleResource;
use Exception;
use App\Services\StateService;
use App\Http\Requests\StateRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Resources\StateResource;
use App\Models\State;
use Illuminate\Support\Facades\Log;


class StateController extends AdminController
{

    private StateService $stateService;

    public function __construct(StateService $state)
    {
        parent::__construct();
        $this->stateService = $state;
        $this->middleware(['permission:settings'])->only('store', 'update', 'destroy', 'show');
    }

    public function index(PaginateRequest $request)
    {
        try {
            return StateResource::collection($this->stateService->list($request));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function simpleLists(PaginateRequest $request)
    {
        try {
            return StateSimpleResource::collection($this->stateService->list($request));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function store(StateRequest $request)
    {
        try {
            return new StateResource($this->stateService->store($request));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(StateRequest $request, State $state)
    {
        try {
            return new StateResource($this->stateService->update($request, $state));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(State $state)
    {
        try {
            $this->stateService->destroy($state);
            return response('', 202);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function statesByCountry($countryName)
    {
        try {
            return StateResource::collection($this->stateService->statesByCountry($countryName));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }
}
