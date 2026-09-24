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
        $query = AccessCard::query()->with(['user', 'classroom']);

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
            $rawUid = strtolower(trim((string) $request->string('rfid_uid')));
            $normalizedUid = preg_replace('/^rfid[-_]?/', '', $rawUid) ?? $rawUid;
            $compactUid = preg_replace('/[^a-z0-9]/', '', $normalizedUid) ?? $normalizedUid;
            $colonUid = implode(':', str_split($compactUid, 2));
            $hyphenUid = implode('-', str_split($compactUid, 2));
            $query->where(function ($uidQuery) use ($normalizedUid, $compactUid, $colonUid, $hyphenUid): void {
                $uidQuery->whereIn('rfid_uid', [
                    $normalizedUid,
                    strtoupper($normalizedUid),
                    $compactUid,
                    strtoupper($compactUid),
                    $colonUid,
                    strtoupper($colonUid),
                    $hyphenUid,
                    strtoupper($hyphenUid),
                    'RFID-'.$normalizedUid,
                    'RFID-'.strtoupper($normalizedUid),
                    'RFID-'.$colonUid,
                    'RFID-'.strtoupper($colonUid),
                ]);
            });
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

        $cards = $query->latest('id')->paginate($request->integer('per_page', 20));

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
