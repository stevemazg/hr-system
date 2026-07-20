<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveAllowance;
use App\Models\LeaveAdjustment;
use App\Models\LeaveDisruption;
use App\Models\PublicHoliday;
use App\Models\User;
use Carbon\Carbon;

class LeaveController extends Controller {

    public function index(Request $request) {
        $user  = $request->user();
        $orgId = $user->org_id;
        $month = $request->get('m', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) $month = now()->format('Y-m');

        $month1 = Carbon::parse($month . '-01');
        $month2 = $month1->copy()->addMonth();
        $month3 = $month1->copy()->addMonths(2);
        $prevM  = $month1->copy()->subMonth()->format('Y-m');
        $nextM  = $month2->format('Y-m');
        $calStart = $month1->format('Y-m-d');
        $calEnd   = $month3->copy()->endOfMonth()->format('Y-m-d');

        $calRequests = LeaveRequest::with(['user','leaveType'])
            ->where('org_id', $orgId)->whereIn('status',['pending','approved'])
            ->where('start_date','<=',$calEnd)->where('end_date','>=',$calStart)->get();

        $bankHolidays = PublicHoliday::where('org_id',$orgId)->where('active',true)
            ->whereBetween('date',[$calStart,$calEnd])->get()
            ->keyBy(fn($b) => $b->date->format('Y-m-d'));

        $disruptions = LeaveDisruption::with('creator')->where('org_id',$orgId)
            ->whereBetween('date',[$calStart,$calEnd])->orderBy('date')->orderBy('time_from')
            ->get()->groupBy(fn($d) => $d->date->format('Y-m-d'));

        $dayMap = [];
        foreach ($calRequests as $req) {
            $cur = Carbon::parse($req->start_date);
            $end = Carbon::parse($req->end_date);
            while ($cur->lte($end)) {
                $ds = $cur->format('Y-m-d');
                if ($ds >= $calStart && $ds <= $calEnd) {
                    $isMe = $req->user_id === $user->id;
                    $dayMap[$ds][] = [
                        'type'            => $isMe ? ($req->status==='approved' ? 'my_approved' : 'my_pending') : 'other',
                        'req_id'          => $req->id,
                        'req_start'       => $req->start_date->format('Y-m-d'),
                        'req_end'         => $req->end_date->format('Y-m-d'),
                        'req_type'        => $req->leaveType->name,
                        'req_colour'      => $req->leaveType->colour,
                        'req_status'      => $req->status,
                        'half_day'        => $req->half_day,
                        'half_day_period' => $req->half_day_period,
                        'days'            => $req->days_count,
                        'name'            => $req->user->full_name,
                        'initials'        => $req->user->initials,
                        'is_me'           => $isMe,
                    ];
                }
                $cur->addDay();
            }
        }

        $myRequests = LeaveRequest::with('leaveType')->where('user_id',$user->id)
            ->orderByDesc('start_date')->take(30)->get();
        $teamLeave = LeaveRequest::with(['user','leaveType'])->where('org_id',$orgId)
            ->whereIn('status',['pending','approved'])->where('end_date','>=',now()->subDays(7))
            ->orderBy('start_date')->get();
        $myBalances = $user->leaveAllowances()->with('leaveType')->where('year',now()->year)->get();
        $pendingRequests = $user->isManager()
            ? LeaveRequest::with(['user','leaveType'])->where('org_id',$orgId)->where('status','pending')->latest()->get()
            : collect();
        $allDisruptions = $user->isManager()
            ? LeaveDisruption::with('creator')->where('org_id',$orgId)->where('date','>=',today())->orderBy('date')->get()
            : collect();

        return view('leave.index', compact(
            'user','month','month1','month2','month3','prevM','nextM',
            'dayMap','bankHolidays','disruptions','myRequests','teamLeave',
            'myBalances','pendingRequests','allDisruptions'
        ));
    }

    public function create(Request $request) {
        $user = $request->user();
        $targetUser = $request->has('employee_id') && $user->isManager()
            ? User::findOrFail($request->employee_id) : $user;
        $leaveTypes = LeaveType::where('org_id',$targetUser->org_id)->where('active',true)->orderBy('sort_order')->get();
        $balances   = $targetUser->leaveAllowances()->with('leaveType')->where('year',now()->year)->get();
        $start = $request->get('start', now()->format('Y-m-d'));
        $end   = $request->get('end', $start);
        return view('leave.create', compact('user','targetUser','leaveTypes','balances','start','end'));
    }

