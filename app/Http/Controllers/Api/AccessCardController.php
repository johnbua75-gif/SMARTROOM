<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAccessCardRequest;
use App\Http\Requests\Api\UpdateAccessCardRequest;
use App\Http\Resources\AccessCardResource;
use App\Models\AccessCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessCardController extends Controller
{
    public function index(Request $request)
    {
        $device = $request->attributes->get('device');
        $doorApi = $request->attributes->get('door_api', false);
        if (($device || $doorApi) && ! $request->filled('rfid_uid')) {
            abort(422, 'A scanned RFID UID is required for device lookup.');
        }

        $query = AccessCard::query()->with(['user', 'classroom']);

        if ($doorApi) {
            $classroomIds = $request->attributes->get('door_classroom_ids', []);
            $query->where(function ($classroomQuery) use ($classroomIds): void {
                $classroomQuery->whereNull('classroom_id')
                    ->orWhereIn('classroom_id', $classroomIds);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('classroom_id')) {
            if (! $request->filled('rfid_uid')) {
                $query->where('classroom_id', $request->integer('classroom_id'));
            }
        }

        if ($request->filled('rfid_uid')) {
            $query->whereNormalizedRfidUid((string) $request->string('rfid_uid'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('card_number', 'like', "%{$search}%")
                    ->orWhere('rfid_uid', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $cards = $device || $doorApi
            ? $query->latest('id')->limit(1)->get()
            : $query->latest('id')->paginate($request->integer('per_page', 20));

        return AccessCardResource::collection($cards);
    }

    public function store(StoreAccessCardRequest $request): AccessCardResource
    {
        $card = AccessCard::create($request->validated())->load(['user', 'classroom']);

        return new AccessCardResource($card);
    }

    public function show(AccessCard $accessCard): AccessCardResource
    {
        return new AccessCardResource($accessCard->load(['user', 'classroom', 'accessLogs']));
    }

    public function update(UpdateAccessCardRequest $request, AccessCard $accessCard): AccessCardResource
    {
        $accessCard->update($request->validated());

        return new AccessCardResource($accessCard->fresh()->load(['user', 'classroom']));
    }

    public function destroy(AccessCard $accessCard): JsonResponse
    {
        $accessCard->delete();

        return response()->json(['message' => 'Access card deleted successfully.']);
    }
}
