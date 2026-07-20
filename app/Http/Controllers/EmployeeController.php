<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Organisation;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller {
    public function index(Request $request) {
        $user = $request->user();
        $query = User::with("organisation")
            ->where("active", true)
            ->orderBy("last_name")->orderBy("first_name");
        if (!$user->isGlobalAdmin()) $query->where("org_id", $user->org_id);
        $employees = $query->paginate(25);
        return view("employees.index", compact("employees","user"));
    }

    public function show(Request $request, User $employee) {
        $user = $request->user();
        $this->authorizeEmployeeAccess($user, $employee);
        $employee->load(["organisation","personalDetails","contracts","leaveAllowances.leaveType","lineManager","directReports"]);
        $canViewWages = $user->canViewWages();
        $wageHistory = $canViewWages ? $employee->wageHistory()->with("creator")->get() : collect();
        return view("employees.show", compact("employee","user","canViewWages","wageHistory"));
    }

    public function edit(Request $request, User $employee) {
        $user = $request->user();
        $this->authorizeEmployeeAccess($user, $employee);
        $orgs = $user->isGlobalAdmin() ? Organisation::where("active",true)->orderBy("name")->get() : collect([$user->organisation]);
        $managers = User::where("org_id", $employee->org_id)->where("role","!=","user")->where("id","!=",$employee->id)->orderBy("last_name")->get();
        return view("employees.edit", compact("employee","user","orgs","managers"));
    }

    public function update(Request $request, User $employee) {
        $user = $request->user();
        $this->authorizeEmployeeAccess($user, $employee);
        $data = $request->validate([
            "first_name" => "required|string|max:100",
            "last_name" => "required|string|max:100",
            "email" => "required|email|unique:users,email,{$employee->id}",
            "phone" => "nullable|string|max:30",
            "job_title" => "nullable|string|max:150",
            "employment_type" => "required|in:permanent,part_time,freelancer,contractor,zero_hours",
            "start_date" => "nullable|date",
            "working_hours_per_week" => "nullable|numeric|min:0|max:168",
            "line_manager_id" => "nullable|exists:users,id",
            "org_id" => "nullable|exists:organisations,id",
        ]);
        $employee->update($data);
        return redirect()->route("employees.show", $employee)->with("success","Profile updated.");
    }

    public function create(Request $request) {
        $user = $request->user();
        if (!$user->isManager()) abort(403);
        $orgs = $user->isGlobalAdmin() ? Organisation::where("active",true)->orderBy("name")->get() : collect([$user->organisation]);
        return view("employees.create", compact("user","orgs"));
    }

    public function store(Request $request) {
        $user = $request->user();
        if (!$user->isManager()) abort(403);
        $data = $request->validate([
            "first_name" => "required|string|max:100",
            "last_name" => "required|string|max:100",
            "email" => "required|email|unique:users,email",
            "org_id" => "required|exists:organisations,id",
            "job_title" => "nullable|string|max:150",
            "employment_type" => "required|in:permanent,part_time,freelancer,contractor,zero_hours",
            "start_date" => "nullable|date",
            "role" => "required|in:global_admin,manager,user",
        ]);
        $data["password"] = Hash::make("Welcome2026!");
        $data["name"] = "{$data["first_name"]} {$data["last_name"]}";
        $employee = User::create($data);
        $employee->personalDetails()->create(["user_id" => $employee->id]);
        return redirect()->route("employees.show", $employee)->with("success","Employee created. Default password: Welcome2026!");
    }

    private function authorizeEmployeeAccess(User $viewer, User $subject): void {
        if ($viewer->isGlobalAdmin()) return;
        if ($viewer->id === $subject->id) return;
        if ($viewer->isManager() && $viewer->org_id === $subject->org_id) return;
        abort(403);
    }
}