    public function store(Request $request) {
        $user = $request->user();
        $data = $request->validate([
            'user_id'         => 'required|exists:users,id',
            'leave_type_id'   => 'required|exists:leave_types,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'half_day'        => 'nullable|boolean',
            'half_day_period' => 'nullable|in:am,pm',
            'notes'           => 'nullable|string|max:500',
        ]);
        $targetUser = User::findOrFail($data['user_id']);
        if ($user->id !== $targetUser->id && !$user->isManager()) abort(403);
        $days   = $this->calcDays($data['start_date'], $data['end_date'], (bool)($data['half_day'] ?? false));
        $lt     = LeaveType::findOrFail($data['leave_type_id']);
        $status = (!$lt->requires_approval || $user->isGlobalAdmin()) ? 'approved' : 'pending';
        LeaveRequest::create(array_merge($data, [
            'org_id' => $targetUser->org_id, 'days_count' => $days, 'status' => $status,
            'approved_by' => $status==='approved' ? $user->id : null,
            'approved_at' => $status==='approved' ? now() : null,
        ]));
        if ($status==='approved') $this->syncDays($targetUser, $data['leave_type_id'], $data['start_date']);
        $m = Carbon::parse($data['start_date'])->format('Y-m');
        return redirect()->route('leave.index',['m'=>$m])->with('success', $status==='approved' ? 'Leave booked.' : 'Request submitted — awaiting approval.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest) {
        if (!$request->user()->isManager()) abort(403);
        $leaveRequest->update(['status'=>'approved','approved_by'=>$request->user()->id,'approved_at'=>now()]);
        $this->syncDays($leaveRequest->user, $leaveRequest->leave_type_id, $leaveRequest->start_date);
        return back()->with('success','Approved.');
    }

    public function decline(Request $request, LeaveRequest $leaveRequest) {
        if (!$request->user()->isManager()) abort(403);
        $request->validate(['reason'=>'nullable|string|max:300']);
        $leaveRequest->update(['status'=>'declined','approved_by'=>$request->user()->id,'approved_at'=>now(),'decline_reason'=>$request->reason]);
        return back()->with('success','Declined.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest) {
        $user = $request->user();
        if ($leaveRequest->user_id !== $user->id && !$user->isManager()) abort(403);
        if ($leaveRequest->isApproved()) $this->syncDays($leaveRequest->user, $leaveRequest->leave_type_id, $leaveRequest->start_date);
        $leaveRequest->update(['status'=>'cancelled']);
        return back()->with('success','Cancelled.');
    }

    public function reschedule(Request $request, LeaveRequest $leaveRequest) {
        $user = $request->user();
        if ($leaveRequest->user_id !== $user->id && !$user->isManager()) abort(403);
        $data = $request->validate(['start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date']);
        $wasApproved = $leaveRequest->isApproved();
        if ($wasApproved) $this->syncDays($leaveRequest->user, $leaveRequest->leave_type_id, $leaveRequest->start_date);
        $leaveRequest->update(array_merge($data, [
            'days_count' => $this->calcDays($data['start_date'], $data['end_date'], $leaveRequest->half_day),
            'status'     => $wasApproved && !$user->isGlobalAdmin() ? 'pending' : $leaveRequest->status,
        ]));
        if ($leaveRequest->isApproved()) $this->syncDays($leaveRequest->user, $leaveRequest->leave_type_id, $data['start_date']);
        $m = Carbon::parse($data['start_date'])->format('Y-m');
        return redirect()->route('leave.index',['m'=>$m])->with('success','Dates updated.');
    }

    public function adjust(Request $request, User $employee) {
        if (!$request->user()->isManager()) abort(403);
        $data = $request->validate([
            'leave_type_id'=>'required|exists:leave_types,id','year'=>'required|integer|min:2020|max:2030',
            'adjustment_days'=>'required|numeric|between:-365,365','reason'=>'required|string|max:300',
        ]);
        LeaveAdjustment::create(array_merge($data,['user_id'=>$employee->id,'type'=>'manual','created_by'=>$request->user()->id]));
        $al = LeaveAllowance::firstOrCreate(
            ['user_id'=>$employee->id,'leave_type_id'=>$data['leave_type_id'],'year'=>$data['year']],
            ['total_days'=>0,'carried_days'=>0,'adjusted_days'=>0,'used_days'=>0]
        );
        $al->increment('adjusted_days', $data['adjustment_days']);
        return back()->with('success','Allowance adjusted.');
    }

    public function addDisruption(Request $request) {
        $user = $request->user();
        if (!$user->org_id) abort(403);
        $data = $request->validate([
            'date'=>'required|date','label'=>'required|string|max:200',
            'time_from'=>'nullable|date_format:H:i','time_to'=>'nullable|date_format:H:i',
        ]);
        LeaveDisruption::create(array_merge($data,['org_id'=>$user->org_id,'created_by'=>$user->id]));
        $m = Carbon::parse($data['date'])->format('Y-m');
        return redirect()->route('leave.index',['m'=>$m])->with('success','Disruption logged.');
    }

    public function deleteDisruption(Request $request, LeaveDisruption $disruption) {
        $user = $request->user();
        if ($disruption->created_by !== $user->id && !$user->isManager()) abort(403);
        $disruption->delete();
        return back()->with('success','Disruption removed.');
    }

    private function calcDays(string $start, string $end, bool $halfDay): float {
        if ($halfDay) return 0.5;
        $days = 0; $cur = new \DateTime($start); $endD = new \DateTime($end);
        while ($cur <= $endD) { if (!in_array((int)$cur->format('N'),[6,7])) $days++; $cur->modify('+1 day'); }
        return (float)$days;
    }

    private function syncDays(User $user, int $ltId, $startDate, bool $reverse = false): void {
        $year = (int)date('Y', strtotime($startDate));
        $al = LeaveAllowance::firstOrCreate(
            ['user_id'=>$user->id,'leave_type_id'=>$ltId,'year'=>$year],
            ['total_days'=>$user->organisation?->default_holiday_days??28,'carried_days'=>0,'adjusted_days'=>0,'used_days'=>0]
        );
        $used = LeaveRequest::where('user_id',$user->id)->where('leave_type_id',$ltId)
            ->where('status','approved')->whereYear('start_date',$year)->sum('days_count');
        $al->update(['used_days'=>max(0,(float)$used)]);
    }
}
