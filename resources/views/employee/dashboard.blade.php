{{-- resources/views/employee/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
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
            <a href="{{ route('profile.show') }}" class="hover:underline">Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="bg-white text-indigo-600 px-4 py-1 rounded-lg font-semibold hover:bg-gray-100">
                    Logout
                </button>
            </form>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-6 py-8">

        {{-- Welcome --}}
        <h2 class="text-2xl font-bold text-gray-700 mb-6">
            Welcome, {{ Auth::user()->name }} 👋
        </h2>

        {{-- Success / Error --}}
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4">
                <div class="bg-indigo-100 text-indigo-600 rounded-full p-4 text-2xl">📋</div>
                <div>
                    <p class="text-gray-500 text-sm">Total Leaves Applied</p>
                    <p class="text-3xl font-bold text-gray-700">{{ $totalLeaves }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4">
                <div class="bg-green-100 text-green-600 rounded-full p-4 text-2xl">📅</div>
                <div>
                    <p class="text-gray-500 text-sm">Upcoming Leaves</p>
                    <p class="text-3xl font-bold text-gray-700">{{ $upcomingLeaves->count() }}</p>
                </div>
            </div>
        </div>

        {{-- Upcoming Leaves --}}
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-700 mb-4">Upcoming Leaves</h3>
            @if($upcomingLeaves->isEmpty())
            <p class="text-gray-400">No upcoming leaves scheduled.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600">
                            <th class="px-4 py-3 text-left rounded-l-lg">From</th>
                            <th class="px-4 py-3 text-left">To</th>
                            <th class="px-4 py-3 text-left">Days</th>
                            <th class="px-4 py-3 text-left rounded-r-lg">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingLeaves as $leave)
                        <tr class="border-t">
                            <td class="px-4 py-3">{{ $leave->start_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $leave->end_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $leave->total_days }} days</td>
                            <td class="px-4 py-3 text-gray-500">{{ Str::limit($leave->reason, 40) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Recent Leaves --}}
        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-700">Recent Leaves</h3>
                <a href="{{ route('employee.leave.my') }}"
                    class="text-indigo-600 text-sm hover:underline">View All</a>
            </div>
            @if($recentLeaves->isEmpty())
            <p class="text-gray-400">No leave applications yet.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600">
                            <th class="px-4 py-3 text-left rounded-l-lg">From</th>
                            <th class="px-4 py-3 text-left">To</th>
                            <th class="px-4 py-3 text-left">Days</th>
                            <th class="px-4 py-3 text-left rounded-r-lg">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLeaves as $leave)
                        <tr class="border-t">
                            <td class="px-4 py-3">{{ $leave->start_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $leave->end_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $leave->total_days }} days</td>
                            <td class="px-4 py-3 text-gray-500">{{ Str::limit($leave->reason, 40) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>
</body>

</html>