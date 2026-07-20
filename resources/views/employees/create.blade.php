<x-app-layout>
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Add Employee</h1>
    <form method="POST" action="{{ route("employees.store") }}">
        @csrf
        <div class="bg-white border rounded-lg p-6 space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">First Name</label><input type="text" name="first_name" value="{{ old("first_name") }}" class="w-full border rounded px-3 py-2 text-sm" required autofocus></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label><input type="text" name="last_name" value="{{ old("last_name") }}" class="w-full border rounded px-3 py-2 text-sm" required></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" name="email" value="{{ old("email") }}" class="w-full border rounded px-3 py-2 text-sm" required></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Job Title</label><input type="text" name="job_title" value="{{ old("job_title") }}" class="w-full border rounded px-3 py-2 text-sm"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Organisation</label>
                    <select name="org_id" class="w-full border rounded px-3 py-2 text-sm" required>
                        <option value="">— Select org —</option>
                        @foreach($orgs as $org)
                        <option value="{{ $org->id }}" {{ old("org_id") == $org->id ? "selected" : "" }}>{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label>
                    <select name="employment_type" class="w-full border rounded px-3 py-2 text-sm" required>
                        <option value="permanent">Permanent</option><option value="part_time">Part Time</option><option value="freelancer">Freelancer</option><option value="contractor">Contractor</option><option value="zero_hours">Zero Hours</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label><input type="date" name="start_date" class="w-full border rounded px-3 py-2 text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" class="w-full border rounded px-3 py-2 text-sm" required>
                        <option value="user">User</option><option value="manager">Manager</option><option value="global_admin">Global Admin</option>
                    </select>
                </div>
            </div>
            <p class="text-xs text-gray-400">Default password: <code class="bg-gray-100 px-1 rounded">Welcome2026!</code> — change after first login.</p>
        </div>
        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">Create Employee</button>
            <a href="{{ route("employees.index") }}" class="px-5 py-2 border rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>