<x-app-layout>



<div class="max-w-5xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">People</h1>
        @if($user->isManager())
        <a href="{{ route("employees.create") }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">+ Add employee</a>
        @endif
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Job Title</th>
                    <th class="px-4 py-3 text-left">Organisation</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Role</th>
                    <th class="px-4 py-3 text-left"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($employees as $emp)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-xs font-bold text-white">{{ $emp->initials }}</div>
                            <span class="font-medium text-gray-900">{{ $emp->full_name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $emp->job_title ?? "—" }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $emp->organisation->name ?? "—" }}</td>
                    <td class="px-4 py-3 text-gray-600 capitalize">{{ str_replace("_"," ", $emp->employment_type) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-xs {{ $emp->role === "global_admin" ? "bg-red-100 text-red-700" : ($emp->role === "manager" ? "bg-yellow-100 text-yellow-700" : "bg-gray-100 text-gray-700") }} capitalize">
                            {{ str_replace("_"," ", $emp->role) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route("employees.show", $emp) }}" class="text-blue-600 hover:underline text-xs">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-4 py-3 border-t text-sm text-gray-500">{{ $employees->links() }}</div>
    </div>
</div>

</x-app-layout>
