<x-app-layout>
<div class="max-w-4xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('organisations.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Organisations</a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $organisation->name }}</h1>
    </div>

    {{-- Org Settings --}}
    <div class="bg-white border rounded-lg p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Organisation Settings</h2>
        <form method="POST" action="{{ route('organisations.update', $organisation) }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Organisation Name</label>
                    <input type="text" name="name" value="{{ old('name', $organisation->name) }}" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Default Annual Leave (days)</label>
                    <input type="number" name="default_holiday_days" value="{{ old('default_holiday_days', $organisation->default_holiday_days) }}" class="w-full border rounded px-3 py-2 text-sm" min="0" max="365" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Max Carry Forward (days)</label>
                    <input type="number" name="max_carry_forward_days" value="{{ old('max_carry_forward_days', $organisation->max_carry_forward_days) }}" class="w-full border rounded px-3 py-2 text-sm" min="0" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Leave Year Start Month</label>
                    <select name="leave_year_month_start" class="w-full border rounded px-3 py-2 text-sm">
                        @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" {{ $organisation->leave_year_month_start == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Leave Year Start Day</label>
                    <input type="number" name="leave_year_day_start" value="{{ old('leave_year_day_start', $organisation->leave_year_day_start ?? 1) }}" class="w-full border rounded px-3 py-2 text-sm" min="1" max="28" required>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <input type="hidden" name="carry_forward_enabled" value="0">
                    <input type="checkbox" name="carry_forward_enabled" id="cfe" value="1" {{ $organisation->carry_forward_enabled ? 'checked' : '' }} class="rounded">
                    <label for="cfe" class="text-sm text-gray-700">Enable carry forward</label>
                </div>
            </div>
            <div class="mt-5">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Save Settings</button>
            </div>
        </form>
    </div>

    {{-- Leave Types --}}
    <div class="bg-white border rounded-lg p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Leave Types</h2>
        <div class="space-y-3 mb-6">
            @foreach($leaveTypes as $lt)
            <form method="POST" action="{{ route('organisations.leave-types.update', [$organisation, $lt]) }}" class="border rounded-lg p-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                        <input type="text" name="name" value="{{ $lt->name }}" class="w-full border rounded px-2 py-1.5 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Colour</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="colour" value="{{ $lt->colour }}" class="h-8 w-12 border rounded cursor-pointer">
                            <span class="text-xs text-gray-400">{{ $lt->colour }}</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer"><input type="checkbox" name="has_allowance" value="1" {{ $lt->has_allowance ? 'checked' : '' }} class="rounded"> Fixed allowance</label>
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer"><input type="checkbox" name="paid" value="1" {{ $lt->paid ? 'checked' : '' }} class="rounded"> Paid</label>
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer"><input type="checkbox" name="carries_forward" value="1" {{ $lt->carries_forward ? 'checked' : '' }} class="rounded"> Carries forward</label>
                    </div>
                    <div class="space-y-1">
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer"><input type="checkbox" name="requires_approval" value="1" {{ $lt->requires_approval ? 'checked' : '' }} class="rounded"> Requires approval</label>
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer"><input type="checkbox" name="active" value="1" {{ $lt->active ? 'checked' : '' }} class="rounded"> Active</label>
                    </div>
                    <div class="md:col-span-4 flex justify-end">
                        <button type="submit" class="px-3 py-1 bg-gray-800 text-white text-xs font-medium rounded hover:bg-gray-700">Update</button>
                    </div>
                </div>
            </form>
            @endforeach
        </div>

        {{-- Add new leave type --}}
        <details class="border rounded-lg">
            <summary class="px-4 py-3 text-sm font-medium text-gray-700 cursor-pointer hover:bg-gray-50">+ Add Leave Type</summary>
            <form method="POST" action="{{ route('organisations.leave-types.store', $organisation) }}" class="p-4 border-t">
                @csrf
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                        <input type="text" name="name" placeholder="e.g. Study Leave" class="w-full border rounded px-2 py-1.5 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Colour</label>
                        <input type="color" name="colour" value="#6366f1" class="h-8 w-12 border rounded cursor-pointer">
                    </div>
                    <div class="space-y-1">
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer"><input type="checkbox" name="has_allowance" value="1" checked class="rounded"> Fixed allowance</label>
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer"><input type="checkbox" name="paid" value="1" checked class="rounded"> Paid</label>
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer"><input type="checkbox" name="carries_forward" value="1" class="rounded"> Carries forward</label>
                    </div>
                    <div class="space-y-1">
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer"><input type="checkbox" name="requires_approval" value="1" checked class="rounded"> Requires approval</label>
                    </div>
                    <div class="md:col-span-4">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">Add Leave Type</button>
                    </div>
                </div>
            </form>
        </details>
    </div>
</div>
</x-app-layout>
