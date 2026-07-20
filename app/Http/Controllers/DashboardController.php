<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\User;

class DashboardController extends Controller {
    public function index(Request $request) {
        $user = $request->user();
        $orgId = $user->org_id;

        $pendingRequests = collect();
        if ($user->isManager()) {
            $pendingRequests = LeaveRequest::with(["user","leaveType"])
                ->where("org_id", $orgId)
                ->where("status","pending")
                ->latest()->take(10)->get();
        }

        $myUpcoming = LeaveRequest::with("leaveType")
            ->where("user_id", $user->id)
            ->where("status","approved")
            ->where("start_date",">=", now())
            ->orderBy("start_date")->take(5)->get();

        $myBalances = $user->leaveAllowances()->with("leaveType")->where("year", now()->year)->get();

        $todayOut = LeaveRequest::with("user")
            ->where("org_id", $orgId)
            ->where("status","approved")
            ->whereDate("start_date","<=", today())
            ->whereDate("end_date",">=", today())
            ->where("user_id","!=",$user->id)->get();

        return view("dashboard", compact("pendingRequests","myUpcoming","myBalances","todayOut","user"));
    }
}