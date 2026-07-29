<?php

namespace App\Http\Controllers;

use App\Events\LoginApproved;
use App\Events\LoginRejected;
use App\Models\LoginSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LoginSessionController extends Controller
{
    public function create()
    {
        $session = LoginSession::create([
            'uuid' => Str::uuid(),
            'user_agent' => request()->header('User-Agent'),
            'ip_address' => request()->ip(),
            'expires_at' => now()->addMinutes(5),
        ]);

        return response()->json([
            'uuid' => $session->uuid,
            'url' => url("/qr/{$session->uuid}"),
        ]);
    }

    public function approve($uuid)
    {
        $session = LoginSession::where('uuid', $uuid)->firstOrFail();

        if ($session->expires_at->isPast()) {
            return response()->json([
                'ok' => false,
                'reason' => 'expired',
            ], 410);
        }

        if ($session->status !== 'waiting') {
            return response()->json([
                'ok' => false,
                'reason' => 'already_processed',
            ], 409);
        }

        $user = auth()->user();

        $session->update([
            'status' => 'approved',
            'user_id' => $user->id,
        ]);

        broadcast(new LoginApproved($uuid));

        return response()->json(['ok' => true]);
    }

    public function show(Request $request, $uuid)
    {
        $session = LoginSession::where('uuid', $uuid)->firstOrFail();

        return Inertia::render('Qr/Approve', [
            'uuid' => $uuid,
        ]);
    }

    public function consume($uuid)
    {
        $session = LoginSession::where('uuid', $uuid)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        if (! $session->user) {
            return response()->json([
                'ok' => false,
                'reason' => 'user_not_found',
            ], 404);
        }
        Auth::login($session->user);
        request()->session()->regenerate();

        $session->update(['status' => 'consumed']);
        $session->delete();

        return response()->json(['ok' => true]);
    }

    public function destroy(string $uuid)
    {
        $deleted = LoginSession::where('uuid', $uuid)
            ->delete();

        if (! $deleted) {
            return response()->json([
                'ok' => false,
                'reason' => 'not_found',
            ], 404);
        }

        return response()->noContent();
    }

    public function get_info_from_uuid(string $uuid)
    {
        $uuid_qr_session = LoginSession::where('uuid', $uuid)->firstOrFail();
        $ip_address = $uuid_qr_session->ip_address;
        $user_agent = $uuid_qr_session->user_agent;

        return response()->json(
            [
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
            ]
        );
    }

    public function reject(string $uuid)
    {
        $session = LoginSession::where('uuid', $uuid)->firstOrFail();

        if($session->expires_at->isPast()) {
            return response()->json([
                'ok' => false,
                'reason' => 'expired',
            ], 410);
        }

        if ($session->status !== 'waiting') {
            return response()->json([
               'ok' => false,
               'reason' => 'already_processed',
            ], 409);
        }

        $session->update([
           'status' => 'rejected',
        ]);

        broadcast(new LoginRejected($uuid));

        return response()->json([
            'ok' => true,
        ]);
    }
}
