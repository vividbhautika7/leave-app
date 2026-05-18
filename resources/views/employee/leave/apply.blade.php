{{-- resources/views/employee/leave/apply.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Leave</title>
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

    <div class="max-w-2xl mx-auto px-6 py-10">
        <div class="bg-white rounded-2xl shadow p-8">

            {{-- Below h2 title --}}
            <h2 class="text-2xl font-bold text-gray-700 mb-2">Apply for Leave</h2>

            {{-- Add this notice --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3 mb-6">
                <p class="text-yellow-700 text-sm font-medium">
                    ⚠️ Same day urgent leave must be applied before <strong>12:00 PM</strong>.
                </p>
                <p class="text-yellow-600 text-xs mt-1">
                    Current time:
                    <strong>{{ \Carbon\Carbon::now(config('app.timezone', 'Asia/Kolkata'))->format('h:i A') }}</strong>
                </p>
            </div>
            {{-- Errors --}}
            @if(session('error'))
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4">
                {{ session('error') }}
            </div>
            @endif
            @if($errors->any())
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('employee.leave.store') }}">
                @csrf

                {{-- Start Date --}}
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-1">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}"
                        min="{{ today()->format('Y-m-d') }}" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                {{-- End Date --}}
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-1">
                        End Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}"
                        min="{{ today()->format('Y-m-d') }}" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                {{-- Reason --}}
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-1">
                        Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reason" rows="4" required minlength="10" maxlength="500"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        placeholder="Briefly describe your reason for leave...">{{ old('reason') }}</textarea>
                    <p class="text-gray-400 text-xs mt-1">Minimum 10 characters</p>
                </div>

                {{-- Buttons --}}
                <div class="flex gap-4">
                    <button type="submit"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                        Submit Application
                    </button>
                    <a href="{{ route('employee.dashboard') }}"
                        class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Auto set end date min based on start date --}}
    <script>
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');

    startDate.addEventListener('change', function() {
        endDate.min = this.value;
        if (endDate.value && endDate.value < this.value) {
            endDate.value = this.value;
        }
    });
    </script>

</body>

</html>