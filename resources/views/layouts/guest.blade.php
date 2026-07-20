<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config("app.name", "HR System") }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-100 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">HR System</h1>
                <p class="text-gray-500 text-sm mt-1">People. Managed simply.</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
