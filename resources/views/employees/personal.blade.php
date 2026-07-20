<x-app-layout>
<div class="max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route("employees.show",$employee) }}#personal" class="text-gray-400 hover:text-gray-600 text-sm">← Back</a>
        <h1 class="text-2xl font-bold text-gray-900">Personal Details — {{ $employee->full_name }}</h1>
    </div>
    <form method="POST" action="{{ route("employees.personal.update",$employee) }}">
        @csrf @method("PUT")
        <div class="bg-white border rounded-lg p-6 space-y-6">
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Basic Info</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label><input type="date" name="date_of_birth" value="{{ old("date_of_birth",$details->date_of_birth?->format("Y-m-d")) }}" class="w-full border rounded px-3 py-2 text-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">NI Number</label><input type="text" name="national_insurance" value="{{ old("national_insurance",$details->national_insurance) }}" placeholder="AB123456C" class="w-full border rounded px-3 py-2 text-sm"></div>
                </div>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Address</h3>
                <div class="space-y-3">
                    <input type="text" name="address_line1" value="{{ old("address_line1",$details->address_line1) }}" placeholder="Address line 1" class="w-full border rounded px-3 py-2 text-sm">
                    <input type="text" name="address_line2" value="{{ old("address_line2",$details->address_line2) }}" placeholder="Address line 2" class="w-full border rounded px-3 py-2 text-sm">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="city" value="{{ old("city",$details->city) }}" placeholder="City" class="w-full border rounded px-3 py-2 text-sm">
                        <input type="text" name="postcode" value="{{ old("postcode",$details->postcode) }}" placeholder="Postcode" class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Right to Work</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="right_to_work" class="w-full border rounded px-3 py-2 text-sm">
                            <option value="citizen" {{ ($details->right_to_work ?? "") === "citizen" ? "selected" : "" }}>UK Citizen / ILR</option>
                            <option value="visa" {{ ($details->right_to_work ?? "") === "visa" ? "selected" : "" }}>Has Work Visa</option>
                            <option value="applying" {{ ($details->right_to_work ?? "") === "applying" ? "selected" : "" }}>Applying</option>
                            <option value="not_checked" {{ ($details->right_to_work ?? "not_checked") === "not_checked" ? "selected" : "" }}>Not Checked</option>
                        </select>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Visa Expiry</label><input type="date" name="visa_expiry" value="{{ old("visa_expiry",$details->visa_expiry?->format("Y-m-d")) }}" class="w-full border rounded px-3 py-2 text-sm"></div>
                </div>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Emergency Contact</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Name</label><input type="text" name="emergency_contact_name" value="{{ old("emergency_contact_name",$details->emergency_contact_name) }}" class="w-full border rounded px-3 py-2 text-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="text" name="emergency_contact_phone" value="{{ old("emergency_contact_phone",$details->emergency_contact_phone) }}" class="w-full border rounded px-3 py-2 text-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Relationship</label><input type="text" name="emergency_contact_relationship" value="{{ old("emergency_contact_relationship",$details->emergency_contact_relationship) }}" placeholder="e.g. Partner, Parent" class="w-full border rounded px-3 py-2 text-sm"></div>
                </div>
            </div>
        </div>
        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">Save</button>
            <a href="{{ route("employees.show",$employee) }}#personal" class="px-5 py-2 border rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>