{{-- resources/views/admin/leaves/show.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Detail - Admin</title>
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

    <div class="max-w-3xl mx-auto px-6 py-10">

        <div class="mb-6">
            <a href="{{ route('admin.leaves.index') }}"
                class="text-indigo-600 hover:underline text-sm">← Back to All Leaves</a>
        </div>

        <div class="bg-white rounded-2xl shadow p-8">

            <h2 class="text-2xl font-bold text-gray-700 mb-6">Leave Application Detail</h2>

            {{-- Employee Info --}}
            <div class="flex items-center gap-4 mb-8 p-4 bg-indigo-50 rounded-xl">
                <div class="bg-indigo-600 text-white rounded-full w-14 h-14 flex items-center justify-center font-bold text-xl">
                    {{ strtoupper(substr($leave->user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-700">{{ $leave->user->name }}</p>
                    <p class="text-gray-500 text-sm">{{ $leave->user->email }}</p>
                </div>
            </div>

            {{-- Leave Details --}}
            <div class="grid grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-gray-400 text-xs mb-1">Start Date</p>
                    <p class="font-bold text-gray-700 text-lg">
                        {{ $leave->start_date->format('d M Y') }}
                    </p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-gray-400 text-xs mb-1">End Date</p>
                    <p class="font-bold text-gray-700 text-lg">
                        {{ $leave->end_date->format('d M Y') }}
                    </p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-gray-400 text-xs mb-1">Total Days</p>
                    <p class="font-bold text-indigo-600 text-lg">
                        {{ $leave->total_days }} days
                    </p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-gray-400 text-xs mb-1">Applied On</p>
                    <p class="font-bold text-gray-700 text-lg">
                        {{ $leave->created_at->format('d M Y') }}
                    </p>
                </div>
            </div>

            {{-- Reason --}}
            <div class="bg-gray-50 rounded-xl p-4 mb-8">
                <p class="text-gray-400 text-xs mb-2">Reason for Leave</p>
                <p class="text-gray-700 leading-relaxed">{{ $leave->reason }}</p>
            </div>

            {{-- Google Calendar --}}
            @if($leave->google_event_id)
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-8 flex items-center gap-3">
                <span class="text-green-600 text-xl">✓</span>
                <div>
                    <p class="font-medium text-green-700">Added to Google Calendar</p>
                    <p class="text-green-600 text-xs">Event ID: {{ $leave->google_event_id }}</p>
                </div>
            </div>
            @endif

            {{-- Delete Action --}}
            <form method="POST"
                action="{{ route('admin.leaves.destroy', $leave->id) }}"
                onsubmit="return confirm('Are you sure you want to delete this leave?')">
                @csrf
                @method('DELETE')
                <button class="bg-red-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-600 transition">
                    Delete Leave
                </button>
            </form>

        </div>
    </div>
</body>

</html>