<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - ABDI Workspace & Filament SAAS
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

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

/**
 * Token Generator Darurat (Gunakan untuk Postman)
 */
Route::get('/generate-token-abdi', function () {
    $user = \App\Models\User::where('email', 'masjid1.islamicity@gmail.com')->first();
    if ($user) {
        return response()->json([
            'message' => 'Token berhasil dibuat. Salin token di bawah untuk Bearer Token Postman.',
            'token' => $user->createToken('Postman-Token')->plainTextToken
        ]);
    }
    return response()->json(['error' => 'User tidak ditemukan'], 404);
});

/**
 * API Login Standar (Mendapatkan Token Secara Legal)
 */
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (! $user || !\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Kredensial yang Anda masukkan salah.'], 401);
    }

    return response()->json([
        'message' => 'Login Sukses!',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
        'token' => $user->createToken('ABDI-Mobile-Token')->plainTextToken
    ]);
});


// ==========================================
// 2. RUTE TERPROTEKSI (Wajib Menggunakan Bearer Token di Postman)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    /**
     * Mengambil data profil user yang sedang login
     */
    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    });

    /**
     * Memperbarui nama atau profil user saat ini
     */
    Route::put('/user/update', function (Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $user->update(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => $user
        ]);
    });

    /**
     * Mengambil daftar Tenant/Perusahaan yang dimiliki oleh User (SaaS Fitur)
     */
    Route::get('/tenants', function (Request $request) {
        // Asumsi model User Anda memiliki relasi 'tenants' atau tabel pivot terkait multi-tenancy
        if (method_exists($request->user(), 'tenants')) {
            return response()->json([
                'status' => 'success',
                'data' => $request->user()->tenants
            ]);
        }
        
        // Fallback jika menggunakan struktur tabel gabungan/custom field
        return response()->json([
            'status' => 'success',
            'message' => 'Fitur multi-tenant aktif. Silakan sesuaikan relasi model Anda.',
            'data' => []
        ]);
    });

    /**
     * Utilitas Sistem: Mengetahui Informasi Konfigurasi Aplikasi (Aman/Internal)
     */
    Route::get('/system/info', function () {
        return response()->json([
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'timezone' => config('app.timezone'),
            'app_name' => config('app.name'),
        ]);
    });

    /**
     * Logout / Hapus Token Aktif saat ini (Revoke Token)
     */
    Route::post('/logout', function (Request $request) {
        // Menghapus token yang saat ini sedang digunakan untuk mengakses API ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Token berhasil dicabut. Logout sukses!'
        ], 200);
    });
});