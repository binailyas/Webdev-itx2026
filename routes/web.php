<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Staff;
use App\Http\Controllers\Student;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik & autentikasi
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'welcome'])->name('welcome');
Route::get('/darurat', [PublicController::class, 'darurat'])->name('darurat');
Route::get('/media/informasi/{file}', [PublicController::class, 'informationImage'])->where('file', '[A-Za-z0-9._-]+')->name('media.informasi');
Route::get('/lupa-password', [PublicController::class, 'forgot'])->name('forgot');
Route::redirect('/admin/masuk', '/masuk');   // G6: tidak ada lagi login admin terpisah

Route::middleware('guest:web')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/otp', [AuthController::class, 'showOtp'])->name('otp.show');
    Route::post('/otp', [AuthController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('/otp/kirim-ulang', [AuthController::class, 'resendOtp'])->name('otp.resend');
});

Route::get('/lapor-anonim', [AuthController::class, 'anonInfo'])->name('anon.info');
Route::post('/lapor-anonim', [AuthController::class, 'anonCreate'])->name('anon.create')->middleware('throttle:5,10');
Route::get('/lapor-anonim/berhasil', [AuthController::class, 'anonCreated'])->name('anon.created');
Route::get('/masuk-anonim', [AuthController::class, 'showAnonLogin'])->name('login.anon');
Route::post('/masuk-anonim', [AuthController::class, 'anonLogin'])->name('login.anon.attempt');
Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
Route::post('/keluar-cepat', [AuthController::class, 'quickExit'])->name('keluar.cepat');

Route::get('/badges', [Staff\NotificationController::class, 'badges'])->name('badges')->middleware('role:bk,wali_kelas');

