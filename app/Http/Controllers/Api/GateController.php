<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\RfidUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GateController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uid' => ['required', 'string', 'max:50'],
        ]);

        $uid  = RfidUser::normalizeUid($data['uid']);
        $user = RfidUser::where('uid', $uid)->first();

        $granted = $user !== null && $user->is_active;

        AccessLog::create([
            'rfid_user_id' => $user?->id,
            'uid'          => $uid,
            'status'       => $granted ? 'granted' : 'denied',
            'ip_address'   => $request->ip(),
        ]);

        return response()->json([
            'status'   => $granted ? 'granted' : 'denied',
            'name'     => $granted ? $user->name : null,
            'position' => $granted ? $user->position : null,
        ]);
    }
}