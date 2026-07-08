<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rute Bypass Publik untuk Login API di Web Group
Route::post('/api/external-login', function (\Illuminate\Http\Request $request) {
    $user = \App\Models\User::where('email', $request->email)->first();

    if (! $user || !\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Kredensial salah.'], 401);
    }

    return response()->json([
        'status' => 'success',
        'token' => $user->createToken('ABDI-Cloud-Token')->plainTextToken
    ]);
});
