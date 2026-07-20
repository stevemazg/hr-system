<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PersonalDetailsController;
use App\Http\Controllers\WageController;
use App\Http\Controllers\ProfileController;

Route::get("/", fn() => redirect()->route("login"));

Route::middleware(["auth"])->group(function () {
    Route::get("/dashboard", [DashboardController::class, "index"])->name("dashboard");

    // Profile (Breeze)
    Route::get("/profile", [ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [ProfileController::class, "update"])->name("profile.update");
    Route::delete("/profile", [ProfileController::class, "destroy"])->name("profile.destroy");

    // Employees
    Route::resource("employees", EmployeeController::class)->except(["destroy"]);

    // Personal details
    Route::get("employees/{employee}/personal", [PersonalDetailsController::class, "edit"])->name("employees.personal.edit");
    Route::put("employees/{employee}/personal", [PersonalDetailsController::class, "update"])->name("employees.personal.update");

    // Leave
    Route::get("leave", [LeaveController::class, "index"])->name("leave.index");
    Route::get("leave/create", [LeaveController::class, "create"])->name("leave.create");
    Route::post("leave", [LeaveController::class, "store"])->name("leave.store");
    Route::post("leave/{leaveRequest}/approve", [LeaveController::class, "approve"])->name("leave.approve");
    Route::post("leave/{leaveRequest}/decline", [LeaveController::class, "decline"])->name("leave.decline");
    Route::post("leave/{leaveRequest}/cancel", [LeaveController::class, "cancel"])->name("leave.cancel");

    // Allowance adjustments
    Route::post("employees/{employee}/leave/adjust", [LeaveController::class, "adjust"])->name("leave.adjust");

    // Wages (global admin only)
    Route::post("employees/{employee}/wages", [WageController::class, "store"])->name("wages.store");
});

require __DIR__ . "/auth.php";
