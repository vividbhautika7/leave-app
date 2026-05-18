{{-- resources/views/profile/employee.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    {{-- Navbar --}}
    <nav class="bg-indigo-600 text-white px-6 py-4 flex justify-between items-center shadow">
        <h1 class="text-xl font-bold">Leave App</h1>
        <div class="flex items-center gap-6">
            <a href="{{ route('employee.dashboard') }}" class="hover:underline">Dashboard</a>
            <a href="{{ route('employee.leave.apply') }}" class="hover:underline">Apply Leave</a>
            <a href="{{ route('employee.leave.my') }}" class="hover:underline">My Leaves</a>
            <a href="{{ route('profile.show') }}" class="hover:underline font-semibold">Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="bg-white text-indigo-600 px-4 py-1 rounded-lg font-semibold hover:bg-gray-100">
                    Logout
                </button>
            </form>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-6 py-10">

        <h2 class="text-2xl font-bold text-gray-700 mb-8">My Profile</h2>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        {{-- Profile Info Card --}}
        <div class="bg-white rounded-2xl shadow p-8 mb-6">

            {{-- Avatar + Basic Info --}}
            <div class="flex items-center gap-5 mb-8 pb-6 border-b border-gray-100">
                <div class="w-16 h-16 rounded-full bg-indigo-600 flex items-center justify-center text-white text-2xl font-bold">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-700">{{ $user->name }}</h3>
                    <p class="text-gray-400 text-sm">{{ $user->email }}</p>
                    <span class="bg-green-100 text-green-600 text-xs px-3 py-1 rounded-full font-medium mt-1 inline-block">
                        Employee
                    </span>
                </div>
            </div>

            {{-- Leave Stats --}}
            <div class="grid grid-cols-2 gap-4 mb-8 pb-6 border-b border-gray-100">
                <div class="bg-indigo-50 rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-indigo-600">
                        {{ $user->leaveApplications()->count() }}
                    </p>
                    <p class="text-gray-500 text-sm mt-1">Total Leaves</p>
                </div>
                <div class="bg-green-50 rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-green-600">
                        {{ $user->leaveApplications()
                            ->whereYear('start_date', now()->year)
                            ->count() }}
                    </p>
                    <p class="text-gray-500 text-sm mt-1">This Year</p>
                </div>
            </div>

            {{-- Edit Form --}}
            <h3 class="text-base font-semibold text-gray-700 mb-4">
                Edit Information
            </h3>

            @if($errors->any())
                <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf

                {{-- Name --}}
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm font-medium mb-1">
                        Full Name
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    >
                </div>

                {{-- Email --}}
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm font-medium mb-1">
                        Email Address
                    </label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    >
                </div>

                {{-- Role — Read Only --}}
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm font-medium mb-1">
                        Role
                    </label>
                    <input
                        type="text"
                        value="Employee"
                        disabled
                        class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm bg-gray-50 text-gray-400 cursor-not-allowed"
                    >
                    <p class="text-xs text-gray-400 mt-1">Role cannot be changed.</p>
                </div>

                {{-- Member Since --}}
                <div class="mb-6">
                    <label class="block text-gray-600 text-sm font-medium mb-1">
                        Member Since
                    </label>
                    <input
                        type="text"
                        value="{{ $user->created_at->format('d M Y') }}"
                        disabled
                        class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm bg-gray-50 text-gray-400 cursor-not-allowed"
                    >
                </div>

                <button
                    type="submit"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition">
                    Save Changes
                </button>
            </form>
        </div>

        {{-- Change Password Card --}}
        <div class="bg-white rounded-2xl shadow p-8">
            <h3 class="text-base font-semibold text-gray-700 mb-6">Change Password</h3>

            @if(session('password_error'))
                <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                    {{ session('password_error') }}
                </div>
            @endif

            @if(session('password_success'))
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
                    {{ session('password_success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('profile.password') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-gray-600 text-sm font-medium mb-1">
                        Current Password
                    </label>
                    <input
                        type="password"
                        name="current_password"
                        required
                        placeholder="••••••••"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    >
                </div>

                <div class="mb-4">
                    <label class="block text-gray-600 text-sm font-medium mb-1">
                        New Password
                    </label>
                    <input
                        type="password"
                        name="new_password"
                        required
                        minlength="8"
                        placeholder="••••••••"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    >
                </div>

                <div class="mb-6">
                    <label class="block text-gray-600 text-sm font-medium mb-1">
                        Confirm New Password
                    </label>
                    <input
                        type="password"
                        name="new_password_confirmation"
                        required
                        placeholder="••••••••"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    >
                </div>

                <button
                    type="submit"
                    class="bg-gray-700 text-white px-6 py-2 rounded-lg font-semibold text-sm hover:bg-gray-800 transition">
                    Update Password
                </button>
            </form>
        </div>

    </div>
</body>
</html>