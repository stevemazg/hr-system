<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header ?? "HR System" }} — HR System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
<div class="min-h-screen flex">
    <aside class="hidden lg:flex lg:flex-col w-64 bg-gray-900 text-white shrink-0">
        <div class="flex items-center h-16 px-6 border-b border-gray-700">
            <span class="text-xl font-bold text-white">HR System</span>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <a href="{{ route("dashboard") }}" class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs("dashboard") ? "bg-blue-600 text-white" : "text-gray-300 hover:bg-gray-700" }}">Dashboard</a>
            <a href="{{ route("employees.index") }}" class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs("employees.*") ? "bg-blue-600 text-white" : "text-gray-300 hover:bg-gray-700" }}">People</a>
            <a href="{{ route("leave.index") }}" class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs("leave.*") ? "bg-blue-600 text-white" : "text-gray-300 hover:bg-gray-700" }}">Leave</a>
            <a href="{{ route("profile.edit") }}" class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs("profile.*") ? "bg-blue-600 text-white" : "text-gray-300 hover:bg-gray-700" }}">Profile</a>
            @if(auth()->user()->isGlobalAdmin())
            <a href="{{ route("organisations.index") }}" class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs("organisations.*") ? "bg-blue-600 text-white" : "text-gray-300 hover:bg-gray-700" }}">Organisations</a>
            @endif
        </nav>
        <div class="px-4 py-4 border-t border-gray-700">
            @auth
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-xs font-bold">{{ auth()->user()->initials }}</div>
                <div><p class="text-sm font-medium text-white truncate">{{ auth()->user()->full_name }}</p><p class="text-xs text-gray-400 capitalize">{{ str_replace("_"," ", auth()->user()->role) }}</p></div>
            </div>
            <form method="POST" action="{{ route("logout") }}">@csrf<button type="submit" class="text-xs text-gray-400 hover:text-white">Sign out</button></form>
            @endauth
        </div>
    </aside>
    <div class="flex-1 flex flex-col min-w-0 overflow-auto">
        <main class="flex-1 p-6">
            @if(session("success"))<div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ session("success") }}</div>@endif
            @if(session("warning"))<div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded text-sm">{{ session("warning") }}</div>@endif
            @if($errors->any())<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
