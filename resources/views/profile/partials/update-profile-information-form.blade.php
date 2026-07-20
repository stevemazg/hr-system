<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>
        <p class="mt-1 text-sm text-gray-600">Update your name, email, and profile photo.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5" enctype="multipart/form-data">
        @csrf
        @method('patch')

        {{-- Avatar --}}
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-full overflow-hidden bg-blue-600 flex items-center justify-center text-white text-xl font-bold shrink-0">
                @if($user->avatar_path)
                <img src="{{ Storage::url($user->avatar_path) }}" alt="Avatar" class="w-full h-full object-cover">
                @else
                {{ $user->initials }}
                @endif
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Profile Photo</label>
                <input type="file" name="avatar" accept="image/*" class="text-sm text-gray-600">
                <p class="text-xs text-gray-400 mt-1">JPG or PNG, max 2MB</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="w-full border rounded px-3 py-2 text-sm" required>
                <x-input-error class="mt-1" :messages="$errors->get('first_name')" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="w-full border rounded px-3 py-2 text-sm" required>
                <x-input-error class="mt-1" :messages="$errors->get('last_name')" />
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border rounded px-3 py-2 text-sm" required autocomplete="username">
            <x-input-error class="mt-1" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Save</button>
            @if (session('status') === 'profile-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600">Saved.</p>
            @endif
        </div>
    </form>
</section>
