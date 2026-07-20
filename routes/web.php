<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PersonalDetailsController;
use App\Http\Controllers\WageController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;

Route::get("/", fn() => redirect()->route("login"));

Route::middleware(["auth"])->group(function () {
    Route::get("/dashboard", [DashboardController::class, "index"])->name("dashboard");
    Route::get("/profile", [ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [ProfileController::class, "update"])->name("profile.update");
    Route::delete("/profile", [ProfileController::class, "destroy"])->name("profile.destroy");

    // Employees
    Route::resource("employees", EmployeeController::class)->except(["destroy"]);
    Route::get("employees/{employee}/personal", [PersonalDetailsController::class, "edit"])->name("employees.personal.edit");
    Route::put("employees/{employee}/personal", [PersonalDetailsController::class, "update"])->name("employees.personal.update");
    Route::post("employees/{employee}/contracts", [ContractController::class, "store"])->name("contracts.store");
    Route::post("employees/{employee}/documents", [DocumentController::class, "store"])->name("documents.store");
    Route::get("documents/{document}/download", [DocumentController::class, "download"])->name("documents.download");
    Route::post("employees/{employee}/wages", [WageController::class, "store"])->name("wages.store");
    Route::post("employees/{employee}/leave/adjust", [LeaveController::class, "adjust"])->name("leave.adjust");

    // Leave
    Route::get("leave", [LeaveController::class, "index"])->name("leave.index");
    Route::get("leave/create", [LeaveController::class, "create"])->name("leave.create");
    Route::post("leave", [LeaveController::class, "store"])->name("leave.store");
    Route::post("leave/{leaveRequest}/approve", [LeaveController::class, "approve"])->name("leave.approve");
    Route::post("leave/{leaveRequest}/decline", [LeaveController::class, "decline"])->name("leave.decline");
    Route::post("leave/{leaveRequest}/cancel", [LeaveController::class, "cancel"])->name("leave.cancel");
    Route::put("leave/{leaveRequest}/reschedule", [LeaveController::class, "reschedule"])->name("leave.reschedule");

    // Disruptions
    Route::post("leave/disruption/add", [LeaveController::class, "addDisruption"])->name("leave.disruption.add");
    Route::delete("leave/disruption/{disruption}/delete", [LeaveController::class, "deleteDisruption"])->name("leave.disruption.delete");
});

require __DIR__ . "/auth.php";
