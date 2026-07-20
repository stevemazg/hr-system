<x-app-layout>
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Organisations</h1>
    </div>
    <div class="space-y-3">
        @foreach($organisations as $org)
        <div class="bg-white border rounded-lg p-5 flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-900">{{ $org->name }}</p>
                <p class="text-sm text-gray-500">{{ $org->users_count }} {{ $org->users_count === 1 ? 'employee' : 'employees' }} &bull; {{ $org->default_holiday_days }} days annual leave &bull; Leave year starts {{ \Carbon\Carbon::create()->month($org->leave_year_month_start)->day($org->leave_year_day_start ?? 1)->format('j M') }}</p>
            </div>
            <a href="{{ route('organisations.edit', $org) }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Settings</a>
        </div>
        @endforeach
    </div>
</div>
</x-app-layout>
