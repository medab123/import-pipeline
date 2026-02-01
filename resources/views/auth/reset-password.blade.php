<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="bg-white dark:bg-[#161615] rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold mb-6 text-center">Reset Password</h1>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-[#fff2f2] dark:bg-[#1D0002] border border-[#F53003] rounded">
                    <ul class="list-disc list-inside text-sm text-[#F53003] dark:text-[#FF4433]">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium mb-2">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        required
                        autofocus
                        class="w-full px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white"
                    >
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium mb-2">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white"
                    >
                </div>

                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium mb-2">Confirm Password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        class="w-full px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-black dark:bg-white text-white dark:text-[#1C1C1A] py-2 px-4 rounded-sm font-medium hover:opacity-90 transition-opacity"
                >
                    Reset Password
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:underline">
                    Back to login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
