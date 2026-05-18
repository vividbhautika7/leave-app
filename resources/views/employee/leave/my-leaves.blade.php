{{-- resources/views/employee/leave/my-leaves.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leaves</title>
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

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-700">My Leave Applications</h2>
            <a href="{{ route('employee.leave.apply') }}"
               class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                + Apply Leave
            </a>
        </div>

        {{-- Messages --}}
        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow p-6">
            @if($leaves->isEmpty())
                <div class="text-center py-12 text-gray-400">
                    <p class="text-4xl mb-3">📋</p>
                    <p class="text-lg">No leave applications found.</p>
                    <a href="{{ route('employee.leave.apply') }}"
                       class="text-indigo-600 hover:underline mt-2 inline-block">Apply your first leave</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600">
                                <th class="px-4 py-3 text-left rounded-l-lg">#</th>
                                <th class="px-4 py-3 text-left">From</th>
                                <th class="px-4 py-3 text-left">To</th>
                                <th class="px-4 py-3 text-left">Days</th>
                                <th class="px-4 py-3 text-left">Reason</th>
                                <th class="px-4 py-3 text-left">Applied On</th>
                                <th class="px-4 py-3 text-left rounded-r-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaves as $index => $leave)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium">
                                    {{ $leave->start_date->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    {{ $leave->end_date->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full text-xs font-medium">
                                        {{ $leave->total_days }} days
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 max-w-xs">
                                    {{ Str::limit($leave->reason, 50) }}
                                </td>
                                <td class="px-4 py-3 text-gray-400">
                                    {{ $leave->created_at->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($leave->start_date->isFuture())
                                        <form method="POST"
                                              action="{{ route('employee.leave.destroy', $leave->id) }}"
                                              onsubmit="return confirm('Are you sure you want to cancel this leave?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-500 hover:text-red-700 text-xs font-medium">
                                                Cancel Leave
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-300 text-xs">No action</span>
                                    @endif
                                </td>
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