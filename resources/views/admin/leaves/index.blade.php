{{-- resources/views/admin/leaves/index.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Leaves - Admin</title>
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

        <h2 class="text-2xl font-bold text-gray-700 mb-6">All Leave Applications</h2>

        {{-- Messages --}}
        @if(session('success'))
        <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-4">
            {{ session('success') }}
        </div>
        @endif

        {{-- Filters --}}
        <div class="bg-white rounded-2xl shadow p-4 mb-6">
            <form method="GET" action="{{ route('admin.leaves.index') }}"
                class="flex flex-wrap gap-4 items-end">

                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">
                        Filter by Employee
                    </label>
                    <select name="employee_id"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}"
                            {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">
                        Filter by Month
                    </label>
                    <input type="month" name="month"
                        value="{{ request('month') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-indigo-700">
                    Apply Filter
                </button>
                <a href="{{ route('admin.leaves.index') }}"
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-300">
                    Reset
                </a>
            </form>
        </div>

        {{-- Leaves Table --}}
        <div class="bg-white rounded-2xl shadow p-6">
            @if($leaves->isEmpty())
            <div class="text-center py-12 text-gray-400">
                <p class="text-4xl mb-3">📋</p>
                <p class="text-lg">No leave applications found.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600">
                            <th class="px-4 py-3 text-left rounded-l-lg">#</th>
                            <th class="px-4 py-3 text-left">Employee</th>
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
                            <td class="px-4 py-3 text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($leave->user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-700">{{ $leave->user->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $leave->user->email }}</p>
                                    </div>
                                </div>
                            </td>
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
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.leaves.show', $leave->id) }}"
                                        class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                        View
                                    </a>
                                    <form method="POST"
                                        action="{{ route('admin.leaves.destroy', $leave->id) }}"
                                        onsubmit="return confirm('Delete this leave application?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-500 hover:text-red-700 text-xs font-medium">
                                            Delete
                                        </button>
                                    </form>
                                </div>
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