/*
|--------------------------------------------------------------------------
| Siswa & anonim
|--------------------------------------------------------------------------
*/
Route::prefix('siswa')->name('siswa.')->middleware('student')->group(function () {
    Route::get('/', [Student\HomeController::class, 'index'])->name('beranda');
    Route::get('/informasi', [Student\InfoController::class, 'index'])->name('informasi.index');
    Route::get('/informasi/{slug}', [Student\InfoController::class, 'show'])->name('informasi.show');
    Route::post('/chatbot', [Student\HomeController::class, 'chatbot'])->name('chatbot')->middleware('throttle:30,1');
    Route::get('/notifikasi', [Student\HomeController::class, 'notifications'])->name('notifikasi');
    Route::post('/notifikasi/baca', [Student\HomeController::class, 'readAll'])->name('notifikasi.baca');
    Route::get('/profil', [Student\HomeController::class, 'profile'])->name('profil');
    Route::post('/profil/kata-sandi', [Student\HomeController::class, 'password'])->name('profil.password');
    Route::delete('/profil', [Student\HomeController::class, 'destroyAnon'])->name('profil.hapus');

    Route::get('/laporan', [Student\ReportController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/buat', [Student\ReportController::class, 'create'])->name('laporan.create');
    Route::post('/laporan', [Student\ReportController::class, 'store'])->name('laporan.store');
    Route::get('/cek-status', [Student\ReportController::class, 'checkForm'])->name('cekstatus');
    Route::post('/cek-status', [Student\ReportController::class, 'checkResult'])->name('cekstatus.hasil')->middleware('throttle:10,1');
    Route::get('/laporan/terkirim', [Student\ReportController::class, 'sent'])->name('laporan.sent');
    Route::get('/laporan/{ticket}', [Student\ReportController::class, 'show'])->name('laporan.show');
    Route::get('/laporan/{ticket}/chat', [Student\ChatController::class, 'show'])->name('laporan.chat');
    Route::post('/laporan/{ticket}/chat', [Student\ChatController::class, 'send'])->name('laporan.chat.send');
    Route::post('/laporan/{ticket}/chat/mulai', [Student\ChatController::class, 'start'])->name('laporan.chat.mulai');

    Route::middleware('student:registered')->group(function () {
        Route::get('/karir', [Student\CareerController::class, 'index'])->name('karir.index');
        Route::get('/karir/baru', [Student\CareerController::class, 'create'])->name('karir.create');
        Route::post('/karir', [Student\CareerController::class, 'store'])->name('karir.store');
        Route::get('/karir/{room}', [Student\CareerController::class, 'show'])->name('karir.show');
        Route::post('/karir/{room}', [Student\CareerController::class, 'send'])->name('karir.send');
        Route::get('/kredit', [Student\CreditController::class, 'index'])->name('kredit');
        Route::get('/kredit/{record}', [Student\CreditController::class, 'show'])->name('kredit.show');
    });
});

/*
|--------------------------------------------------------------------------
| Guru BK & Wali Kelas (controller dipakai bersama; perilaku mengikuti peran)
|--------------------------------------------------------------------------
*/
$staffRoutes = function () {
    Route::get('/', [Staff\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/laporan', [Staff\ReportController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/{report}', [Staff\ReportController::class, 'show'])->name('laporan.show');
    Route::post('/laporan/{report}/status', [Staff\ReportController::class, 'status'])->name('laporan.status');
    Route::post('/laporan/{report}/prioritas', [Staff\ReportController::class, 'override'])->name('laporan.override');
    Route::post('/laporan/{report}/catatan', [Staff\ReportController::class, 'note'])->name('laporan.note');
    Route::get('/laporan/{report}/lampiran/{attachment}', [Staff\ReportController::class, 'attachment'])->name('laporan.lampiran');
    Route::post('/laporan/{report}/pihak/{entity}', [Staff\ReportController::class, 'entity'])->name('laporan.entity');
    Route::get('/laporan/{report}/chat', [Staff\ChatController::class, 'show'])->name('chat.show');

    Route::get('/arsip', [Staff\ReportController::class, 'archived'])->name('arsip.index');
    Route::get('/ringkasan', [Staff\SummaryController::class, 'index'])->name('ringkasan');
    Route::get('/ringkasan/unduh', [Staff\SummaryController::class, 'download'])->name('ringkasan.unduh');

    Route::get('/analitik', [Staff\AnalyticsController::class, 'keywords'])->name('analitik.kata');
    Route::get('/analitik/kata/{keyword}', [Staff\AnalyticsController::class, 'keywordDetail'])->name('analitik.kata.detail');
    Route::get('/analitik/orang', [Staff\AnalyticsController::class, 'people'])->name('analitik.orang');
    Route::get('/analitik/orang/{name}', [Staff\AnalyticsController::class, 'profile'])->name('analitik.profil');
    Route::post('/analitik/orang/{name}/unduh', [Staff\AnalyticsController::class, 'download'])->name('analitik.profil.unduh');
    Route::get('/analitik/lokasi', [Staff\AnalyticsController::class, 'locations'])->name('analitik.lokasi');
    Route::get('/analitik/tren', [Staff\AnalyticsController::class, 'trends'])->name('analitik.tren');
    Route::get('/analitik/watchlist', [Staff\AnalyticsController::class, 'watchlist'])->name('analitik.watchlist');
    Route::post('/analitik/semua-kelas', [Staff\AnalyticsController::class, 'allClasses'])->name('analitik.semua');
    Route::post('/analitik/watchlist', [Staff\AnalyticsController::class, 'watchStore'])->name('watchlist.store');
    Route::post('/analitik/watchlist/{term}/hapus', [Staff\AnalyticsController::class, 'watchDestroy'])->name('watchlist.destroy');

    Route::get('/pengaturan-analitik', [Staff\DictionaryController::class, 'index'])->name('pengaturan.index');
    Route::post('/pengaturan-analitik/alias', [Staff\DictionaryController::class, 'aliasStore'])->name('alias.store');
    Route::post('/pengaturan-analitik/alias/{alias}/hapus', [Staff\DictionaryController::class, 'aliasDestroy'])->name('alias.destroy');
    Route::post('/pengaturan-analitik/stopword', [Staff\DictionaryController::class, 'stopStore'])->name('stopword.store');
    Route::post('/pengaturan-analitik/stopword/{word}/hapus', [Staff\DictionaryController::class, 'stopDestroy'])->name('stopword.destroy');
    Route::post('/pengaturan-analitik/stopword-pulihkan', [Staff\DictionaryController::class, 'stopReset'])->name('stopword.reset');
    Route::post('/pengaturan-analitik/ekstraksi', [Staff\DictionaryController::class, 'extraction'])->name('pengaturan.ekstraksi');

    Route::get('/skor', [Staff\CreditController::class, 'index'])->name('skor.index');
    Route::get('/skor/{student}', [Staff\CreditController::class, 'show'])->name('skor.show');
    Route::post('/skor/{student}', [Staff\CreditController::class, 'store'])->name('skor.store');

    Route::get('/notifikasi', [Staff\NotificationController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca', [Staff\NotificationController::class, 'readAll'])->name('notifikasi.baca');
};

Route::prefix('bk')->name('bk.')->middleware(['role:bk', 'feature'])->group(function () use ($staffRoutes) {
    $staffRoutes();

    // Khusus BK: buka/tulis chat, izin baca Wali Kelas, arsip, karir, informasi, pembatalan skor.
    Route::post('/laporan/{report}/arsip', [Staff\ReportController::class, 'archive'])->name('laporan.arsip');
    Route::post('/laporan/{report}/chat/buka', [Staff\ChatController::class, 'open'])->name('chat.open');
    Route::post('/laporan/{report}/chat', [Staff\ChatController::class, 'send'])->name('chat.send');
    Route::post('/laporan/{report}/chat/izin', [Staff\ChatController::class, 'allow'])->name('chat.allow');

    Route::get('/karir', [Staff\CareerController::class, 'index'])->name('karir.index');
    Route::post('/karir/{room}/terima', [Staff\CareerController::class, 'accept'])->name('karir.accept');
    Route::get('/karir/{room}', [Staff\CareerController::class, 'show'])->name('karir.show');
    Route::post('/karir/{room}/kirim', [Staff\CareerController::class, 'send'])->name('karir.send');
    Route::post('/karir/{room}/tutup', [Staff\CareerController::class, 'close'])->name('karir.close');

    Route::get('/informasi', [Staff\AnnouncementController::class, 'index'])->name('informasi.index');
    Route::get('/informasi/tulis', [Staff\AnnouncementController::class, 'create'])->name('informasi.create');
    Route::post('/informasi', [Staff\AnnouncementController::class, 'store'])->name('informasi.store');
    Route::get('/informasi/{announcement}/ubah', [Staff\AnnouncementController::class, 'edit'])->name('informasi.edit');
    Route::put('/informasi/{announcement}', [Staff\AnnouncementController::class, 'update'])->name('informasi.update');
    Route::delete('/informasi/{announcement}', [Staff\AnnouncementController::class, 'destroy'])->name('informasi.destroy');

    Route::post('/skor/catatan/{record}/batalkan', [Staff\CreditController::class, 'void'])->name('skor.void');
});

Route::prefix('wali-kelas')->name('wk.')->middleware(['role:wali_kelas', 'feature'])->group(function () use ($staffRoutes) {
    $staffRoutes();
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/siswa', [Admin\StudentController::class, 'index'])->name('siswa.index');
    Route::post('/siswa', [Admin\StudentController::class, 'store'])->name('siswa.store');
    Route::post('/siswa/massal', [Admin\StudentController::class, 'bulk'])->name('siswa.bulk');
    Route::get('/siswa/{user}', [Admin\StudentController::class, 'show'])->name('siswa.show');
    Route::put('/siswa/{user}', [Admin\StudentController::class, 'update'])->name('siswa.update');
    Route::post('/siswa/{user}/reset', [Admin\StudentController::class, 'reset'])->name('siswa.reset');
    Route::post('/siswa/{user}/aktif', [Admin\StudentController::class, 'toggle'])->name('siswa.toggle');
    Route::delete('/siswa/{user}', [Admin\StudentController::class, 'destroy'])->name('siswa.destroy');

    Route::get('/staf', [Admin\StaffController::class, 'index'])->name('staf.index');
    Route::post('/staf', [Admin\StaffController::class, 'store'])->name('staf.store');
    Route::get('/staf/{user}', [Admin\StaffController::class, 'show'])->name('staf.show');
    Route::put('/staf/{user}', [Admin\StaffController::class, 'update'])->name('staf.update');
    Route::post('/staf/{user}/reset', [Admin\StaffController::class, 'reset'])->name('staf.reset');
    Route::post('/staf/{user}/aktif', [Admin\StaffController::class, 'toggle'])->name('staf.toggle');
    Route::delete('/staf/{user}', [Admin\StaffController::class, 'destroy'])->name('staf.destroy');

    Route::get('/impor', [Admin\ImportController::class, 'index'])->name('impor.index');
    Route::get('/impor/templat', [Admin\ImportController::class, 'template'])->name('impor.template');
    Route::post('/impor/tinjau', [Admin\ImportController::class, 'preview'])->name('impor.preview');
    Route::get('/impor/sandi', [Admin\ImportController::class, 'passwords'])->name('impor.sandi');
    Route::post('/impor/proses', [Admin\ImportController::class, 'commit'])->name('impor.commit');

    Route::get('/kelas', [Admin\ClassroomController::class, 'index'])->name('kelas.index');
    Route::post('/kelas', [Admin\ClassroomController::class, 'store'])->name('kelas.store');
    Route::delete('/kelas/{classroom}', [Admin\ClassroomController::class, 'destroy'])->name('kelas.destroy');
    Route::get('/kelas/naik-kelas', [Admin\ClassroomController::class, 'promoteForm'])->name('kelas.naik');
    Route::post('/kelas/naik-kelas', [Admin\ClassroomController::class, 'promote'])->name('kelas.naik.proses');

    Route::get('/role', [Admin\RoleController::class, 'index'])->name('role.index');
    Route::post('/role', [Admin\RoleController::class, 'update'])->name('role.update');

    Route::get('/kategori', [Admin\CategoryController::class, 'index'])->name('kategori.index');
    Route::post('/kategori', [Admin\CategoryController::class, 'store'])->name('kategori.store');
    Route::put('/kategori/{type}/{id}', [Admin\CategoryController::class, 'update'])->name('kategori.update');
    Route::delete('/kategori/{type}/{id}', [Admin\CategoryController::class, 'destroy'])->name('kategori.destroy');

    Route::get('/audit', [Admin\AuditController::class, 'index'])->name('audit.index');
    Route::get('/pengaturan', [Admin\SettingController::class, 'index'])->name('pengaturan.index');
    Route::post('/pengaturan', [Admin\SettingController::class, 'update'])->name('pengaturan.update');
});
