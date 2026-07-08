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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - ABDI (Aplikasi Berjamaah Dakwah Islamicity)
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. GLOBAL PUBLIC BYPASS ROUTES (Taruh di paling atas, di luar scope Tenant)
// =========================================================================

/**
 * Jalur Khusus Postman untuk bypass login di Laravel Cloud (Bebas Restriksi Tenant)
 */
Route::post('/api/external-login', function (Request $request) {
    // Validasi input payload JSON
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (! $user || !\Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Kredensial salah, gagal menjaring token.'
        ], 401);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Token Berdaya Berhasil Dibuat!',
        'token' => $user->createToken('ABDI-Cloud-Token')->plainTextToken
    ], 200);
});


// =========================================================================
// 2. ROOT LANDING PAGE (Akses Domain Utama tanpa Subdomain Tenant)
// =========================================================================

Route::get('/', function () {
    return response()->json([
        'aplikasi' => 'ABDI (Aplikasi Berjamaah Dakwah Islamicity)',
        'gerakan' => '4B Kaffah (Belajar, Beribadah, Berinfak, Berbisnis)',
        'manajemen' => 'ASRI (Aman, Sehat, Ringkas, Rapih, Resik, Rawat, Rajin, Indah)',
        'status' => 'Landing Page Global Aktif'
    ]);
});


// =========================================================================
// 3. MULTI-TENANCY SCOPE (Khusus Aplikasi Bersubdomain / Regional Jamaah)
// =========================================================================

Route::domain('{tenant}.' . parse_url(config('app.url'), PHP_URL_HOST))->group(function () {

    // Halaman Welcome khusus untuk masing-masing Tenant/Regional Masjid
    Route::get('/', function ($tenant) {
        return view('welcome', ['tenant' => $tenant]);
    });

});
