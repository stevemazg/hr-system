<x-app-layout>
<div class="max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route("employees.show",$employee) }}" class="text-gray-400 hover:text-gray-600 text-sm">← Back</a>
        <h1 class="text-2xl font-bold text-gray-900">Edit — {{ $employee->full_name }}</h1>
    </div>
    <form method="POST" action="{{ route("employees.update",$employee) }}">
        @csrf @method("PUT")
        <div class="bg-white border rounded-lg p-6 space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">First Name</label><input type="text" name="first_name" value="{{ old("first_name",$employee->first_name) }}" class="w-full border rounded px-3 py-2 text-sm" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label><input type="text" name="last_name" value="{{ old("last_name",$employee->last_name) }}" class="w-full border rounded px-3 py-2 text-sm" required></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" name="email" value="{{ old("email",$employee->email) }}" class="w-full border rounded px-3 py-2 text-sm" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="text" name="phone" value="{{ old("phone",$employee->phone) }}" class="w-full border rounded px-3 py-2 text-sm"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Job Title</label><input type="text" name="job_title" value="{{ old("job_title",$employee->job_title) }}" class="w-full border rounded px-3 py-2 text-sm"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label>
                    <select name="employment_type" class="w-full border rounded px-3 py-2 text-sm">
                        @foreach(["permanent"=>"Permanent","part_time"=>"Part Time","freelancer"=>"Freelancer","contractor"=>"Contractor","zero_hours"=>"Zero Hours"] as $val => $label)
                        <option value="{{ $val }}" {{ old("employment_type",$employee->employment_type) === $val ? "selected" : "" }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label><input type="date" name="start_date" value="{{ old("start_date",$employee->start_date?->format("Y-m-d")) }}" class="w-full border rounded px-3 py-2 text-sm"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Hours per Week</label><input type="number" name="working_hours_per_week" value="{{ old("working_hours_per_week",$employee->working_hours_per_week) }}" step="0.5" class="w-full border rounded px-3 py-2 text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Line Manager</label>
                    <select name="line_manager_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">— None —</option>
                        @foreach($managers as $m)
                        <option value="{{ $m->id }}" {{ old("line_manager_id",$employee->line_manager_id) == $m->id ? "selected" : "" }}>{{ $m->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if($user->isGlobalAdmin())
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Organisation</label>
                    <select name="org_id" class="w-full border rounded px-3 py-2 text-sm">
                        @foreach($orgs as $org)
                        <option value="{{ $org->id }}" {{ old("org_id",$employee->org_id) == $org->id ? "selected" : "" }}>{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="user" {{ $employee->role === "user" ? "selected" : "" }}>User</option>
                        <option value="manager" {{ $employee->role === "manager" ? "selected" : "" }}>Manager</option>
                        <option value="global_admin" {{ $employee->role === "global_admin" ? "selected" : "" }}>Global Admin</option>
                    </select>
                </div>
            </div>
            @endif
        </div>
        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">Save Changes</button>
            <a href="{{ route("employees.show",$employee) }}" class="px-5 py-2 border rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>