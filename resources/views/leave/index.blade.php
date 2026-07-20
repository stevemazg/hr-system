<x-app-layout>



<div class="max-w-5xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Leave</h1>
            @if($pending > 0)
            <p class="text-sm text-orange-600 mt-1">{{ $pending }} pending request(s) awaiting action</p>
            @endif
        </div>
        <a href="{{ route("leave.create") }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">+ Book leave</a>
    </div>

    <div class="flex gap-2 mb-4">
        @foreach([now()->year - 1, now()->year, now()->year + 1] as $y)
        <a href="{{ request()->fullUrlWithQuery(["year" => $y]) }}" class="px-3 py-1 rounded text-sm {{ $year == $y ? "bg-blue-600 text-white" : "bg-white border text-gray-700 hover:bg-gray-50" }}">{{ $y }}</a>
        @endforeach
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    @if($user->isManager())<th class="px-4 py-3 text-left">Employee</th>@endif
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Dates</th>
                    <th class="px-4 py-3 text-left">Days</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($requests as $req)
                <tr class="hover:bg-gray-50">
                    @if($user->isManager())<td class="px-4 py-3 font-medium">{{ $req->user->full_name }}</td>@endif
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium" style="background:{{ $req->leaveType->colour }}22;color:{{ $req->leaveType->colour }}">{{ $req->leaveType->name }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $req->start_date->format("d M Y") }}@if(!$req->start_date->eq($req->end_date)) – {{ $req->end_date->format("d M Y") }}@endif @if($req->half_day)<span class="text-xs text-gray-400">({{ strtoupper($req->half_day_period) }})</span>@endif</td>
                    <td class="px-4 py-3">{{ $req->days_count }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-xs capitalize {{ ["pending"=>"bg-yellow-100 text-yellow-700","approved"=>"bg-green-100 text-green-700","declined"=>"bg-red-100 text-red-700","cancelled"=>"bg-gray-100 text-gray-500"][$req->status] ?? "" }}">{{ $req->status }}</span>
                    </td>
                    <td class="px-4 py-3 flex gap-2">
                        @if($req->isPending() && $user->isManager())
                        <form method="POST" action="{{ route("leave.approve",$req) }}">@csrf<button class="text-green-600 text-xs hover:underline">Approve</button></form>
                        <form method="POST" action="{{ route("leave.decline",$req) }}">@csrf<button class="text-red-500 text-xs hover:underline">Decline</button></form>
                        @elseif(in_array($req->status,["pending","approved"]))
                        <form method="POST" action="{{ route("leave.cancel",$req) }}">@csrf<button class="text-gray-400 text-xs hover:underline">Cancel</button></form>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">No leave requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">{{ $requests->links() }}</div>
    </div>
</div>

</x-app-layout>
