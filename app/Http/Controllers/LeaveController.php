<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveAllowance;
use App\Models\LeaveAdjustment;
use App\Models\User;

class LeaveController extends Controller {
    public function index(Request $request) {
        $user = $request->user();
        $year = $request->get("year", now()->year);
        $query = LeaveRequest::with(["user","leaveType"]);
        if ($user->isManager()) {
            $query->where("org_id", $user->org_id);
        } else {
            $query->where("user_id", $user->id);
        }
        $requests = $query->whereYear("start_date", $year)->latest()->paginate(20);
        $pending = LeaveRequest::where("status","pending")
            ->where($user->isManager() ? "org_id" : "user_id", $user->isManager() ? $user->org_id : $user->id)
            ->count();
        return view("leave.index", compact("requests","user","year","pending"));
    }

    public function create(Request $request) {
        $user = $request->user();
        $targetUser = $request->has("employee_id") && $user->isManager()
            ? User::findOrFail($request->employee_id)
            : $user;
        $leaveTypes = LeaveType::where("org_id", $targetUser->org_id)->where("active",true)->orderBy("sort_order")->get();
        $balances = $targetUser->leaveAllowances()->with("leaveType")->where("year", now()->year)->get();
        return view("leave.create", compact("user","targetUser","leaveTypes","balances"));
    }

    public function store(Request $request) {
        $user = $request->user();
        $data = $request->validate([
            "user_id" => "required|exists:users,id",
            "leave_type_id" => "required|exists:leave_types,id",
            "start_date" => "required|date|after_or_equal:today",
            "end_date" => "required|date|after_or_equal:start_date",
            "half_day" => "nullable|boolean",
            "half_day_period" => "nullable|in:am,pm",
            "notes" => "nullable|string|max:500",
        ]);

        $targetUser = User::findOrFail($data["user_id"]);
        if ($user->id !== $targetUser->id && !$user->isManager()) abort(403);

        $days = $this->calculateDays($data["start_date"], $data["end_date"], $data["half_day"] ?? false, $targetUser);

        $leaveType = LeaveType::findOrFail($data["leave_type_id"]);
        $status = $leaveType->requires_approval ? "pending" : "approved";

        // Auto-approve if manager is submitting for themselves or no approval needed
        if ($user->isGlobalAdmin()) $status = "approved";

        $request = LeaveRequest::create(array_merge($data, [
            "org_id" => $targetUser->org_id,
            "days_count" => $days,
            "status" => $status,
            "approved_by" => $status === "approved" ? $user->id : null,
            "approved_at" => $status === "approved" ? now() : null,
        ]));

        if ($status === "approved") {
            $this->updateUsedDays($targetUser, $data["leave_type_id"], $data["start_date"]);
        }

        $msg = $status === "approved" ? "Leave approved and booked." : "Leave request submitted. Awaiting approval.";
        return redirect()->route("leave.index")->with("success", $msg);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest) {
        $user = $request->user();
        if (!$user->isManager()) abort(403);
        if ($leaveRequest->org_id !== $user->org_id && !$user->isGlobalAdmin()) abort(403);

        $leaveRequest->update(["status" => "approved","approved_by" => $user->id,"approved_at" => now()]);
        $this->updateUsedDays($leaveRequest->user, $leaveRequest->leave_type_id, $leaveRequest->start_date);
        return back()->with("success","Leave request approved.");
    }

    public function decline(Request $request, LeaveRequest $leaveRequest) {
        $user = $request->user();
        if (!$user->isManager()) abort(403);
        $request->validate(["reason" => "nullable|string|max:300"]);
        $leaveRequest->update(["status" => "declined","approved_by" => $user->id,"approved_at" => now(),"decline_reason" => $request->reason]);
        return back()->with("success","Leave request declined.");
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest) {
        $user = $request->user();
        if ($leaveRequest->user_id !== $user->id && !$user->isManager()) abort(403);
        if (!in_array($leaveRequest->status, ["pending","approved"])) abort(400,"Cannot cancel.");

        if ($leaveRequest->isApproved()) {
            $this->updateUsedDays($leaveRequest->user, $leaveRequest->leave_type_id, $leaveRequest->start_date, -$leaveRequest->days_count);
        }
        $leaveRequest->update(["status" => "cancelled"]);
        return back()->with("success","Leave request cancelled.");
    }

    public function adjust(Request $request, User $employee) {
        $user = $request->user();
        if (!$user->isManager()) abort(403);
        $data = $request->validate([
            "leave_type_id" => "required|exists:leave_types,id",
            "year" => "required|integer|min:2020|max:2030",
            "adjustment_days" => "required|numeric|between:-365,365",
            "reason" => "required|string|max:300",
        ]);
        LeaveAdjustment::create(array_merge($data, ["user_id" => $employee->id,"type" => "manual","created_by" => $user->id]));
        $allowance = LeaveAllowance::firstOrCreate(
            ["user_id" => $employee->id,"leave_type_id" => $data["leave_type_id"],"year" => $data["year"]],
            ["total_days" => 0,"carried_days" => 0,"adjusted_days" => 0,"used_days" => 0]
        );
        $allowance->increment("adjusted_days", $data["adjustment_days"]);
        return back()->with("success","Allowance adjusted by {$data["adjustment_days"]} days.");
    }

    private function calculateDays(string $start, string $end, bool $halfDay, User $user): float {
        if ($halfDay) return 0.5;
        $days = 0;
        $current = new \DateTime($start);
        $endDt = new \DateTime($end);
        while ($current <= $endDt) {
            if (!in_array($current->format("N"), [6, 7])) $days++;
            $current->modify("+1 day");
        }
        return $days;
    }

    private function updateUsedDays(User $user, int $leaveTypeId, $startDate, float $delta = null): void {
        $year = (int) date("Y", strtotime($startDate));
        $allowance = LeaveAllowance::firstOrCreate(
            ["user_id" => $user->id,"leave_type_id" => $leaveTypeId,"year" => $year],
            ["total_days" => $user->organisation->default_holiday_days ?? 28,"carried_days" => 0,"adjusted_days" => 0,"used_days" => 0]
        );
        // Recalculate from approved requests
        $used = LeaveRequest::where("user_id", $user->id)->where("leave_type_id", $leaveTypeId)
            ->where("status","approved")->whereYear("start_date", $year)->sum("days_count");
        $allowance->update(["used_days" => $used]);
    }
}