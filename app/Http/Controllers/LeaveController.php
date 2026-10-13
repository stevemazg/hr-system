<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\LeaveRequestNotification;
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
        $orgId   = $user->org_id;
        $isAdmin = $user->isGlobalAdmin();
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
            ->whereIn('status',['pending','approved'])
            ->where('start_date','<=',$calEnd)->where('end_date','>=',$calStart)->get();

        $bankHolidays = PublicHoliday::when(!$isAdmin, fn($q)=>$q->where('org_id',$orgId))->where('active',true)
            ->whereBetween('date',[$calStart,$calEnd])->get()
            ->keyBy(fn($b) => $b->date->format('Y-m-d'));

        $disruptions = LeaveDisruption::with('creator')->when(!$isAdmin,fn($q)=>$q->where('org_id',$orgId))
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
                        'first_name'      => $req->user->first_name,
                        'user_id'         => $req->user_id,
                        'is_me'           => $isMe,
                    ];
                }
                $cur->addDay();
            }
        }

        $myRequests = LeaveRequest::with('leaveType')->where('user_id',$user->id)
            ->orderByDesc('start_date')->take(30)->get();
        // Show ALL companies' leave (not just same org)
        $teamLeave = LeaveRequest::with(['user','leaveType'])
            ->whereIn('status',['pending','approved'])->where('end_date','>=',now()->subDays(7))
            ->orderBy('start_date')->get();
        $orgModel2   = $isAdmin ? \App\Models\Organisation::where('active',true)->first() : \App\Models\Organisation::find($orgId);
        $myLeaveYear = $this->leaveYearForDate($orgModel2, now()->format('Y-m-d'));

        // Build myBalances with live cross-org calculation (same pattern as employee show page)
        $myAllLeaveTypesByName = \App\Models\LeaveType::all()->groupBy('name')
            ->map(fn($g) => $g->pluck('id')->toArray());
        $myYrSStr = Carbon::create($myLeaveYear, $orgModel2->leave_year_month_start??1, $orgModel2->leave_year_day_start??1)->format('Y-m-d');
        $myYrEStr = Carbon::create($myLeaveYear, $orgModel2->leave_year_month_start??1, $orgModel2->leave_year_day_start??1)->addYear()->subDay()->format('Y-m-d');
        $myTodayStr = now()->toDateString();

        $rawMyBals = $user->leaveAllowances()->with('leaveType')->where('year',$myLeaveYear)->get()
            ->map(function($bal) use ($user, $myYrSStr, $myYrEStr, $myTodayStr, $myAllLeaveTypesByName) {
                $ltName = $bal->leaveType->name;
                $qIds = $myAllLeaveTypesByName->get($ltName, [$bal->leave_type_id]);
                $bal->live_approved = (float) LeaveRequest::where('user_id',$user->id)
                    ->whereIn('leave_type_id',$qIds)->where('status','approved')
                    ->where('start_date','<=',$myTodayStr)->whereBetween('start_date',[$myYrSStr,$myYrEStr])->sum('days_count');
                $bal->live_pending = (float) LeaveRequest::where('user_id',$user->id)
                    ->whereIn('leave_type_id',$qIds)->where('status','approved')
                    ->where('start_date','>',$myTodayStr)->whereBetween('start_date',[$myYrSStr,$myYrEStr])->sum('days_count')
                + (float) LeaveRequest::where('user_id',$user->id)
                    ->whereIn('leave_type_id',$qIds)->where('status','pending')
                    ->whereBetween('start_date',[$myYrSStr,$myYrEStr])->sum('days_count');
                $bal->total_activity = $bal->live_approved + $bal->live_pending;
                return $bal;
            });
        // Deduplicate same-named types — keep highest total entitlement
        $myBalances = $rawMyBals->groupBy(fn($b) => $b->leaveType->name)
            ->map(fn($grp) => $grp->sortByDesc(fn($b) => (float)$b->total_days+(float)$b->carried_days+(float)$b->adjusted_days)->first())
            ->values()->filter()->sortBy(fn($b) => $b->leaveType->sort_order ?? 99)->values();
        // Sync Unpaid balance card to reflect AL overage when AL is overdrawn
        $_alBal = $myBalances->first(fn($b) => $b->leaveType->name === 'Annual Leave');
        $_unpaidBal = $myBalances->first(fn($b) => $b->leaveType->name === 'Unpaid');
        if ($_alBal && $_unpaidBal) {
            $_alOverage = max(0.0, ((float)$_alBal->live_approved + (float)$_alBal->live_pending) - (float)$_alBal->total_entitlement);
            if ($_alOverage > (float)($_unpaidBal->live_approved ?? 0.0)) {
                $_unpaidBal->live_approved = $_alOverage;
            }
        }

        $pendingRequests = $user->isManager()
            ? LeaveRequest::with(['user','leaveType'])->when(!$isAdmin,fn($q)=>$q->where('org_id',$orgId))->where('status','pending')->latest()->get()
            : collect();
        $allDisruptions = $user->isManager()
            ? LeaveDisruption::with('creator')->when(!$isAdmin,fn($q)=>$q->where('org_id',$orgId))->where('date','>=',today())->orderBy('date')->get()
            : collect();


        // Per-staff leave stats for top cards (live from requests, proper leave year)
        $colours = ["#3b82f6","#10b981","#f59e0b","#ef4444","#8b5cf6","#ec4899","#06b6d4","#f97316"];
        // Show all active users across all companies
        $allStaff = User::where("active",true)->orderBy("first_name")->get();
        $orgModel = $isAdmin ? \App\Models\Organisation::where("active",true)->first() : \App\Models\Organisation::find($orgId);
        $currLeaveYear = $this->leaveYearForDate($orgModel, now()->format('Y-m-d'));
        [$yrStart, $yrEnd] = $this->leaveYearDates($orgModel, $currLeaveYear);
        $staffBalances = [];
        $staffColourMap = [];
        foreach ($allStaff as $idx => $emp) {
            $colour = $colours[$idx % count($colours)];
            $staffColourMap[$emp->id] = $colour;
            $al = $emp->leaveAllowances()->with("leaveType")->where("year",$currLeaveYear)
                ->whereHas("leaveType",fn($q)=>$q->where("name","Annual Leave"))->first();
            $alTypeId = $al ? $al->leave_type_id : null;
            $todayStr = now()->toDateString();
            // Use ALL Annual Leave type IDs to catch cross-org requests
            $allAlTypeIds = \App\Models\LeaveType::where("name","Annual Leave")->pluck("id")->toArray();
            $pastApproved = !empty($allAlTypeIds) ? (float)LeaveRequest::where("user_id",$emp->id)
                ->whereIn("leave_type_id",$allAlTypeIds)->where("status","approved")
                ->where("start_date","<=",$todayStr)
                ->whereBetween("start_date",[$yrStart,$yrEnd])->sum("days_count") : 0;
            $futureApproved = !empty($allAlTypeIds) ? (float)LeaveRequest::where("user_id",$emp->id)
                ->whereIn("leave_type_id",$allAlTypeIds)->where("status","approved")
                ->where("start_date",">",$todayStr)
                ->whereBetween("start_date",[$yrStart,$yrEnd])->sum("days_count") : 0;
            $pendingApproval = !empty($allAlTypeIds) ? (float)LeaveRequest::where("user_id",$emp->id)
                ->whereIn("leave_type_id",$allAlTypeIds)->where("status","pending")
                ->whereBetween("start_date",[$yrStart,$yrEnd])->sum("days_count") : 0;
            $upcoming = $futureApproved + $pendingApproval;
            $allSkTypeIds = \App\Models\LeaveType::whereIn("name",["Sick","Sickness"])->pluck("id")->toArray();
            $sickUsed = !empty($allSkTypeIds) ? (float)LeaveRequest::where("user_id",$emp->id)
                ->whereIn("leave_type_id",$allSkTypeIds)->where("status","approved")
                ->whereBetween("start_date",[$yrStart,$yrEnd])->sum("days_count") : 0;
            $allUnpaidTypeIds = \App\Models\LeaveType::where("name","Unpaid")->pluck("id")->toArray();
            $unpaidFromRequests = !empty($allUnpaidTypeIds) ? (float)LeaveRequest::where("user_id",$emp->id)
                ->whereIn("leave_type_id",$allUnpaidTypeIds)->where("status","approved")
                ->whereBetween("start_date",[$yrStart,$yrEnd])->sum("days_count") : 0;
            // Also surface AL overage as unpaid when no formal Unpaid requests exist
            $_alTotal = $al ? (float)$al->total_entitlement : 0.0;
            $unpaidUsed = max($unpaidFromRequests, max(0.0, -(float)($_alTotal - $pastApproved - $upcoming)));
            $total = $al ? $al->total_entitlement : 0;
            $staffBalances[] = [
                "id"=>$emp->id,"name"=>$emp->first_name,"full_name"=>$emp->full_name,
                "colour"=>$colour,"approved"=>$pastApproved,"pending"=>$upcoming,"upcoming"=>$upcoming,"total"=>$total,
                "remaining"=>$total-$pastApproved-$upcoming,
                "sick"=>$sickUsed,
                "unpaid"=>$unpaidUsed,
                "avatar_path"=>$emp->avatar_path,
            ];
        }

        // Bank holiday lookup for tile display
        $bhDateMap = \App\Models\PublicHoliday::where("org_id",$orgId ?? $user->org_id)
            ->get()->keyBy(fn($b) => \Carbon\Carbon::parse($b->date)->format("Y-m-d"));
        return view('leave.index', compact(
            'user','month','month1','month2','month3','prevM','nextM',
            'dayMap','bankHolidays','disruptions','myRequests','teamLeave',
            'myBalances','pendingRequests','allDisruptions','staffBalances','staffColourMap','bhDateMap'
        ));
    }

    public function create(Request $request) {
        $user = $request->user();
        $targetUser = $request->has('employee_id') && $user->isManager()
            ? User::findOrFail($request->employee_id) : $user;

        // Use proper leave year (not calendar year) — matches dashboard tile logic
        $org = $targetUser->organisation ?? \App\Models\Organisation::find($targetUser->org_id);
        $leaveYear = $this->leaveYearForDate($org, now()->format('Y-m-d'));
        [$yrStart, $yrEnd] = $this->leaveYearDates($org, $leaveYear);
        $today = now()->toDateString();

        $leaveTypes = LeaveType::where('org_id',$targetUser->org_id)->where('active',true)->orderBy('sort_order')->get();
        $balances   = $targetUser->leaveAllowances()->with('leaveType')->where('year',$leaveYear)->get()
            ->sortBy(fn($b) => $b->leaveType->sort_order ?? 99)->values();

        // Live-recalculate balances to match dashboard "Left" tile:
        // available = total_entitlement - liveUsed - livePending
        foreach ($balances as $bal) {
            $sameNameTypeIds = LeaveType::where('name', $bal->leaveType->name)->pluck('id')->toArray();
            $liveUsed = (float) LeaveRequest::where('user_id', $targetUser->id)
                ->whereIn('leave_type_id', $sameNameTypeIds)
                ->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->whereBetween('start_date', [$yrStart, $yrEnd])
                ->sum('days_count');
            $livePending = (float) LeaveRequest::where('user_id', $targetUser->id)
                ->whereIn('leave_type_id', $sameNameTypeIds)
                ->where('status', 'approved')
                ->where('start_date', '>', $today)
                ->whereBetween('start_date', [$yrStart, $yrEnd])
                ->sum('days_count')
            + (float) LeaveRequest::where('user_id', $targetUser->id)
                ->whereIn('leave_type_id', $sameNameTypeIds)
                ->where('status', 'pending')
                ->whereBetween('start_date', [$yrStart, $yrEnd])
                ->sum('days_count');
            $bal->used_days = $liveUsed + $livePending;
        }

        $start = $request->get('start', now()->format('Y-m-d'));
        $end   = $request->get('end', $start);
        $staffList = $user->isManager()
            ? ($user->isGlobalAdmin()
                ? \App\Models\User::where('active', true)->orderBy('first_name')->get()
                : \App\Models\User::where('org_id', $user->org_id)->where('active', true)->orderBy('first_name')->get())
            : collect();
        return view('leave.create', compact('user','targetUser','leaveTypes','balances','start','end','staffList'));
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
            'confirm_toil'    => 'nullable|boolean',
        ]);
        $targetUser = User::findOrFail($data['user_id']);
        if ($user->id !== $targetUser->id && !$user->isManager()) abort(403);
        $days = $this->calcDays($data['start_date'], $data['end_date'], (bool)($data['half_day'] ?? false), (int)($targetUser->org_id ?? 0));
        $lt   = LeaveType::findOrFail($data['leave_type_id']);

        // ── Overbooking check for Annual Leave types ──────────────────────────
        if ($lt->has_allowance && strtolower($lt->name) === 'annual leave' && !$user->isGlobalAdmin()) {
            $org   = $targetUser->organisation;
            $lym   = $org->leave_year_month_start ?? 1;
            $lyd   = $org->leave_year_day_start   ?? 1;
            $now   = Carbon::now();
            $yrS   = Carbon::create($now->year, $lym, $lyd);
            if ($now->lt($yrS)) $yrS->subYear();
            $yrE   = $yrS->copy()->addYear()->subDay();

            // Get the allowance record for this employee
            $al = LeaveAllowance::where('user_id', $targetUser->id)
                ->where('year', $yrS->year)
                ->whereHas('leaveType', fn($q) => $q->where('name','Annual Leave'))
                ->orderByRaw('(total_days + carried_days + adjusted_days) DESC')
                ->first();

            if ($al) {
                $alTypeIds = LeaveType::where('name','Annual Leave')->pluck('id')->toArray();
                $alreadyBooked = (float) LeaveRequest::where('user_id', $targetUser->id)
                    ->whereIn('leave_type_id', $alTypeIds)
                    ->whereIn('status', ['approved','pending'])
                    ->whereBetween('start_date', [$yrS->format('Y-m-d'), $yrE->format('Y-m-d')])
                    ->sum('days_count');
                $remaining = $al->total_entitlement - $alreadyBooked;

                if ($days > $remaining && !$request->boolean('confirm_toil')) {
                    $overflow = $days - max(0, $remaining);
                    return back()->withInput()->with([
                        'toil_warning' => true,
                        'toil_al_days' => max(0, $remaining),
                        'toil_overflow' => $overflow,
                        'toil_total' => $days,
                    ]);
                }

                // If confirmed: book AL request + auto-create Unpaid for overflow
                if ($days > $remaining && $request->boolean('confirm_toil')) {
                    $overflow  = $days - max(0, $remaining);
                    $alDays    = max(0, $remaining);
                    $status    = (!$lt->requires_approval || $user->isGlobalAdmin()) ? 'approved' : 'pending';
                    // Main AL request (full period, flagged)
                    $req = LeaveRequest::create(array_merge($data, [
                        'org_id' => $targetUser->org_id, 'days_count' => $days, 'status' => $status,
                        'approved_by' => $status==='approved' ? $user->id : null,
                        'approved_at' => $status==='approved' ? now() : null,
                        'notes' => trim(($data['notes'] ?? '').' [Includes '.$overflow.'d Unpaid]'),
                    ]));
                    if ($status==='approved') $this->syncDays($targetUser, $data['leave_type_id'], $data['start_date']);

                    // Auto-create TOIL request for overflow days
                    $unpaidType = LeaveType::where('name','Unpaid')->where('org_id', $targetUser->org_id)->first();
                    if ($unpaidType && $overflow > 0) {
                        $toilReq = LeaveRequest::create([
                            'user_id'      => $targetUser->id,
                            'org_id'       => $targetUser->org_id,
                            'leave_type_id'=> $unpaidType->id,
                            'start_date'   => $data['start_date'],
                            'end_date'     => $data['end_date'],
                            'days_count'   => $overflow,
                            'status'       => $status,
                            'half_day'     => $data['half_day'] ?? false,
                            'half_day_period' => $data['half_day_period'] ?? null,
                            'notes'        => 'Auto: '.number_format($overflow,1).'d Unpaid — annual leave allowance exceeded',
                            'approved_by'  => $status==='approved' ? $user->id : null,
                            'approved_at'  => $status==='approved' ? now() : null,
                        ]);
                        if ($status==='approved') $this->syncDays($targetUser, $unpaidType->id, $data['start_date']);
                    }

                    $m = Carbon::parse($data['start_date'])->format('Y-m');
                    return redirect()->route('leave.index',['m'=>$m])->with('success',
                        'Leave booked. '.number_format($overflow,1).'d recorded as Unpaid as annual leave is fully used.'
                    );
                }
            }
        }
        // ── Normal booking ────────────────────────────────────────────────────
        $status = (!$lt->requires_approval || $user->isGlobalAdmin()) ? 'approved' : 'pending';
        if (empty($data['half_day'])) $data['half_day_period'] = null;
        $leaveRequest = LeaveRequest::create(array_merge($data, [
            'org_id' => $targetUser->org_id, 'days_count' => $days, 'status' => $status,
            'approved_by' => $status==='approved' ? $user->id : null,
            'approved_at' => $status==='approved' ? now() : null,
        ]));
        if ($status==='approved') $this->syncDays($targetUser, $data['leave_type_id'], $data['start_date']);
        $m = Carbon::parse($data['start_date'])->format('Y-m');
        if ($status === 'pending') {
            $this->notifyManagersOfLeaveRequest($leaveRequest, $targetUser);
        }
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
            'days_count' => $this->calcDays($data['start_date'], $data['end_date'], $leaveRequest->half_day, (int)($leaveRequest->user->org_id ?? 0)),
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

    public function edit(Request $request, LeaveRequest $leaveRequest) {
        if (!$request->user()->isManager()) abort(403);
        $targetUser = $leaveRequest->user;
        $leaveTypes = LeaveType::where('org_id', $targetUser->org_id)->where('active', true)->orderBy('sort_order')->get();
        return view('leave.edit', compact('leaveRequest', 'targetUser', 'leaveTypes'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest) {
        if (!$request->user()->isManager()) abort(403);
        $data = $request->validate([
            'leave_type_id'   => 'required|exists:leave_types,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'half_day'        => 'nullable|boolean',
            'half_day_period' => 'nullable|in:am,pm',
            'status'          => 'required|in:pending,approved,declined,cancelled',
            'notes'           => 'nullable|string|max:500',
        ]);
        $wasApproved = $leaveRequest->isApproved();
        $oldLtId     = $leaveRequest->leave_type_id;
        $oldStart    = $leaveRequest->start_date;
        $days        = $this->calcDays($data['start_date'], $data['end_date'], (bool)($data['half_day'] ?? false), (int)($leaveRequest->user->org_id ?? 0));
        // Resync old record out before changing
        if ($wasApproved) $this->syncDays($leaveRequest->user, $oldLtId, $oldStart->format('Y-m-d'));
        $leaveRequest->update(array_merge($data, [
            'days_count'  => $days,
            'approved_by' => $data['status'] === 'approved' ? $request->user()->id : $leaveRequest->approved_by,
            'approved_at' => $data['status'] === 'approved' ? now() : $leaveRequest->approved_at,
        ]));
        // Sync updated record in
        if ($data['status'] === 'approved') $this->syncDays($leaveRequest->user, $data['leave_type_id'], $data['start_date']);
        return redirect()->route('employees.show', $leaveRequest->user_id)->with('success', 'Leave entry updated.');
    }

    public function destroy(Request $request, LeaveRequest $leaveRequest) {
        if (!$request->user()->isGlobalAdmin()) abort(403);
        $wasApproved = $leaveRequest->isApproved();
        $empUser     = $leaveRequest->user;
        $ltId        = $leaveRequest->leave_type_id;
        $startDate   = $leaveRequest->start_date->format('Y-m-d');
        $empId       = $leaveRequest->user_id;
        $leaveRequest->delete();
        if ($wasApproved) $this->syncDays($empUser, $ltId, $startDate);
        return redirect()->route('employees.show', $empId)->with('success', 'Leave entry deleted.');
    }

    private function calcDays(string $start, string $end, bool $halfDay, int $orgId = 0): float {
        if ($halfDay) return 0.5;
        // Load bank holiday dates for this org to exclude them
        $bhDates = [];
        if ($orgId > 0) {
            $bhDates = \App\Models\PublicHoliday::where('org_id', $orgId)
                ->where('active', true)
                ->whereBetween('date', [$start, $end])
                ->pluck('date')
                ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
                ->toArray();
        }
        $days = 0;
        $cur  = new \DateTime($start);
        $endD = new \DateTime($end);
        while ($cur <= $endD) {
            $dow = (int)$cur->format('N'); // 1=Mon … 7=Sun
            $ds  = $cur->format('Y-m-d');
            if (!in_array($dow, [6, 7]) && !in_array($ds, $bhDates)) $days++;
            $cur->modify('+1 day');
        }
        return (float)$days;
    }

    // ── Leave year helpers ─────────────────────────────────────────
    private function leaveYearForDate(\App\Models\Organisation $org, string $date): int {
        $d   = \Carbon\Carbon::parse($date);
        $yrS = \Carbon\Carbon::create($d->year, $org->leave_year_month_start ?? 1, $org->leave_year_day_start ?? 1);
        return $d->gte($yrS) ? $d->year : $d->year - 1;
    }

    private function leaveYearDates(\App\Models\Organisation $org, int $leaveYear): array {
        $m   = $org->leave_year_month_start ?? 1;
        $day = $org->leave_year_day_start   ?? 1;
        $s   = \Carbon\Carbon::create($leaveYear, $m, $day);
        return [$s->format('Y-m-d'), $s->copy()->addYear()->subDay()->format('Y-m-d')];
    }

    public function currentLeaveYear(\App\Models\Organisation $org): int {
        return $this->leaveYearForDate($org, now()->format('Y-m-d'));
    }

    // ── Sync used_days from approved requests ───────────────────
    private function syncDays(User $user, int $ltId, $startDate, bool $reverse = false): void {
        $org  = $user->organisation ?? \App\Models\Organisation::find($user->org_id);
        $year = $this->leaveYearForDate($org, $startDate);
        [$yrStart, $yrEnd] = $this->leaveYearDates($org, $year);
        $lt          = LeaveType::find($ltId);
        $defaultDays = ($lt && $lt->has_allowance) ? ($org->default_holiday_days ?? 28) : 0;
        $al = LeaveAllowance::firstOrCreate(
            ['user_id'=>$user->id,'leave_type_id'=>$ltId,'year'=>$year],
            ['total_days'=>$defaultDays,'carried_days'=>0,'adjusted_days'=>0,'used_days'=>0]
        );
        $used = LeaveRequest::where('user_id',$user->id)->where('leave_type_id',$ltId)
            ->where('status','approved')
            ->whereBetween('start_date', [$yrStart, $yrEnd])
            ->sum('days_count');
        $al->update(['used_days'=>max(0,(float)$used)]);
    }

    private function applyOrgMailConfigForLeave(int $orgId): void {
        $org = \App\Models\Organisation::find($orgId);
        if (!$org) {
            $org = \App\Models\Organisation::where('active', true)->whereNotNull('smtp_host')->whereNotNull('smtp_username')->first();
        }
        if (!$org || $org->mail_driver !== 'smtp' || !$org->smtp_host || !$org->smtp_username) return;
        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $org->smtp_host,
            'mail.mailers.smtp.port'       => $org->smtp_port ?? 587,
            'mail.mailers.smtp.encryption' => $org->smtp_encryption ?? 'tls',
            'mail.mailers.smtp.username'   => $org->smtp_username,
            'mail.mailers.smtp.password'   => $org->smtp_password,
            'mail.from.address'            => $org->smtp_from_email ?: config('mail.from.address'),
            'mail.from.name'               => $org->smtp_from_name  ?: config('mail.from.name'),
        ]);
        Mail::purge('smtp');
    }

    private function notifyManagersOfLeaveRequest(LeaveRequest $leaveRequest, User $requester): void {
        try {
            $this->applyOrgMailConfigForLeave((int)$requester->org_id);
            $managers = User::where('org_id', $requester->org_id)
                ->whereIn('role', ['manager', 'admin', 'global_admin'])
                ->where('active', true)
                ->where('id', '!=', $requester->id)
                ->whereNotNull('email')
                ->get();
            // Also include global admins from any org
            $globalAdmins = User::where('role', 'global_admin')
                ->where('active', true)
                ->where('id', '!=', $requester->id)
                ->whereNotNull('email')
                ->get();
            $recipients = $managers->merge($globalAdmins)->unique('id');
            $leaveRequest->load('leaveType');
            foreach ($recipients as $mgr) {
                Mail::to($mgr->email)->send(new LeaveRequestNotification($leaveRequest, $requester));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Leave request notification failed: ' . $e->getMessage());
        }
    }

}