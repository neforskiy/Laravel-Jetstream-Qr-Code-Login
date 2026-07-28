<?php

use App\Http\Controllers\LoginSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/qr/session', [LoginSessionController::class, 'create']);
Route::delete('/qr/session/{uuid}', [LoginSessionController::class, 'destroy']);
Route::get('/qr/session/{uuid}/info', [LoginSessionController::class, 'get_info_from_uuid']);
Route::get('deviceinfo', function (Request $request) {
    $userAgent = $request->userAgent() ?? '';
    $isMobile = (bool) preg_match(
        '/(android|iphone|ipad|ipod|blackberry|windows phone|mobile)/i',
        $userAgent
    );

    return response()->json([
        'device' => $isMobile ? 'mobile' : 'desktop',
    ]);
});
