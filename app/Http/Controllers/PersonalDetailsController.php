<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;

class PersonalDetailsController extends Controller {
    public function edit(Request $request, User $employee) {
        $user = $request->user();
        if ($user->id !== $employee->id && !$user->isManager()) abort(403);
        $details = $employee->personalDetails ?? $employee->personalDetails()->create(["user_id" => $employee->id]);
        return view("employees.personal", compact("employee","details","user"));
    }

    public function update(Request $request, User $employee) {
        $user = $request->user();
        if ($user->id !== $employee->id && !$user->isManager()) abort(403);
        $data = $request->validate([
            "date_of_birth" => "nullable|date|before:today",
            "address_line1" => "nullable|string|max:200",
            "address_line2" => "nullable|string|max:200",
            "city" => "nullable|string|max:100",
            "postcode" => "nullable|string|max:15",
            "country" => "nullable|string|max:2",
            "national_insurance" => "nullable|string|max:20",
            "right_to_work" => "nullable|in:citizen,visa,applying,not_checked",
            "visa_expiry" => "nullable|date",
            "emergency_contact_name" => "nullable|string|max:150",
            "emergency_contact_phone" => "nullable|string|max:30",
            "emergency_contact_relationship" => "nullable|string|max:100",
            "bank_account_name" => "nullable|string|max:150",
            "bank_sort_code" => "nullable|string|max:10",
            "bank_account_number" => "nullable|string|max:20",
        ]);
        $employee->personalDetails()->updateOrCreate(["user_id" => $employee->id], $data);
        return redirect()->route("employees.show", $employee)->with("success","Personal details updated.");
    }
}