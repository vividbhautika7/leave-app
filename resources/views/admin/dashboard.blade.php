{{-- resources/views/admin/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-gray-100 min-h-screen">

        {{-- Navbar --}}
        <nav class="bg-indigo-600 text-white px-6 py-4 flex justify-between items-center shadow">
            <h1 class="text-xl font-bold">Leave App — Admin</h1>
            <div class="flex items-center gap-6">
                <a href="{{ route('admin.dashboard') }}" class="hover:underline">Dashboard</a>
                <a href="{{ route('admin.leaves.index') }}" class="hover:underline">All Leaves</a>
                <a href="{{ route('profile.show') }}" class="hover:underline">Profile</a>

                {{-- Google Connect Button --}}
                @if(!Auth::user()->google_token)
                    <a href="{{ route('admin.google.auth') }}"
                    class="bg-white text-indigo-600 px-4 py-1 rounded-lg font-semibold hover:bg-gray-100 text-sm">
                        Connect Google Calendar
                    </a>
                @else
                    <div class="flex items-center gap-2">
                        <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-medium">
                            ✓ Google Connected
                        </span>
                        <form method="POST"
                            action="{{ route('admin.google.disconnect') }}"
                            onsubmit="return confirm('This will remove ALL leave events from Google Calendar. Are you sure?')">
                            @csrf
                            <button type="submit"
                                    class="bg-red-500 text-white px-3 py-1 rounded-lg text-xs font-semibold hover:bg-red-600 transition">
                                Disconnect
                            </button>
                        </form>
                    </div>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bg-white text-indigo-600 px-4 py-1 rounded-lg font-semibold hover:bg-gray-100">
                        Logout
                    </button>
                </form>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-6 py-8">

            {{-- Messages --}}
            @if(session('success'))
            <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
            @endif

            {{-- Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4">
                    <div class="bg-indigo-100 text-indigo-600 rounded-full p-4 text-2xl">👥</div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Employees</p>
                        <p class="text-3xl font-bold text-gray-700">{{ $totalEmployees }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4">
                    <div class="bg-yellow-100 text-yellow-600 rounded-full p-4 text-2xl">📋</div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Leaves</p>
                        <p class="text-3xl font-bold text-gray-700">{{ $totalLeaves }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4">
                    <div class="bg-red-100 text-red-600 rounded-full p-4 text-2xl">🏠</div>
                    <div>
                        <p class="text-gray-500 text-sm">On Leave Today</p>
                        <p class="text-3xl font-bold text-gray-700">{{ $todayLeaves->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                {{-- Today's Leaves --}}
                <div class="bg-white rounded-2xl shadow p-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4">On Leave Today</h3>
                    @if($todayLeaves->isEmpty())
                    <p class="text-gray-400">No employees on leave today.</p>
                    @else
                    <ul class="space-y-3">
                        @foreach($todayLeaves as $leave)
                        <li class="flex items-center gap-3 border-b pb-3">
                            <div class="bg-indigo-100 text-indigo-600 rounded-full w-9 h-9 flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($leave->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-700">{{ $leave->user->name }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $leave->start_date->format('d M') }} —
                                    {{ $leave->end_date->format('d M Y') }}
                                </p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                {{-- Upcoming Leaves --}}
                <div class="bg-white rounded-2xl shadow p-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4">Upcoming Leaves</h3>
                    @if($upcomingLeaves->isEmpty())
                    <p class="text-gray-400">No upcoming leaves.</p>
                    @else
                    <ul class="space-y-3">
                        @foreach($upcomingLeaves as $leave)
                        <li class="flex items-center gap-3 border-b pb-3">
                            <div class="bg-green-100 text-green-600 rounded-full w-9 h-9 flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($leave->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-700">{{ $leave->user->name }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $leave->start_date->format('d M') }} —
                                    {{ $leave->end_date->format('d M Y') }}
                                    ({{ $leave->total_days }} days)
                                </p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>

            {{-- Google Calendar Embed --}}
            <div class="bg-white rounded-2xl shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-700">
                        📅 Leave Calendar
                    </h3>
                    <a href="https://calendar.google.com" target="_blank"
                        class="text-indigo-600 text-sm hover:underline">
                        Open in Google Calendar →
                    </a>
                </div>

                @if($googleCalendarUrl)
                <div class="rounded-xl overflow-hidden border border-gray-200">
                    <iframe
                        src="{{ $googleCalendarUrl }}"
                        style="border: 0"
                        width="100%"
                        height="600"
                        frameborder="0"
                        scrolling="no"
                        allowfullscreen>
                    </iframe>
                </div>
                @else
                <div class="text-center py-12 text-gray-400">
                    <p class="text-4xl mb-3">📅</p>
                    <p>Google Calendar not connected yet.</p>
                    <a href="{{ route('admin.google.auth') }}"
                        class="text-indigo-600 hover:underline mt-2 inline-block">
                        Connect Google Calendar
                    </a>
                </div>
                @endif
            </div>


        </div>

        </div>
    </body>
</html>