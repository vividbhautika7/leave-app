{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Leave App</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md">

        {{-- Logo / Title --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-indigo-600">Leave App</h1>
            <p class="text-gray-500 mt-1">Sign in to your account</p>
        </div>

        {{-- Session Error --}}
        @if(session('error'))
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4">
                {{ session('error') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Login Form --}}
        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Email --}}
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-1">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    placeholder="you@company.com"
                >
            </div>

            {{-- Password --}}
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-1">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    placeholder="••••••••"
                >
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                class="w-full bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                Sign In
            </button>
        </form>

        {{-- Register Link --}}
        <p class="text-center text-gray-500 mt-6 text-sm">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-indigo-600 font-medium hover:underline">
                Register here
            </a>
        </p>

    </div>

</body>
</html>