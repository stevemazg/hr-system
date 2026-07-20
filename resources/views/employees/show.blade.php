<x-app-layout>
<div class="max-w-5xl">
    <div class="flex items-start justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-blue-600 flex items-center justify-center text-xl font-bold text-white">{{ $employee->initials }}</div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $employee->full_name }}</h1>
                <p class="text-gray-500 text-sm">{{ $employee->job_title ?: "No job title" }}</p>
                <div class="flex gap-2 mt-1">
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $employee->active ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-500" }}">{{ $employee->active ? "Active" : "Inactive" }}</span>
                    <span class="px-2 py-0.5 rounded text-xs {{ $employee->role === "global_admin" ? "bg-red-100 text-red-700" : ($employee->role === "manager" ? "bg-yellow-100 text-yellow-700" : "bg-gray-100 text-gray-700") }} capitalize">{{ str_replace("_"," ",$employee->role) }}</span>
                </div>
            </div>
        </div>
        @if($user->isManager())
        <a href="{{ route("employees.edit", $employee) }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">Edit</a>
        @endif
    </div>

    <div x-data="{ tab: window.location.hash ? window.location.hash.substring(1) : 'employment' }">
        <div class="flex gap-1 border-b mb-6 overflow-x-auto">
            @foreach(["employment" => "Employment", "personal" => "Personal", "leave" => "Leave", "contracts" => "Contracts", "documents" => "Documents"] as $key => $label)
            <button @click="tab='{{ $key }}'; window.location.hash='{{ $key }}'"
                    :class="tab==='{{ $key }}' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm font-medium whitespace-nowrap">{{ $label }}</button>
            @endforeach
            @if($canViewWages)
            <button @click="tab='wages'; window.location.hash='wages'"
                    :class="tab==='wages' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm font-medium whitespace-nowrap">Wages</button>
            @endif
        </div>

        {{-- EMPLOYMENT --}}
        <div x-show="tab==='employment'">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white border rounded-lg p-5">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Employment Details</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Organisation</dt><dd class="font-medium">{{ $employee->organisation->name ?? "—" }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Job Title</dt><dd class="font-medium">{{ $employee->job_title ?: "—" }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd class="font-medium capitalize">{{ str_replace("_"," ",$employee->employment_type) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Start Date</dt><dd class="font-medium">{{ $employee->start_date?->format("d M Y") ?: "—" }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Hours/Week</dt><dd class="font-medium">{{ $employee->working_hours_per_week }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd class="font-medium text-xs">{{ $employee->email }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd class="font-medium">{{ $employee->phone ?: "—" }}</dd></div>
                    </dl>
                </div>
                <div class="bg-white border rounded-lg p-5">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Reporting</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-gray-500 mb-1">Line Manager</dt><dd class="font-medium">{{ $employee->lineManager?->full_name ?: "—" }}</dd></div>
                        <div><dt class="text-gray-500 mb-2">Direct Reports</dt>
                            @forelse($employee->directReports as $r)
                            <a href="{{ route("employees.show",$r) }}" class="block text-blue-600 hover:underline">{{ $r->full_name }}</a>
                            @empty<dd class="text-gray-400">None</dd>@endforelse
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- PERSONAL --}}
        <div x-show="tab==='personal'">
            @php $d = $employee->personalDetails; @endphp
            <div class="bg-white border rounded-lg p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Personal Details</h3>
                    @if($user->id === $employee->id || $user->isManager())
                    <a href="{{ route("employees.personal.edit",$employee) }}" class="text-sm text-blue-600 hover:underline">Edit</a>
                    @endif
                </div>
                @if($d)
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Date of Birth</dt><dd class="font-medium">{{ $d->date_of_birth?->format("d M Y") ?: "—" }}</dd></div>
                    <div><dt class="text-gray-500">NI Number</dt><dd class="font-medium">{{ $d->national_insurance ?: "—" }}</dd></div>
                    <div><dt class="text-gray-500">Address</dt><dd class="font-medium">{{ collect([$d->address_line1,$d->city,$d->postcode])->filter()->implode(", ") ?: "—" }}</dd></div>
                    <div><dt class="text-gray-500">Right to Work</dt><dd class="font-medium capitalize">{{ str_replace("_"," ",$d->right_to_work) }}</dd></div>
                    <div><dt class="text-gray-500">Emergency Contact</dt><dd class="font-medium">{{ $d->emergency_contact_name ?: "—" }}@if($d->emergency_contact_phone) <span class="text-gray-400 text-xs">({{ $d->emergency_contact_phone }})</span>@endif</dd></div>
                    <div><dt class="text-gray-500">Visa Expiry</dt><dd class="font-medium">{{ $d->visa_expiry?->format("d M Y") ?: "—" }}</dd></div>
                </dl>
                @else
                <p class="text-sm text-gray-400">No personal details recorded. <a href="{{ route("employees.personal.edit",$employee) }}" class="text-blue-600 hover:underline">Add now</a></p>
                @endif
            </div>
        </div>

        {{-- LEAVE --}}
        <div x-show="tab==='leave'">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @foreach($employee->leaveAllowances()->with("leaveType")->where("year", now()->year)->get() as $bal)
                <div class="bg-white border rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ $bal->leaveType->name }}</p>
                    <p class="text-2xl font-bold mt-1">{{ number_format($bal->available,1) }}</p>
                    <p class="text-xs text-gray-400">of {{ number_format($bal->total_entitlement,1) }} days</p>
                    @if($user->isManager())
                    <p class="text-xs text-gray-400 mt-1">Used: {{ $bal->used_days }} | Carried: {{ $bal->carried_days }}</p>
                    @endif
                </div>
                @endforeach
                @if($employee->leaveAllowances()->count() === 0)
                <div class="col-span-4 bg-white border rounded-lg p-6 text-center text-sm text-gray-400">No leave allowances set up.</div>
                @endif
            </div>

            @if($user->isManager())
            <div class="bg-white border rounded-lg p-5 mb-6">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Manual Allowance Adjustment</h3>
                <form method="POST" action="{{ route("leave.adjust",$employee) }}" class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
                    @csrf
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Leave Type</label>
                        <select name="leave_type_id" class="w-full border rounded px-2 py-1.5 text-sm" required>
                            @foreach($employee->organisation?->leaveTypes()->where("active",true)->get() ?? collect() as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Year</label><input type="number" name="year" value="{{ now()->year }}" class="w-full border rounded px-2 py-1.5 text-sm" required></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Days (+/-)</label><input type="number" name="adjustment_days" step="0.5" placeholder="+2 or -1" class="w-full border rounded px-2 py-1.5 text-sm" required></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Reason</label><input type="text" name="reason" placeholder="Reason" class="w-full border rounded px-2 py-1.5 text-sm" required></div>
                    <div><button type="submit" class="w-full px-3 py-1.5 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">Apply</button></div>
                </form>
            </div>
            @endif

            <div class="bg-white border rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b text-sm font-semibold text-gray-700">Leave History</div>
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50"><tr>
                        <th class="px-4 py-2 text-left">Type</th><th class="px-4 py-2 text-left">Dates</th><th class="px-4 py-2 text-left">Days</th><th class="px-4 py-2 text-left">Status</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @forelse($employee->leaveRequests()->with("leaveType")->orderByDesc("start_date")->take(20)->get() as $req)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2"><span class="px-1.5 py-0.5 rounded text-xs" style="background:{{ $req->leaveType->colour }}22;color:{{ $req->leaveType->colour }}">{{ $req->leaveType->name }}</span></td>
                            <td class="px-4 py-2 text-gray-600">{{ $req->start_date->format("d M Y") }}@if(!$req->start_date->eq($req->end_date)) – {{ $req->end_date->format("d M Y") }}@endif</td>
                            <td class="px-4 py-2">{{ $req->days_count }}</td>
                            <td class="px-4 py-2"><span class="px-1.5 py-0.5 rounded text-xs capitalize">{{ $req->status }}</span></td>
                        </tr>
                        @empty<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No leave records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- CONTRACTS --}}
        <div x-show="tab==='contracts'">
            @if($user->isManager())
            <div class="bg-white border rounded-lg p-5 mb-6">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Add Contract</h3>
                <form method="POST" action="{{ route("contracts.store",$employee) }}" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @csrf
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Title</label><input type="text" name="title" placeholder="e.g. Permanent 2026" class="w-full border rounded px-2 py-1.5 text-sm" required></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                        <select name="type" class="w-full border rounded px-2 py-1.5 text-sm">
                            <option value="permanent">Permanent</option><option value="fixed_term">Fixed Term</option><option value="zero_hours">Zero Hours</option><option value="freelance">Freelance</option>
                        </select>
                    </div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Start Date</label><input type="date" name="start_date" class="w-full border rounded px-2 py-1.5 text-sm" required></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Hours/Week</label><input type="number" name="hours_per_week" value="37.5" step="0.5" class="w-full border rounded px-2 py-1.5 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Notice Period (days)</label><input type="number" name="notice_period_days" value="30" class="w-full border rounded px-2 py-1.5 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Probation End</label><input type="date" name="probation_end_date" class="w-full border rounded px-2 py-1.5 text-sm"></div>
                    <div class="col-span-2 md:col-span-3"><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">Add Contract</button></div>
                </form>
            </div>
            @endif
            <div class="space-y-3">
                @forelse($employee->contracts()->orderByDesc("start_date")->get() as $c)
                <div class="bg-white border rounded-lg p-5 {{ $c->is_current ? "border-blue-200" : "" }}">
                    <div class="flex justify-between">
                        <div><p class="font-medium">{{ $c->title }}</p><p class="text-sm text-gray-500 capitalize">{{ str_replace("_"," ",$c->type) }} &bull; {{ $c->hours_per_week }}h/week</p></div>
                        @if($c->is_current)<span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium">Current</span>@endif
                    </div>
                    <dl class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mt-3">
                        <div><dt class="text-xs text-gray-500">Start</dt><dd>{{ $c->start_date->format("d M Y") }}</dd></div>
                        <div><dt class="text-xs text-gray-500">End</dt><dd>{{ $c->end_date?->format("d M Y") ?: "Open-ended" }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Probation</dt><dd>{{ $c->probation_end_date?->format("d M Y") ?: "—" }}@if($c->isInProbation()) <span class="text-orange-500 text-xs ml-1">(active)</span>@endif</dd></div>
                        <div><dt class="text-xs text-gray-500">Notice</dt><dd>{{ $c->notice_period_days }}d</dd></div>
                    </dl>
                </div>
                @empty<div class="bg-white border rounded-lg p-6 text-center text-gray-400 text-sm">No contracts on record.</div>
                @endforelse
            </div>
        </div>

        {{-- DOCUMENTS --}}
        <div x-show="tab==='documents'">
            @if($user->isManager())
            <div class="bg-white border rounded-lg p-5 mb-6">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Upload Document</h3>
                <form method="POST" action="{{ route("documents.store",$employee) }}" enctype="multipart/form-data" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @csrf
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Title</label><input type="text" name="title" class="w-full border rounded px-2 py-1.5 text-sm" required></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                        <select name="category" class="w-full border rounded px-2 py-1.5 text-sm">
                            <option value="contract">Contract</option><option value="right_to_work">Right to Work</option><option value="identification">ID</option><option value="qualification">Qualification</option><option value="general">General</option>
                        </select>
                    </div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Expiry Date</label><input type="date" name="expiry_date" class="w-full border rounded px-2 py-1.5 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">File</label><input type="file" name="document" class="w-full text-sm" required></div>
                    <div class="col-span-2 md:col-span-4"><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">Upload</button></div>
                </form>
            </div>
            @endif
            <div class="bg-white border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr>
                        <th class="px-4 py-3 text-left">Title</th><th class="px-4 py-3 text-left">Category</th><th class="px-4 py-3 text-left">Uploaded</th><th class="px-4 py-3 text-left">Expires</th><th class="px-4 py-3"></th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @forelse($employee->documents()->orderByDesc("created_at")->get() as $doc)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $doc->title }}</td>
                            <td class="px-4 py-3 capitalize text-gray-600">{{ str_replace("_"," ",$doc->category) }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $doc->created_at->format("d M Y") }}</td>
                            <td class="px-4 py-3">@if($doc->expiry_date)<span class="{{ $doc->isExpired() ? "text-red-600 font-medium" : ($doc->isExpiringSoon() ? "text-orange-500" : "text-gray-600") }} text-xs">{{ $doc->expiry_date->format("d M Y") }}</span>@else<span class="text-gray-300 text-xs">—</span>@endif</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route("documents.download",$doc) }}" class="text-blue-600 text-xs hover:underline">Download</a></td>
                        </tr>
                        @empty<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No documents.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- WAGES (admin only) --}}
        @if($canViewWages)
        <div x-show="tab==='wages'">
            <div class="bg-white border rounded-lg p-5 mb-6">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Add Wage Record</h3>
                <form method="POST" action="{{ route("wages.store",$employee) }}" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @csrf
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Effective Date</label><input type="date" name="effective_date" class="w-full border rounded px-2 py-1.5 text-sm" required></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Salary / Amount (£)</label><input type="number" name="salary" step="0.01" placeholder="28000" class="w-full border rounded px-2 py-1.5 text-sm" required></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Pay Frequency</label>
                        <select name="pay_frequency" class="w-full border rounded px-2 py-1.5 text-sm">
                            <option value="monthly">Monthly</option><option value="annual">Annual</option><option value="weekly">Weekly</option>
                        </select>
                    </div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Basis</label>
                        <select name="pay_basis" class="w-full border rounded px-2 py-1.5 text-sm">
                            <option value="salary">Salary</option><option value="hourly">Hourly</option>
                        </select>
                    </div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Notes</label><input type="text" name="notes" class="w-full border rounded px-2 py-1.5 text-sm"></div>
                    <div class="flex items-end"><button type="submit" class="w-full px-4 py-1.5 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">Add</button></div>
                </form>
            </div>
            <div class="bg-white border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr>
                        <th class="px-4 py-3 text-left">Effective</th><th class="px-4 py-3 text-left">Amount</th><th class="px-4 py-3 text-left">Annual</th><th class="px-4 py-3 text-left">Frequency</th><th class="px-4 py-3 text-left">Notes</th><th class="px-4 py-3 text-left">Added by</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @forelse($wageHistory as $w)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $w->effective_date->format("d M Y") }}</td>
                            <td class="px-4 py-3">£{{ number_format($w->salary,2) }}</td>
                            <td class="px-4 py-3 text-gray-500">£{{ number_format($w->annual_salary,0) }}/yr</td>
                            <td class="px-4 py-3 capitalize text-gray-600">{{ $w->pay_frequency }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ $w->notes ?: "—" }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ $w->creator->full_name }}</td>
                        </tr>
                        @empty<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No wage records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
</x-app-layout>