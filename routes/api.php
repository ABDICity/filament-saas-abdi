<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/generate-token-abdi', function () {
    $user = \App\Models\User::where('email', 'masjid1.islamicity@gmail.com')->first();
    if ($user) {
        return response()->json(['token' => $user->createToken('Postman-Token')->plainTextToken]);
    }
    return response()->json(['error' => 'User tidak ditemukan'], 404);
});
