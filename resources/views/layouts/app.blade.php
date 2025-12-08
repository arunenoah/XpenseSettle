<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ExpenseSettle') - Expense Sharing Made Easy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <!-- Firebase & Sanctum Token for Mobile Notifications -->
    @auth
    <script>
        window.SANCTUM_TOKEN = "{{ session('sanctum_token', '') }}";
        window.APP_API_URL = "{{ env('APP_URL') }}/api";
    </script>
    @endauth
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-blue-600">
                        💰 ExpenseSettle
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex gap-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-bold transition-all text-base">
                        <span class="text-xl">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('groups.index') }}" class="flex items-center gap-2 px-4 py-2 {{ request()->routeIs('groups.index') ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-bold transition-all text-base">
                        <span class="text-xl">📋</span>
                        <span>All Groups</span>
                    </a>
                </div>


                <!-- Mobile Quick Actions (sm devices only) -->
                <div class="md:hidden flex items-center gap-1">
                    <a href="{{ route('dashboard') }}" class="p-2 text-xl hover:bg-gray-100 rounded-lg transition-all {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-700' }}" title="Dashboard">
                        📊
                    </a>
                    <a href="{{ route('groups.index') }}" class="p-2 text-xl hover:bg-gray-100 rounded-lg transition-all {{ request()->routeIs('groups.*') ? 'text-blue-600' : 'text-gray-700' }}" title="Groups">
                        👥
                    </a>
                    <a href="{{ route('auth.show-update-pin') }}" class="p-2 text-xl hover:bg-gray-100 rounded-lg transition-all {{ request()->routeIs('auth.show-update-pin') ? 'text-blue-600' : 'text-gray-700' }}" title="Change PIN">
                        🔐
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="p-2 text-xl hover:bg-red-100 rounded-lg transition-all text-gray-700" title="Logout">🚪</button>
                    </form>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <span class="text-sm text-gray-700">{{ auth()->user()->name }}</span>

                    <!-- Activity Notification Bell -->
                    @php
                        $unreadActivities = \App\Models\Activity::where('user_id', '<>', auth()->id())
                            ->whereIn('group_id', auth()->user()->groups()->pluck('groups.id'))
                            ->orderByDesc('created_at')
                            ->limit(5)
                            ->get();
                    @endphp

                    <div class="relative group">
                        <button class="relative text-gray-700 hover:text-blue-600 font-medium transition-colors p-2" title="Recent Activity">
                            🔔
                            @if($unreadActivities->count() > 0)
                                <span class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">
                                    {{ $unreadActivities->count() > 9 ? '9+' : $unreadActivities->count() }}
                                </span>
                            @endif
                        </button>

                        <!-- Activity Dropdown -->
                        @if($unreadActivities->count() > 0)
                        <div class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-40">
                            <div class="p-4 border-b border-gray-200">
                                <h3 class="text-sm font-bold text-gray-900">Recent Activity</h3>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                @foreach($unreadActivities as $activity)
                                <div class="p-3 border-b border-gray-100 hover:bg-blue-50 transition-colors">
                                    <div class="flex items-start gap-2">
                                        <span class="text-lg mt-1">{{ $activity->icon }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $activity->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ $activity->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <a href="{{ route('auth.show-update-pin') }}" class="text-gray-700 hover:text-blue-600 font-medium" title="Update PIN">
                        🔐
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-700 hover:text-blue-600 font-medium">Logout</button>
                    </form>
                </div>
            </div>

        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 py-6 sm:py-8 lg:py-10">
        <!-- Alerts -->
        @if($errors->any())
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm text-red-700">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Page Content -->
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-sm text-gray-600">
                <p>&copy; {{ date('Y') }} ExpenseSettle. Track shared expenses with ease.</p>
            </div>
        </div>
    </footer>


    <!-- Toast Notifications -->
    @include('components.toast')

    <!-- Confetti Animation -->
    @include('components.confetti')

    <!-- Firebase Cloud Messaging for Capacitor/Mobile -->
    <script>
        // Setup Firebase messaging when Capacitor is available and user is authenticated
        document.addEventListener('DOMContentLoaded', async function() {
            if (typeof window.Capacitor !== 'undefined' && window.SANCTUM_TOKEN) {
                console.log('✅ Capacitor + Token detected - Setting up Firebase...');
                await setupFirebaseMessaging();
            }
        });

        async function setupFirebaseMessaging() {
            try {
                const { FirebaseMessaging } = window.Capacitor.Plugins;

                console.log('📱 Requesting notification permissions...');

                // Request permissions
                const permissionResult = await FirebaseMessaging.requestPermissions();

                if (permissionResult.receive === 'granted') {
                    console.log('✅ Notifications allowed');

                    // Get device token
                    const tokenResult = await FirebaseMessaging.getToken();
                    const deviceToken = tokenResult.token;

                    console.log('🔑 Device Token:', deviceToken.substring(0, 20) + '...');

                    // Register token with backend
                    await registerTokenWithBackend(deviceToken);

                    // Listen for notifications
                    setupNotificationListeners(FirebaseMessaging);
                } else {
                    console.log('⚠️ Notifications denied');
                }
            } catch (error) {
                console.error('❌ Firebase setup error:', error);
            }
        }

        async function registerTokenWithBackend(token) {
            try {
                const sanctumToken = window.SANCTUM_TOKEN;

                if (!sanctumToken) {
                    console.log('⚠️ No Sanctum token found - skipping registration');
                    return;
                }

                console.log('📤 Registering token with backend...');

                const response = await fetch(window.APP_API_URL + '/device-tokens', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + sanctumToken,
                    },
                    body: JSON.stringify({
                        token: token,
                        device_name: 'Android Device',
                        device_type: 'android',
                        app_version: '1.0.0'
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    console.log('✅ Token registered:', data.message);
                    localStorage.setItem('device_token', token);
                } else {
                    console.error('❌ Registration failed:', data);
                }
            } catch (error) {
                console.error('❌ Failed to register token:', error);
            }
        }

        function setupNotificationListeners(FirebaseMessaging) {
            console.log('👂 Setting up notification listeners...');

            // Handle notification when app is in foreground
            FirebaseMessaging.addListener('messageReceived', (event) => {
                console.log('📬 Foreground notification:', event);
                handleForegroundNotification(event.notification);
            });

            // Handle notification tap when app in background
            FirebaseMessaging.addListener('notificationActionPerformed', (event) => {
                console.log('👆 Notification tapped:', event);
                handleNotificationTap(event.notification);
            });

            console.log('✅ Notification listeners ready');
        }

        function handleForegroundNotification(notification) {
            const title = notification?.title || 'ExpenseSettle';
            const body = notification?.body || 'New notification';

            console.log(`🔔 ${title}: ${body}`);

            // Show a green banner at top
            const banner = document.createElement('div');
            banner.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                padding: 16px;
                z-index: 9999;
                text-align: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                font-weight: bold;
            `;
            banner.innerHTML = `<strong>${title}</strong><p>${body}</p>`;
            document.body.appendChild(banner);

            setTimeout(() => banner.remove(), 5000);
        }

        function handleNotificationTap(notification) {
            const data = notification?.data || {};

            console.log('🔗 Navigating:', data);

            if (data.group_id) {
                window.location.href = `/groups/${data.group_id}/summary`;
            }
        }
    </script>
</body>
</html>
