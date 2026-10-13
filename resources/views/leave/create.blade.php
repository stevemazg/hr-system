<x-app-layout>
<div class="w-full">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">
        Book Leave @if($targetUser->id !== $user->id)for {{ $targetUser->full_name }}@endif
    </h1>

    @if($user->isManager() && $staffList->count())
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5">
        <label class="block text-sm font-semibold text-blue-800 mb-2">Booking for:</label>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('leave.create') }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors
                      {{ $targetUser->id === $user->id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-700 border-blue-300 hover:bg-blue-100' }}">
                Myself
            </a>
            @foreach($staffList as $emp)
            @if($emp->id !== $user->id)
            <a href="{{ route('leave.create') }}?employee_id={{ $emp->id }}&start={{ request('start','') }}&end={{ request('end','') }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors
                      {{ $targetUser->id === $emp->id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-700 border-blue-300 hover:bg-blue-100' }}">
                {{ $emp->first_name }}
            </a>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{
    halfDay: false,
    startDate: '',
    endDate: '',
    get dayCount() {
        if (this.halfDay) return 0.5;
        if (!this.startDate || !this.endDate) return 0;
        let start = new Date(this.startDate);
        let end = new Date(this.endDate);
        if (end < start) return 0;
        let days = 0;
        let cur = new Date(start);
        while (cur <= end) {
            let dow = cur.getDay();
            if (dow !== 0 && dow !== 6) days++;
            cur.setDate(cur.getDate() + 1);
        }
        return days;
    },
    get dayLabel() {
        let n = this.dayCount;
        if (n === 0) return '';
        return n === 0.5 ? '0.5 days' : (n === 1 ? '1 day' : n + ' days');
    }
}">
        {{-- Form --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('leave.store') }}">
                @csrf
                <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                <div class="bg-white border rounded-xl p-6 shadow-sm space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                        <select name="leave_type_id" class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#1A1A3D] focus:border-[#1A1A3D]" required>
                            @foreach($leaveTypes as $lt)
                            @if(!str_contains(strtolower($lt->name), 'work from home') && !str_contains(strtolower($lt->name), 'wfh'))
                            <option value="{{ $lt->id }}" {{ old('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" value="{{ old('start_date', request('start', date('Y-m-d'))) }}" x-model="startDate" @change="endDate = endDate || startDate" class="w-full border rounded-lg px-3 py-2.5 text-sm" required>
                        </div>
                        <div x-show="!halfDay">
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" value="{{ old('end_date', request('end', date('Y-m-d'))) }}" x-model="endDate" class="w-full border rounded-lg px-3 py-2.5 text-sm">
                        </div>
                    </div>

                    <div x-show="dayLabel" x-cloak class="flex items-center gap-2 py-2 px-3 bg-indigo-50 border border-indigo-200 rounded-lg text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-indigo-700 font-semibold"><span x-text="dayLabel"></span> will be deducted from your allowance</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="half_day" name="half_day" value="1" x-model="halfDay" class="rounded border-gray-300">
                        <label for="half_day" class="text-sm font-medium text-gray-700">Half day</label>
                        <select name="half_day_period" x-show="halfDay" class="border rounded-lg px-2 py-1.5 text-sm">
                            <option value="am">Morning (AM)</option>
                            <option value="pm">Afternoon (PM)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                        <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2.5 text-sm" placeholder="Any additional details...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                @if(session('toil_warning'))
                <div class="mt-4 p-4 bg-amber-50 border border-amber-300 rounded-xl">
                    <p class="font-semibold text-amber-900 mb-1">⚠️ Annual leave allowance exceeded</p>
                    <p class="text-sm text-amber-800 mb-1">This booking is <strong>{{ number_format(session('toil_total'),1) }} days</strong> but you only have <strong>{{ number_format(session('toil_al_days'),1) }} days</strong> AL remaining.</p>
                    <p class="text-sm text-amber-800 mb-3">The extra <strong>{{ number_format(session('toil_overflow'),1) }} day(s)</strong> will be recorded as <strong>Unpaid</strong> — Unpaid Leave. This is time off taken beyond your annual leave allowance and is <strong>unpaid</strong>.</p>
                    <form method="POST" action="{{ route('leave.store') }}" class="inline">
                        @csrf
                        @foreach(old() as $k => $v)
                            @if(is_array($v))@foreach($v as $vi)<input type="hidden" name="{{ $k }}[]" value="{{ $vi }}">@endforeach
                            @else<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                        @endforeach
                        <input type="hidden" name="confirm_toil" value="1">
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-semibold hover:bg-amber-700 mr-2">✓ Accept — {{ number_format(session('toil_overflow'),1) }}d as Unpaid</button>
                    </form>
                    <a href="{{ route('leave.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</a>
                </div>
                @else
                <div class="mt-4 flex gap-3">
                    <button type="submit" class="px-5 py-2.5 bg-[#1A1A3D] text-white rounded-lg text-sm font-medium hover:bg-[#2d2d6b]">Submit Request</button>
                    <a href="{{ route('leave.index') }}" class="px-5 py-2.5 border text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</a>
                </div>
                @endif
            </form>
        </div>

        {{-- Balance summary sidebar --}}
        <div>
            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">{{ $targetUser->first_name }}'s Balances ({{ now()->year }})</p>
                <div class="space-y-3">
                    @foreach($balances as $bal)
                    @php
                        $ltNameLower = strtolower($bal->leaveType->name);
                        $isWfh       = str_contains($ltNameLower, 'work from home') || str_contains($ltNameLower, 'wfh');
                        $isParental  = str_contains($ltNameLower, 'parental');
                        $liveUsed    = (float)$bal->used_days; // already live-calculated by controller
                        $skipRow     = $isWfh || ($isParental && $liveUsed == 0 && $bal->total_entitlement == 0);
                    @endphp
                    @if(!$skipRow)
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-700 font-medium">{{ $bal->leaveType->name }}</span>
                        @if($bal->leaveType->has_allowance)
                        <div class="text-right">
                            <span class="font-bold {{ $bal->available <= 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ number_format($bal->available,1) }}</span>
                            <span class="text-gray-400 text-xs"> remaining</span>
                        </div>
                        @else
                        <span class="text-gray-400 text-xs">{{ number_format($liveUsed,1) }} used</span>
                        @endif
                    </div>
                    @if($bal->leaveType->has_allowance)
                    @php $pct = $bal->total_entitlement > 0 ? min(100, (($bal->total_entitlement - $bal->available) / $bal->total_entitlement) * 100) : 0; @endphp
                    <div class="h-1 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $pct>=90?'bg-red-500':($pct>=70?'bg-amber-400':'bg-emerald-500') }}" style="width:{{ $pct }}%"></div>
                    </div>
                    @endif
                    @endif
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-4 border-t pt-3">Bank holidays within your leave period are not counted against your allowance.</p>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
