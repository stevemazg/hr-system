<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Contract;

class ContractController extends Controller {
    public function store(Request $request, User $employee) {
        $user = $request->user();
        if (!$user->isManager()) abort(403);
        $data = $request->validate([
            "title" => "required|string|max:200",
            "type" => "required|in:permanent,fixed_term,zero_hours,freelance,apprenticeship",
            "start_date" => "required|date",
            "end_date" => "nullable|date",
            "probation_end_date" => "nullable|date",
            "notice_period_days" => "nullable|integer|min:0",
            "hours_per_week" => "nullable|numeric|min:0|max:168",
        ]);
        Contract::where("user_id", $employee->id)->update(["is_current" => false]);
        Contract::create(array_merge($data, ["user_id" => $employee->id, "is_current" => true, "created_by" => $user->id]));
        return redirect()->route("employees.show", $employee)->with("success","Contract added.");
    }
}
