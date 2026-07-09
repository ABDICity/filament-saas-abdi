<?php

/*
|--------------------------------------------------------------------------
| Web Routes - ABDI (Aplikasi Berjamaah Dakwah Islamicity)
|--------------------------------------------------------------------------
|
| Dikembangkan dengan prinsip ASRI (Aman, Sehat, Ringkas, Rapih, Resik, Rawat, 
| Rajin, Indah) untuk menunjang gerakan 4B Kaffah.
|
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =========================================================================
// 1. GLOBAL PUBLIC BYPASS & UTILITY ROUTES (Bebas Restriksi Tenant)
// =========================================================================

/**
 * Jalur Khusus Postman/n8n untuk bypass login di Laravel Cloud
 */
Route::post('/api/external-login', function (Request $request) {
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

/**
 * Pengecekan Kesehatan Sistem (Health Check) & Metrik n8n
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
 * Jalur Debug Database Internal (Hapus di Produksi Stabil)
 */
Route::get('/api/debug-database', function () {
    $tables = collect(\DB::select('SHOW TABLES'))->map(function ($table) {
        return current((array) $table);
    });

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

    // --- LANDING PAGE TENANT ---
    Route::get('/', function ($tenant) {
        return view('welcome', ['tenant' => $tenant]);
    })->name('tenant.home');

    // --- AUTENTIKASI STANDARD (WEB) ---
    Route::middleware('guest')->group(function () {
        Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
        Route::get('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);
        
        // Lupa Kata Sandi
        Route::get('/password/reset', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('/password/email', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/password/reset/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/password/reset', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

        // --- MANAJEMEN PROFIL & PENGATURAN ---
        Route::prefix('profile')->group(function () {
            Route::get('/', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
        });

        // =========================================================================
        // 4. MODUL GERAKAN 4B KAFFAH & OLEH-OLEH EKOSISTEM (BERDAYA)
        // =========================================================================

        // --- BERDAKWAH (Manajemen Masjid, Jadwal Kajian, Inventaris Taichi) ---
        Route::prefix('dakwah')->group(function () {
            Route::get('/kajian', [\App\Http\Controllers\Dakwah\KajianController::class, 'index'])->name('dakwah.kajian.index');
            Route::post('/kajian', [\App\Http\Controllers\Dakwah\KajianController::class, 'store']);
            Route::get('/jamaah', [\App\Http\Controllers\Dakwah\JamaahController::class, 'index'])->name('dakwah.jamaah.index');
            Route::get('/taichi', [\App\Http\Controllers\Dakwah\TaichiController::class, 'index'])->name('dakwah.taichi.index'); // Optimalisasi Jasmani & Rohani
        });

        // --- BERSYARIAH (E-Infaq, Zakat, & Wakaf Digital) ---
        Route::prefix('syariah')->group(function () {
            Route::get('/infaq', [\App\Http\Controllers\Syariah\InfaqController::class, 'index'])->name('syariah.infaq');
            Route::post('/infaq/bayar', [\App\Http\Controllers\Syariah\InfaqController::class, 'process'])->name('syariah.infaq.pay');
            Route::get('/laporan-keuangan', [\App\Http\Controllers\Syariah\FinanceController::class, 'index'])->name('syariah.report');
        });

        // --- BERJAMAAH (Program Regional ASRI & Penataan Wilayah) ---
        Route::prefix('berjamaah')->group(function () {
            Route::get('/asri-roadmap', [\App\Http\Controllers\Berjamaah\AsriController::class, 'index'])->name('berjamaah.asri.index');
            Route::post('/asri-roadmap/update', [\App\Http\Controllers\Berjamaah\AsriController::class, 'updateStatus']);
            Route::get('/investor-commitment', [\App\Http\Controllers\Berjamaah\InvestorController::class, 'index'])->name('berjamaah.investor');
        });

        // --- BERMUAMALAH (SaaS Bisnis Komersial Penunjang Dakwah) ---
        Route::prefix('muamalah')->group(function () {
            
            // 🏢 Sub-Modul: Fab Self-Storage
            Route::prefix('storage')->group(function () {
                Route::get('/units', [\App\Http\Controllers\Muamalah\StorageController::class, 'index'])->name('storage.units.index');
                Route::post('/rent', [\App\Http\Controllers\Muamalah\StorageController::class, 'rentUnit'])->name('storage.rent');
                Route::get('/billing', [\App\Http\Controllers\Muamalah\StorageController::class, 'billing'])->name('storage.billing');
            });

            // 🐟 Sub-Modul: Sustainable Resources (Aquaculture & Budidaya Lele / Pupuk)
            Route::prefix('aquaculture')->group(function () {
                Route::get('/rab-lele', [\App\Http\Controllers\Muamalah\AquacultureController::class, 'rabLeleIndex'])->name('aquaculture.lele.rab');
                Route::post('/rab-lele/save', [\App\Http\Controllers\Muamalah\AquacultureController::class, 'storeRab']);
                Route::get('/fertilizer-production', [\App\Http\Controllers\Muamalah\AquacultureController::class, 'fertilizerIndex'])->name('aquaculture.fertilizer');
            });
        });

        // =========================================================================
        // 5. INTERNAL AUTOMATION INTEGRATION (n8n Webhook / Postman Target)
        // =========================================================================
        Route::prefix('integration')->group(function () {
            Route::get('/n8n-logs', [\App\Http\Controllers\Integration\N8nController::class, 'logs'])->name('integration.n8n.logs');
            Route::post('/trigger-automation', [\App\Http\Controllers\Integration\N8nController::class, 'trigger'])->name('integration.n8n.trigger');
        });

    });
});

// =========================================================================
// 6. GLOBAL ERROR & FALLBACK HANDLER
// =========================================================================
Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Alamat Rute tidak ditemukan dalam ekosistem ABDI. Periksa kembali struktur URL Anda.'
    ], 404);
});