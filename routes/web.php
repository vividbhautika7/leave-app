<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLeaveController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Employee\EmployeeDashboardController;
use App\Http\Controllers\Employee\LeaveController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
     return view('welcome');
});

// Route::get('/dashboard', function () {
//      return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
     // Inside auth middleware group
     Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
     Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
     Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});




// Root redirect
Route::get('/', function () {
     return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
     Route::get('/admin/auth/google', [GoogleAuthController::class, 'redirect'])
          ->name('admin.google.auth');

     Route::get('/admin/auth/google/callback', [GoogleAuthController::class, 'callback'])
          ->name('admin.google.callback');

     // Add this new disconnect route
     Route::post('/admin/auth/google/disconnect', [GoogleAuthController::class, 'disconnect'])
          ->name('admin.google.disconnect');
});

// Guest routes
Route::middleware('guest')->group(function () {
     Route::get('/register', [RegisteredUserController::class, 'create'])
          ->name('register');

     Route::post('/register', [RegisteredUserController::class, 'store']);

     Route::get('/login', [AuthenticatedSessionController::class, 'create'])
          ->name('login');

     Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Logout
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
     ->middleware('auth')
     ->name('logout');

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

     Route::get('/dashboard', [AdminDashboardController::class, 'index'])
          ->name('dashboard');

     // Leave management
     Route::get('/leaves', [AdminLeaveController::class, 'index'])
          ->name('leaves.index');
     Route::get('/leaves/{leave}', [AdminLeaveController::class, 'show'])
          ->name('leaves.show');
     Route::delete('/leaves/{leave}', [AdminLeaveController::class, 'destroy'])
          ->name('leaves.destroy');
});

// Employee routes
Route::middleware(['auth', 'employee'])->prefix('employee')->name('employee.')->group(function () {
     Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])
          ->name('dashboard');

     Route::get('/leave/apply', [LeaveController::class, 'create'])
          ->name('leave.apply');

     Route::post('/leave/store', [LeaveController::class, 'store'])
          ->name('leave.store');

     Route::get('/leave/my-leaves', [LeaveController::class, 'myLeaves'])
          ->name('leave.my');

     Route::delete('/leave/{leave}', [LeaveController::class, 'destroy'])
          ->name('leave.destroy');
});


require __DIR__ . '/auth.php';
