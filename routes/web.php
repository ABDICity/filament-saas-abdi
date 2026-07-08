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
        'gerakan' => '4B Kaffah (Berdakwah, Bersyariah, Berjamaah, Bermuamalah)',
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


// Jalur Pintas Eksklusif Mengintip Semua Tabel & Data (Hapus jika sudah masuk Produksi Stabil)
Route::get('/api/debug-database', function () {
    // 1. Ambil semua nama tabel
    $tables = collect(\DB::select('SHOW TABLES'))->map(function ($table) {
        return current((array) $table);
    });

    // 2. Ambil sampel data dari 3 tabel utama (Ubah sesuai kebutuhan)
    $preview = [];
    foreach (['users', 'tenants'] as $tableName) {
        if (\Schema::hasTable($tableName)) {
            $preview[$tableName] = \DB::table($tableName)->limit(5)->get();
        }
    }

    return response()->json([
        'status' => 'success',
        'total_tabel' => $tables->count(),
        'daftar_tabel' => $tables,
        'sampel_isi_data' => $preview
    ]);
});



// ==========================================
// 1. RUTE PUBLIK (Tanpa Bearer Token)
// ==========================================

/**
 * Pengecekan Kesehatan Sistem (Health Check)
 * Sangat berguna untuk memastikan server Laravel Cloud Anda aktif dan terhubung ke Database dengan aman.
 */
Route::get('/health-check', function () {
    try {
        \DB::connection()->getPdo();
        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'database' => 'disconnected',
            'error' => $e->getMessage(),
        ], 500);
    }
});
