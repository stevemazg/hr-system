<x-app-layout>
    <div class="max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            @foreach($myBalances as $bal)
            <div class="bg-white rounded-lg border p-4">
                <p class="text-xs font-medium text-gray-500 uppercase">{{ $bal->leaveType->name }}</p>
                @if($bal->leaveType->has_allowance)
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($bal->available, 1) }}</p>
                <p class="text-xs text-gray-400 mt-1">of {{ number_format($bal->total_entitlement, 1) }} days remaining</p>
                @else
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($bal->used_days, 1) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $bal->used_days == 1 ? 'day' : 'days' }} taken</p>
                @endif
            </div>
            @endforeach
            @if($myBalances->isEmpty())
            <div class="col-span-3 bg-white rounded-lg border p-6 text-center text-gray-500 text-sm">No leave allowances configured yet.</div>
            @endif
        </div>

        @if($user->isManager() && $pendingRequests->count())
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Pending Requests ({{ $pendingRequests->count() }})</h2>
            <div class="bg-white border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase"><tr>
                        <th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Dates</th><th class="px-4 py-3 text-left">Days</th><th class="px-4 py-3 text-left">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @foreach($pendingRequests as $req)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $req->user->full_name }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium" style="background:{{ $req->leaveType->colour }}22;color:{{ $req->leaveType->colour }}">{{ $req->leaveType->name }}</span></td>
                            <td class="px-4 py-3 text-gray-600">{{ $req->start_date->format("d M") }}@if(!$req->start_date->eq($req->end_date)) – {{ $req->end_date->format("d M") }}@endif</td>
                            <td class="px-4 py-3">{{ $req->days_count }}</td>
                            <td class="px-4 py-3 flex gap-3">
                                <form method="POST" action="{{ route("leave.approve",$req) }}">@csrf<button class="text-green-600 text-xs hover:underline font-medium">Approve</button></form>
                                <form method="POST" action="{{ route("leave.decline",$req) }}">@csrf<button class="text-red-500 text-xs hover:underline">Decline</button></form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-3">My Upcoming Leave</h2>
                <div class="bg-white border rounded-lg divide-y">
                    @forelse($myUpcoming as $req)
                    <div class="px-4 py-3 flex justify-between items-center text-sm">
                        <div><span class="font-medium">{{ $req->leaveType->name }}</span><span class="text-gray-500 ml-2">{{ $req->start_date->format("d M") }}</span></div>
                        <span class="text-gray-400">{{ $req->days_count }}d</span>
                    </div>
                    @empty<div class="px-4 py-6 text-center text-sm text-gray-400">No upcoming leave.</div>
                    @endforelse
                </div>
                <a href="{{ route("leave.create") }}" class="mt-3 inline-block text-sm text-blue-600 hover:underline">+ Book leave</a>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Out Today</h2>
                <div class="bg-white border rounded-lg divide-y">
                    @forelse($todayOut as $req)
                    <div class="px-4 py-3 flex items-center gap-3 text-sm">
                        <div class="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-xs font-bold text-white">{{ $req->user->initials }}</div>
                        <div><span class="font-medium">{{ $req->user->full_name }}</span><span class="text-gray-400 text-xs ml-2">{{ $req->leaveType->name }}</span></div>
                    </div>
                    @empty<div class="px-4 py-6 text-center text-sm text-gray-400">Everyone in today.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
