<x-app-layout>



<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Book Leave @if($targetUser->id !== $user->id)for {{ $targetUser->full_name }}@endif</h1>

    <form method="POST" action="{{ route("leave.store") }}" x-data="{ halfDay: false }">
        @csrf
        <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
        <div class="bg-white border rounded-lg p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                <select name="leave_type_id" class="w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" required>
                    @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}" {{ old("leave_type_id") == $lt->id ? "selected" : "" }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ old("start_date", date("Y-m-d")) }}" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div x-show="!halfDay">
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ old("end_date", date("Y-m-d")) }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" id="half_day" name="half_day" value="1" x-model="halfDay" class="rounded">
                <label for="half_day" class="text-sm font-medium text-gray-700">Half day</label>
                <select name="half_day_period" x-show="halfDay" class="border rounded px-2 py-1 text-sm">
                    <option value="am">Morning (AM)</option>
                    <option value="pm">Afternoon (PM)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm" placeholder="Any additional details...">{{ old("notes") }}</textarea>
            </div>

            <div class="bg-gray-50 rounded p-3">
                <p class="text-xs text-gray-500 font-medium mb-2">Current balances ({{ now()->year }})</p>
                @foreach($balances as $bal)
                <div class="flex justify-between text-xs text-gray-700 mb-1">
                    <span>{{ $bal->leaveType->name }}</span>
                    <span class="font-medium">{{ number_format($bal->available, 1) }} days remaining</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">Submit Request</button>
            <a href="{{ route("leave.index") }}" class="px-5 py-2 border text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>

</x-app-layout>
