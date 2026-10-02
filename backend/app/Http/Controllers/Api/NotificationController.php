<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Notifications in-app de l'utilisateur connecté (TDR §5.1). */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);
    }

    public function marquerLu(Request $request, int $notification)
    {
        $request->user()->notifications()
            ->whereKey($notification)
            ->update(['lu_at' => now()]);

        return response()->json(['message' => 'ok']);
    }

    public function toutMarquerLu(Request $request)
    {
        $request->user()->notifications()->whereNull('lu_at')->update(['lu_at' => now()]);

        return response()->json(['message' => 'ok']);
    }
}
