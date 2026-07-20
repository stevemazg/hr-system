<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organisation;
use App\Models\LeaveType;

class OrganisationController extends Controller {

    public function index(Request $request) {
        if (!$request->user()->isGlobalAdmin()) abort(403);
        $organisations = Organisation::withCount('users')->where('active', true)->orderBy('name')->get();
        return view('organisations.index', compact('organisations'));
    }

    public function edit(Request $request, Organisation $organisation) {
        if (!$request->user()->isGlobalAdmin()) abort(403);
        $leaveTypes = $organisation->leaveTypes()->orderBy('sort_order')->get();
        return view('organisations.edit', compact('organisation', 'leaveTypes'));
    }

    public function update(Request $request, Organisation $organisation) {
        if (!$request->user()->isGlobalAdmin()) abort(403);
        $data = $request->validate([
            'name'                       => 'required|string|max:150',
            'default_holiday_days'       => 'required|integer|min:0|max:365',
            'max_carry_forward_days'     => 'required|integer|min:0',
            'carry_forward_enabled'      => 'boolean',
            'leave_year_month_start'     => 'required|integer|min:1|max:12',
            'leave_year_day_start'       => 'required|integer|min:1|max:28',
        ]);
        $data['carry_forward_enabled'] = $request->boolean('carry_forward_enabled');
        $organisation->update($data);
        return redirect()->route('organisations.edit', $organisation)->with('success', 'Organisation updated.');
    }

    public function updateLeaveType(Request $request, Organisation $organisation, LeaveType $leaveType) {
        if (!$request->user()->isGlobalAdmin()) abort(403);
        if ($leaveType->org_id !== $organisation->id) abort(403);

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'colour'           => 'required|string|max:7',
            'paid'             => 'boolean',
            'carries_forward'  => 'boolean',
            'requires_approval'=> 'boolean',
            'has_allowance'    => 'boolean',
            'active'           => 'boolean',
        ]);
        foreach (['paid','carries_forward','requires_approval','has_allowance','active'] as $bool) {
            $data[$bool] = $request->boolean($bool);
        }
        $leaveType->update($data);
        return redirect()->route('organisations.edit', $organisation)->with('success', 'Leave type updated.');
    }

    public function storeLeaveType(Request $request, Organisation $organisation) {
        if (!$request->user()->isGlobalAdmin()) abort(403);
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'colour'           => 'required|string|max:7',
            'paid'             => 'boolean',
            'carries_forward'  => 'boolean',
            'requires_approval'=> 'boolean',
            'has_allowance'    => 'boolean',
        ]);
        foreach (['paid','carries_forward','requires_approval','has_allowance'] as $bool) {
            $data[$bool] = $request->boolean($bool);
        }
        $data['org_id'] = $organisation->id;
        $data['sort_order'] = $organisation->leaveTypes()->max('sort_order') + 1;
        LeaveType::create($data);
        return redirect()->route('organisations.edit', $organisation)->with('success', 'Leave type added.');
    }
}
