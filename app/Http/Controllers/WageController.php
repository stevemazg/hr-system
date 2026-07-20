<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\WageHistory;

class WageController extends Controller {
    public function store(Request $request, User $employee) {
        $user = $request->user();
        if (!$user->canViewWages()) abort(403);
        $data = $request->validate([
            "effective_date" => "required|date",
            "salary" => "required|numeric|min:0",
            "pay_frequency" => "required|in:weekly,fortnightly,monthly,annual",
            "pay_basis" => "required|in:salary,hourly",
            "hourly_rate" => "nullable|numeric|min:0",
            "notes" => "nullable|string|max:500",
        ]);
        WageHistory::create(array_merge($data, ["user_id" => $employee->id,"created_by" => $user->id]));
        return back()->with("success","Wage record added.");
    }
}