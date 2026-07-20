<x-app-layout>
<style>
.hol-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:#e5e7eb}
.hol-cal-hdr{background:#f3f4f6;text-align:center;padding:.3rem 0;font-size:.65rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em}
.hol-day{background:#fff;min-height:68px;padding:4px 5px;position:relative;font-size:.7rem;transition:filter .12s}
.hol-day.clickable:hover{filter:brightness(.91);cursor:pointer}
.hol-daynum{display:block;font-size:.75rem;font-weight:500;line-height:1.3}
.hol-today{outline:2px solid #1d4ed8;outline-offset:-2px}
.hol-today .hol-daynum{font-weight:800}
.hol-weekend{background:#f9fafb}.hol-weekend .hol-daynum{color:#d1d5db}
.hol-bh{background:#ede9fe}
.hol-my-approved{background:#d1fae5}
.hol-my-pending{background:#fef9c3}
.hol-bh-badge{position:absolute;top:2px;right:3px;font-size:.52rem;font-weight:700;color:#5b21b6;background:#ddd6fe;padding:0 3px;border-radius:3px;line-height:1.5}
.hol-chip{display:block;font-size:.6rem;font-weight:600;margin-top:2px;border-radius:3px;padding:1px 3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.hol-my-chip{background:rgba(22,101,52,.12);color:#166534;cursor:pointer}
.hol-my-chip:hover{background:rgba(22,101,52,.25)}
.hol-my-chip.pending{background:rgba(161,98,7,.12);color:#92400e}
.hol-other-chip{background:rgba(30,58,95,.08);color:#1e3a5f}
.hol-dis-chip{background:#fff7ed;color:#c2410c;border-left:2px solid #f97316;cursor:pointer}
.hol-dis-chip:hover{background:#fed7aa}
.bal-bar{background:#e5e7eb;border-radius:999px;height:7px;position:relative;overflow:hidden}
.bal-fill{height:100%;border-radius:999px;position:absolute;left:0;top:0}
</style>

<div class="max-w-6xl" x-data="leaveApp()" x-init="init()">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Leave</h1>
            @if($pendingRequests->count())
            <p class="text-sm text-orange-600 mt-0.5">{{ $pendingRequests->count() }} pending request{{ $pendingRequests->count()>1?'s':'' }} awaiting action</p>
            @endif
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('leave.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">+ Book Leave</a>
            <button @click="disruptionModal=true" class="px-4 py-2 bg-orange-50 border border-orange-200 text-orange-700 rounded-md text-sm font-medium hover:bg-orange-100">⚡ + Disruption</button>
        </div>
    </div>

    {{-- Balance bars --}}
    @if($myBalances->count())
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        @foreach($myBalances as $bal)
        <div class="bg-white border rounded-lg px-4 py-3">
            <p class="text-xs text-gray-500 uppercase font-medium mb-1">{{ $bal->leaveType->name }}</p>
            <div class="bal-bar mb-1.5">
                @php $pct = $bal->total_entitlement > 0 ? min(100, ($bal->used_days/$bal->total_entitlement)*100) : 0; @endphp
                <div class="bal-fill {{ $pct>=90?'bg-red-500':($pct>=70?'bg-amber-400':'bg-emerald-500') }}" style="width:{{ $pct }}%"></div>
            </div>
            <p class="text-xs text-gray-600"><span class="font-semibold text-gray-900 text-sm">{{ number_format($bal->available,1) }}</span> of {{ number_format($bal->total_entitlement,1) }}d left</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Pending approvals (manager) --}}
    @if($pendingRequests->count())
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-5">
        <p class="text-sm font-semibold text-amber-800 mb-3">⏳ Pending approvals</p>
        <div class="space-y-2">
            @foreach($pendingRequests as $req)
            <div class="flex items-center justify-between flex-wrap gap-2 bg-white rounded border px-3 py-2 text-sm">
                <div>
                    <span class="font-medium">{{ $req->user->full_name }}</span>
                    <span class="text-gray-500 ml-2">{{ $req->start_date->format('d M') }}@if(!$req->start_date->eq($req->end_date)) – {{ $req->end_date->format('d M') }}@endif</span>
                    <span class="ml-2 px-1.5 py-0.5 rounded text-xs font-medium" style="background:{{ $req->leaveType->colour }}22;color:{{ $req->leaveType->colour }}">{{ $req->leaveType->name }}</span>
                    <span class="text-gray-400 text-xs ml-1">{{ $req->days_count }}d</span>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('leave.approve',$req) }}">@csrf<button class="px-3 py-1 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700">✓ Approve</button></form>
                    <form method="POST" action="{{ route('leave.decline',$req) }}">@csrf<button class="px-3 py-1 border border-red-400 text-red-600 rounded text-xs font-medium hover:bg-red-50">✕ Decline</button></form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Month nav --}}
    <div class="flex items-center justify-between mb-3">
        <a href="{{ route('leave.index',['m'=>$prevM]) }}" class="px-3 py-1.5 border rounded text-sm text-gray-600 hover:bg-gray-50">← Prev</a>
        <span class="font-semibold text-gray-800">{{ $month1->format('F Y') }} — {{ $month3->format('M Y') }}</span>
        <a href="{{ route('leave.index',['m'=>$nextM]) }}" class="px-3 py-1.5 border rounded text-sm text-gray-600 hover:bg-gray-50">Next →</a>
    </div>

    {{-- Calendar --}}
    @php
    $months = [$month1, $month2, $month3];
    $today  = now()->format('Y-m-d');
    @endphp

    @foreach($months as $mi => $mon)
    <div class="mb-4 border rounded-lg overflow-hidden">
        <div class="px-4 py-2.5 font-semibold text-sm text-white" style="background:#1e40af">{{ $mon->format('F Y') }}</div>
        <div class="hol-cal-grid">
            @foreach(['Mo','Tu','We','Th','Fr','Sa','Su'] as $dh)
            <div class="hol-cal-hdr">{{ $dh }}</div>
            @endforeach
            @php
            $daysInMonth = (int)$mon->format('t');
            $firstDow    = (int)Carbon\Carbon::parse($mon->format('Y-m').'-01')->format('N');
            @endphp
            @for($i=1;$i<$firstDow;$i++)<div class="hol-day hol-weekend"></div>@endfor
            @for($d=1;$d<=$daysInMonth;$d++)
            @php
            $ds      = $mon->format('Y-m').'-'.str_pad($d,2,'0',STR_PAD_LEFT);
            $dow     = (int)\Carbon\Carbon::parse($ds)->format('N');
            $isWe    = $dow >= 6;
            $isBH    = isset($bankHolidays[$ds]);
            $events  = $dayMap[$ds] ?? [];
            $disEvs  = $disruptions[$ds] ?? collect();
            $myEv    = collect($events)->first(fn($e) => $e['is_me'] ?? false);
            $others  = collect($events)->filter(fn($e) => !($e['is_me'] ?? false));
            $cellCls = $isWe ? 'hol-weekend' : ($isBH ? 'hol-bh' : ($myEv ? ($myEv['type']==='my_approved'?'hol-my-approved':'hol-my-pending') : ''));
            $isToday = $ds === $today;
            $click   = !$isWe && !$isBH;
            @endphp
            <div class="hol-day {{ $cellCls }} {{ $isToday?'hol-today':'' }} {{ $click?'clickable':'' }}"
                 @if($click)
                 @if($myEv)
                 @click="openEdit({{ $myEv['req_id'] }},'{{ $myEv['req_start'] }}','{{ $myEv['req_end'] }}','{{ $myEv['req_status'] }}')"
                 @else
                 @click="openBook('{{ $ds }}')"
                 @endif
                 @endif>
                <span class="hol-daynum">{{ $d }}</span>
                @if($isBH)<span class="hol-bh-badge">BH</span>@endif
                @if($myEv)
                <span class="hol-chip hol-my-chip {{ $myEv['type']==='my_pending'?'pending':'' }}"
                      @click.stop="openEdit({{ $myEv['req_id'] }},'{{ $myEv['req_start'] }}','{{ $myEv['req_end'] }}','{{ $myEv['req_status'] }}')"
                      title="{{ $myEv['req_type'] }} — click to edit">
                    Me {{ $myEv['type']==='my_approved'?'✓':'(pend)' }} ✏️@if($myEv['half_day']) ½@endif
                </span>
                @endif
                @foreach($others->take(3) as $o)
                <span class="hol-chip hol-other-chip" title="{{ $o['name'] }}: {{ $o['req_type'] }} ({{ $o['req_status'] }})">
                    {{ $o['initials'] }}@if($o['half_day']) ½@endif
                    <span style="font-size:.52rem;opacity:.7">{{ $o['days'] }}d</span>
                </span>
                @endforeach
                @foreach($disEvs as $dis)
                <span class="hol-chip hol-dis-chip"
                      @click.stop="openDisEdit({{ $dis->id }},'{{ $dis->date->format('Y-m-d') }}','{{ addslashes($dis->label) }}','{{ $dis->time_from }}','{{ $dis->time_to }}')"
                      title="{{ $dis->label }}">
                    ⚡ {{ $dis->creator->first_name }}@if($dis->time_from) {{ \Carbon\Carbon::parse($dis->time_from)->format('g:ia') }}@endif
                </span>
                @endforeach
            </div>
            @endfor
        </div>
    </div>
    @endforeach

    {{-- Legend --}}
    <div class="flex flex-wrap gap-x-5 gap-y-1.5 mb-6 text-xs text-gray-500">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-green-100 border border-green-400 inline-block"></span>My approved</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-yellow-100 border border-yellow-400 inline-block"></span>My pending</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-purple-100 border border-purple-400 inline-block"></span>Bank holiday</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-300 inline-block"></span>Others' leave</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-orange-50 border-l-2 border-orange-400 inline-block"></span>Disruption</span>
    </div>

    {{-- Team leave table --}}
    <div class="bg-white border rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between flex-wrap gap-2">
            <h3 class="text-sm font-semibold text-gray-700">Team Leave</h3>
            <div class="flex gap-1.5 flex-wrap" x-data>
                <button @click="toggleFilter('approved')" :class="activeFilters.includes('approved')?'bg-green-600 text-white':'border border-green-600 text-green-600'" class="px-2.5 py-0.5 rounded-full text-xs font-semibold">✅ Approved</button>
                <button @click="toggleFilter('pending')"  :class="activeFilters.includes('pending')?'bg-amber-500 text-white':'border border-amber-500 text-amber-600'" class="px-2.5 py-0.5 rounded-full text-xs font-semibold">⏳ Pending</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr>
                    <th class="px-4 py-2 text-left">Name</th><th class="px-4 py-2 text-left">Dates</th><th class="px-4 py-2 text-left">Days</th><th class="px-4 py-2 text-left">Type</th><th class="px-4 py-2 text-left">Status</th><th class="px-4 py-2"></th>
                </tr></thead>
                <tbody class="divide-y">
                    @forelse($teamLeave as $tl)
                    <tr class="hover:bg-gray-50" x-show="activeFilters.includes('{{ $tl->status }}')">
                        <td class="px-4 py-2.5 font-medium">
                            {{ $tl->user->full_name }}
                            @if($tl->user_id===auth()->id())<span class="ml-1 px-1.5 py-0.5 bg-green-100 text-green-700 rounded text-xs font-semibold">Me</span>@endif
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap text-gray-600">{{ $tl->start_date->format('d M') }}@if(!$tl->start_date->eq($tl->end_date)) – {{ $tl->end_date->format('d M Y') }}@else {{ $tl->start_date->format('Y') }}@endif</td>
                        <td class="px-4 py-2.5">{{ $tl->days_count }}</td>
                        <td class="px-4 py-2.5"><span class="px-1.5 py-0.5 rounded text-xs font-medium" style="background:{{ $tl->leaveType->colour }}22;color:{{ $tl->leaveType->colour }}">{{ $tl->leaveType->name }}</span></td>
                        <td class="px-4 py-2.5"><span class="px-2 py-0.5 rounded text-xs font-medium capitalize {{ ['pending'=>'bg-yellow-100 text-yellow-700','approved'=>'bg-green-100 text-green-700','declined'=>'bg-red-100 text-red-700','cancelled'=>'bg-gray-100 text-gray-500'][$tl->status]??'' }}">{{ $tl->status }}</span></td>
                        <td class="px-4 py-2.5 text-right">
                            @if($tl->user_id===auth()->id() || $user->isManager())
                            <button @click="openEdit({{ $tl->id }},'{{ $tl->start_date->format('Y-m-d') }}','{{ $tl->end_date->format('Y-m-d') }}','{{ $tl->status }}')" class="text-blue-600 text-xs hover:underline">✏️ Edit</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No upcoming leave.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Disruptions list (manager) --}}
    @if($user->isManager() && $allDisruptions->count())
    <div class="bg-white border rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-3 bg-orange-50 border-b text-sm font-semibold text-orange-700">⚡ Upcoming Disruptions</div>
        <div class="divide-y">
            @foreach($allDisruptions as $dis)
            <div class="px-4 py-2.5 flex items-center justify-between gap-3 text-sm">
                <div>
                    <span class="font-medium">{{ $dis->date->format('d M Y') }}</span>
                    <span class="text-gray-500 ml-2">{{ $dis->label }}</span>
                    @if($dis->time_from)<span class="text-gray-400 text-xs ml-2">{{ \Carbon\Carbon::parse($dis->time_from)->format('g:ia') }}@if($dis->time_to)–{{ \Carbon\Carbon::parse($dis->time_to)->format('g:ia') }}@endif</span>@endif
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <span>{{ $dis->creator->full_name }}</span>
                    <form method="POST" action="{{ route('leave.disruption.delete',$dis) }}">@csrf @method('DELETE')
                        <button class="text-red-400 hover:text-red-600 text-xs">✕</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Edit / reschedule modal --}}
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div @click.stop class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl">
            <div class="flex justify-between items-start mb-4">
                <h3 class="font-semibold text-gray-900">Edit Leave Dates</h3>
                <button @click="editModal=false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <div x-show="editStatus==='approved'" class="mb-3 p-2.5 bg-amber-50 border border-amber-200 rounded text-xs text-amber-800">Moving approved leave will return it to pending for re-approval.</div>
            <form :action="'/leave/'+editId+'/reschedule'" method="POST">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">New Start</label><input type="date" name="start_date" x-model="editStart" class="w-full border rounded px-3 py-2 text-sm" required></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">New End</label><input type="date" name="end_date" x-model="editEnd" class="w-full border rounded px-3 py-2 text-sm" required></div>
                </div>
                <div class="flex gap-2 justify-between">
                    <form :action="'/leave/'+editId+'/cancel'" method="POST" @submit.prevent="cancelLeave()">
                        @csrf
                        <button type="button" @click="cancelLeave()" class="px-3 py-1.5 border border-red-400 text-red-600 rounded text-sm hover:bg-red-50">✕ Cancel Request</button>
                    </form>
                    <div class="flex gap-2">
                        <button type="button" @click="editModal=false" class="px-3 py-1.5 border rounded text-sm text-gray-600">Close</button>
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Disruption add modal --}}
    <div x-show="disruptionModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div @click.stop class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl">
            <div class="flex justify-between items-start mb-4">
                <h3 class="font-semibold text-gray-900">⚡ Log Disruption</h3>
                <button @click="disruptionModal=false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <p class="text-xs text-gray-500 mb-4">Log a disruption (power cut, broadband down etc.) visible to all staff on the calendar.</p>
            <form method="POST" action="{{ route('leave.disruption.add') }}">
                @csrf
                <div class="space-y-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Date</label><input type="date" name="date" x-model="disDate" class="w-full border rounded px-3 py-2 text-sm" required></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Description</label><input type="text" name="label" placeholder="e.g. Broadband down, Power cut" class="w-full border rounded px-3 py-2 text-sm" required></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">From (optional)</label><input type="time" name="time_from" class="w-full border rounded px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">To (optional)</label><input type="time" name="time_to" class="w-full border rounded px-3 py-2 text-sm"></div>
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-4">
                    <button type="button" @click="disruptionModal=false" class="px-3 py-1.5 border rounded text-sm text-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-orange-600 text-white rounded text-sm font-medium hover:bg-orange-700">Log Disruption</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Disruption edit modal --}}
    <div x-show="disEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div @click.stop class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl">
            <div class="flex justify-between items-start mb-4">
                <h3 class="font-semibold text-gray-900">⚡ Edit Disruption</h3>
                <button @click="disEditModal=false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form :action="'/leave/disruption/'+disEditId+'/delete'" method="POST" x-ref="disDeleteForm">@csrf @method('DELETE')</form>
            <p class="text-sm text-gray-600 mb-3" x-text="disEditLabel"></p>
            <div class="flex gap-2 justify-between mt-4">
                <button type="button" @click="$refs.disDeleteForm.submit()" class="px-3 py-1.5 border border-red-400 text-red-600 rounded text-sm hover:bg-red-50">✕ Remove</button>
                <button type="button" @click="disEditModal=false" class="px-3 py-1.5 border rounded text-sm text-gray-600">Close</button>
            </div>
        </div>
    </div>

</div>

<script>
function leaveApp() {
    return {
        activeFilters: ['approved','pending'],
        editModal: false, editId: null, editStart: '', editEnd: '', editStatus: '',
        disruptionModal: false, disDate: new Date().toISOString().slice(0,10),
        disEditModal: false, disEditId: null, disEditLabel: '',
        init() {},
        toggleFilter(f) {
            if (this.activeFilters.includes(f)) { this.activeFilters = this.activeFilters.filter(x=>x!==f); }
            else { this.activeFilters.push(f); }
        },
        openBook(ds) { window.location.href = '/leave/create?start='+ds+'&end='+ds; },
        openEdit(id, start, end, status) {
            this.editId = id; this.editStart = start; this.editEnd = end; this.editStatus = status; this.editModal = true;
        },
        openDisEdit(id, date, label, from, to) {
            this.disEditId = id;
            let t = label;
            if (from) { t += ' ('+this.fmtTime(from); if (to) t += '–'+this.fmtTime(to); t += ')'; }
            this.disEditLabel = t; this.disEditModal = true;
        },
        fmtTime(t) { if (!t) return ''; const [h,m] = t.split(':'); const hr = parseInt(h); return (hr%12||12)+(m!=='00'?':'+m:'')+(hr<12?'am':'pm'); },
        cancelLeave() {
            if (!confirm('Cancel this leave request?')) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = '/leave/'+this.editId+'/cancel';
            f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="POST">';
            document.body.appendChild(f); f.submit();
        }
    }
}
</script>

    {{-- Calendar Sync --}}
    <div class="bg-white border rounded-lg p-5 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1">📅 Sync to Outlook / Calendar</h3>
                <p class="text-xs text-gray-500 mb-3">Subscribe to your personal leave calendar in Outlook, Google Calendar, or Apple Calendar. Approved leave and bank holidays appear automatically.</p>
                @if(auth()->user()->calendar_token)
                <div class="flex items-center gap-2 flex-wrap">
                    <code class="text-xs bg-gray-100 border rounded px-3 py-2 text-gray-700 break-all flex-1 min-w-0 select-all" id="cal-url">{{ url('/calendar/' . auth()->user()->calendar_token . '.ics') }}</code>
                    <button onclick="navigator.clipboard.writeText(document.getElementById('cal-url').textContent).then(()=>this.textContent='Copied!')" class="px-3 py-2 bg-blue-600 text-white rounded text-xs font-medium whitespace-nowrap hover:bg-blue-700">Copy URL</button>
                </div>
                <div class="mt-3 flex gap-3 flex-wrap text-xs">
                    <a href="https://outlook.office.com/calendar/addcalendar" target="_blank" class="flex items-center gap-1 text-blue-600 hover:underline">Open Outlook → Add Calendar → From Internet →  paste URL</a>
                </div>
                <div class="mt-2">
                    <form method="POST" action="{{ route('calendar.regenerate') }}" onsubmit="return confirm('This will break your existing subscription link. Continue?')">
                        @csrf
                        <button type="submit" class="text-xs text-gray-400 hover:text-red-500 hover:underline">🔄 Reset link</button>
                    </form>
                </div>
                @endif
            </div>
            <div class="shrink-0 hidden md:block">
                <div class="w-14 h-14 rounded-xl bg-blue-50 flex items-center justify-center text-3xl border border-blue-100">📅</div>
            </div>
        </div>
    </div>

</div>
</x-app-layout>