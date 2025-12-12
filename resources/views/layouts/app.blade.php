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
    <script nonce="{{ request()->attributes->get('nonce', '') }}">
        window.SANCTUM_TOKEN = "{{ session('sanctum_token', '') }}";
        window.APP_API_URL = "{{ env('APP_URL') }}/api";
    </script>
    @endauth
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Logo Section -->
            <div class="flex justify-center items-center h-16 border-b border-gray-100">
                <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-blue-600">
                    💰 ExpenseSettle
                </a>
            </div>

            <!-- Top Menu - Centered -->
            <div class="flex justify-center items-center py-2 gap-1 sm:gap-4 overflow-x-auto">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                    <span class="text-base sm:text-lg">🏠</span>
                    <span class="hidden xs:inline sm:inline">Home</span>
                </a>
                <a href="{{ route('groups.index') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('groups.index') ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                    <span class="text-base sm:text-lg">👥</span>
                    <span class="hidden xs:inline sm:inline">Groups</span>
                </a>
                <a href="{{ route('auth.show-update-pin') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('auth.show-update-pin') ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                    <span class="text-base sm:text-lg">🔐</span>
                    <span class="hidden xs:inline sm:inline">Pin</span>
                </a>
                
                @if(auth()->user()->email === 'arun@example.com')
                    <a href="{{ route('admin.verify') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('admin.*') ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : 'bg-purple-100 text-purple-900 hover:bg-purple-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap border-2 border-purple-300">
                        <span class="text-base sm:text-lg">🔧</span>
                        <span class="hidden xs:inline sm:inline">Admin</span>
                    </a>
                @endif
                
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="flex items-center gap-1 px-2 py-2 sm:px-4 bg-gray-100 text-gray-900 hover:bg-red-100 hover:text-red-700 rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                        <span class="text-base sm:text-lg">🚪</span>
                        <span class="hidden xs:inline sm:inline">Exit</span>
                    </button>
                </form>
            </div>

            <!-- User Info & Notifications Bar -->
            <div class="flex justify-between items-center py-2 border-t border-gray-100">
                <span class="text-sm text-gray-700 font-medium">{{ auth()->user()->name }}</span>

                <!-- Activity Notification Bell -->
                @php
                    $unreadCount = \App\Models\Activity::where('user_id', '<>', auth()->id())
                        ->whereIn('group_id', auth()->user()->groups()->pluck('groups.id'))
                        ->unreadFor(auth()->id())
                        ->count();
                @endphp

                <div class="relative" x-data="{ open: false, filter: 'unread', activities: [], unreadCount: {{ $unreadCount }} }">
                    <button @click="open = !open; if(open) loadNotifications()" 
                            class="relative text-gray-700 hover:text-blue-600 font-medium transition-colors p-2" 
                            title="Notifications">
                        🔔
                        <span x-show="unreadCount > 0" 
                              x-text="unreadCount > 9 ? '9+' : unreadCount"
                              class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">
                        </span>
                    </button>

                    <!-- Notification Panel -->
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition
                         class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-2xl border border-gray-200 z-50"
                         style="display: none;">
                        
                        <!-- Header with Tabs -->
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex gap-4">
                                    <button @click="filter = 'unread'; loadNotifications()" 
                                            :class="filter === 'unread' ? 'text-blue-600 font-bold border-b-2 border-blue-600' : 'text-gray-600'"
                                            class="pb-1 transition-colors">
                                        Unread <span x-text="unreadCount"></span>
                                    </button>
                                    <button @click="filter = 'all'; loadNotifications()" 
                                            :class="filter === 'all' ? 'text-blue-600 font-bold border-b-2 border-blue-600' : 'text-gray-600'"
                                            class="pb-1 transition-colors">
                                        All
                                    </button>
                                </div>
                                <button @click="markAllAsRead()" 
                                        x-show="unreadCount > 0"
                                        class="text-sm text-teal-600 hover:text-teal-700 font-semibold">
                                    Mark all as read
                                </button>
                            </div>
                        </div>

                        <!-- Notifications List -->
                        <div class="max-h-96 overflow-y-auto">
                            <template x-if="activities.length === 0">
                                <div class="p-8 text-center text-gray-500">
                                    <span class="text-4xl mb-2 block">🔔</span>
                                    <p class="text-sm">No notifications</p>
                                </div>
                            </template>
                            
                            <template x-for="activity in activities" :key="activity.id">
                                <div @click="markAsRead(activity.id)" 
                                     class="p-3 border-b border-gray-100 hover:bg-blue-50 transition-colors cursor-pointer relative">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                            <span x-text="activity.icon" class="text-lg"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-900" x-text="activity.title"></p>
                                            <p class="text-xs text-gray-500 mt-1" x-text="formatTime(activity.created_at)"></p>
                                        </div>
                                        <span x-show="!activity.is_read" class="w-2 h-2 bg-teal-500 rounded-full flex-shrink-0 mt-2"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <script nonce="{{ request()->attributes->get(\'nonce\', \'\') }}">
                function loadNotifications() {
                    const filter = Alpine.store('notifications')?.filter || 'unread';
                    fetch(`/notifications?filter=${filter}`)
                        .then(res => res.json())
                        .then(data => {
                            Alpine.store('notifications', {
                                activities: data.activities.map(a => ({
                                    ...a,
                                    is_read: a.read_by && a.read_by.includes({{ auth()->id() }})
                                })),
                                unreadCount: data.unread_count,
                                filter: data.filter
                            });
                        });
                }

                function markAsRead(id) {
                    fetch(`/notifications/${id}/read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    }).then(() => loadNotifications());
                }

                function markAllAsRead() {
                    fetch('/notifications/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    }).then(() => loadNotifications());
                }

                function formatTime(dateString) {
                    const date = new Date(dateString);
                    const now = new Date();
                    const diffMs = now - date;
                    const diffMins = Math.floor(diffMs / 60000);
                    const diffHours = Math.floor(diffMs / 3600000);
                    const diffDays = Math.floor(diffMs / 86400000);

                    if (diffMins < 1) return 'Just now';
                    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
                    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
                    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
                    return date.toLocaleDateString();
                }
                </script>
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
    <script nonce="{{ request()->attributes->get(\'nonce\', \'\') }}">
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
