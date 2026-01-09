<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SettleX') - Expense Sharing Made Easy</title>
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
                    ⚖️ SettleX
                </a>
            </div>

            <!-- User Info & Navigation Menu & Notifications Bar (Combined) -->
            <div class="flex justify-between items-center py-2 gap-2 sm:gap-4 px-2 border-b border-gray-100">
                <!-- Left: User Name -->
                <span class="text-sm text-gray-700 font-medium whitespace-nowrap">{{ auth()->user()->name }}</span>

                <!-- Center: Menu Items -->
                <div class="flex justify-center items-center gap-1 sm:gap-4 overflow-x-auto flex-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                    <span>🏠</span>
                    <span class="hidden xs:inline sm:inline">Home</span>
                </a>
                <a href="{{ route('groups.index') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('groups.index') ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                    <span>👥</span>
                    <span class="hidden xs:inline sm:inline">Groups</span>
                </a>
                <a href="{{ route('auth.show-update-pin') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('auth.show-update-pin') ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                    <span>🔒</span>
                    <span class="hidden xs:inline sm:inline">Pin</span>
                </a>

                @if(auth()->user()->email === 'arun@example.com')
                    <a href="{{ route('admin.verify') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('admin.*') ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : 'bg-purple-100 text-purple-900 hover:bg-purple-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap border-2 border-purple-300">
                        <span>🔧</span>
                        <span class="hidden xs:inline sm:inline">Admin</span>
                    </a>
                @endif

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="flex items-center gap-1 px-2 py-2 sm:px-4 bg-gray-100 text-gray-900 hover:bg-red-100 hover:text-red-700 rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                        <span>🚪</span>
                        <span class="hidden xs:inline sm:inline">Exit</span>
                    </button>
                </form>
                </div>

                <!-- Right: Notifications -->
                @php
                    $unreadCount = \App\Models\Activity::where('user_id', '<>', auth()->id())
                        ->whereIn('group_id', auth()->user()->groups()->pluck('groups.id'))
                        ->unreadFor(auth()->id())
                        ->count();
                @endphp

                <div class="relative flex-shrink-0"
                     @notificationData="$el.parentElement.__alpineNotifications = $event.detail"
                     x-data="notificationComponent()">
                    <button @click="open = !open; if(open) { loadNotifications(); }"
                            class="relative text-gray-700 hover:text-blue-600 font-medium transition-colors p-2 cursor-pointer"
                            title="Notifications">
                        <span>🔔</span>
                        <span x-show="unreadCount > 0"
                              x-text="unreadCount"
                              class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center px-1.5 py-0.5 min-w-fit">
                        </span>
                    </button>

                    <!-- Notification Panel -->
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition
                         class="absolute left-1/2 -translate-x-1/2 sm:left-auto sm:right-0 sm:translate-x-0 top-full mt-2 w-full sm:w-[500px] max-w-[calc(100vw-16px)] sm:max-w-none bg-white rounded-lg shadow-2xl border border-gray-200 z-50">

                        <!-- Header with Tabs -->
                        <div class="px-3 py-2.5 sm:p-4 border-b border-gray-200 bg-white">
                            <div class="flex items-center justify-between mb-2 sm:mb-3 gap-1.5 sm:gap-2">
                                <div class="flex gap-1.5 sm:gap-4">
                                    <button @click="filter = 'unread'; loadNotifications()"
                                            :class="filter === 'unread' ? 'text-blue-600 font-bold border-b-2 border-blue-600' : 'text-gray-600'"
                                            class="pb-1 transition-colors text-xs sm:text-sm">
                                        Unread <span x-text="unreadCount"></span>
                                    </button>
                                    <button @click="filter = 'all'; loadNotifications()"
                                            :class="filter === 'all' ? 'text-blue-600 font-bold border-b-2 border-blue-600' : 'text-gray-600'"
                                            class="pb-1 transition-colors text-xs sm:text-sm">
                                        All
                                    </button>
                                </div>
                                <button @click="markAllAsRead()"
                                        x-show="unreadCount > 0"
                                        class="text-xs sm:text-sm text-teal-600 hover:text-teal-700 font-semibold whitespace-nowrap">
                                    Mark all as read
                                </button>
                            </div>
                        </div>

                        <!-- Notifications List -->
                        <div class="max-h-80 sm:max-h-96 overflow-y-auto bg-gray-50 p-2">
                            <template x-if="activities.length === 0">
                                <div class="p-4 sm:p-8 text-center text-gray-500">
                                    <span class="text-2xl">🔔</span>
                                    <p class="text-xs sm:text-sm mt-2">No notifications</p>
                                </div>
                            </template>

                            <template x-for="activity in activities" :key="activity.id">
                                <div @click="markAsRead(activity.id)"
                                     :class="{
                                         'border-l-4 border-blue-500': activity.type === 'expense_created',
                                         'border-l-4 border-green-500': activity.type === 'payment_made',
                                         'border-l-4 border-amber-500': activity.type === 'advance_paid',
                                         'border-l-4 border-purple-500': activity.type !== 'expense_created' && activity.type !== 'payment_made' && activity.type !== 'advance_paid'
                                     }"
                                     class="mx-1.5 my-2 p-3 sm:mx-2 sm:my-2.5 sm:p-4 bg-white rounded-lg transition-all cursor-pointer hover:shadow-md">
                                    <!-- Header: Icon + User/Group + Badge + Unread Dot -->
                                    <div class="flex items-center gap-2 sm:gap-3 mb-3">
                                        <div :class="{
                                            'bg-blue-100 border-2 border-blue-300': activity.type === 'expense_created',
                                            'bg-green-100 border-2 border-green-300': activity.type === 'payment_made',
                                            'bg-amber-100 border-2 border-amber-300': activity.type === 'advance_paid',
                                            'bg-purple-100 border-2 border-purple-300': activity.type !== 'expense_created' && activity.type !== 'payment_made' && activity.type !== 'advance_paid'
                                        }"
                                        class="w-8 sm:w-10 h-8 sm:h-10 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span x-text="activity.icon" class="text-lg sm:text-xl"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs sm:text-sm font-semibold text-gray-900 line-clamp-1" x-text="`${activity.user_name} • ${activity.group_name}`"></p>
                                        </div>
                                        <span :class="{
                                            'bg-blue-100 text-blue-700': activity.type === 'expense_created',
                                            'bg-green-100 text-green-700': activity.type === 'payment_made',
                                            'bg-amber-100 text-amber-700': activity.type === 'advance_paid',
                                            'bg-purple-100 text-purple-700': activity.type !== 'expense_created' && activity.type !== 'payment_made' && activity.type !== 'advance_paid'
                                        }"
                                        class="text-xs sm:text-sm font-bold px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full whitespace-nowrap flex-shrink-0"
                                        x-text="{
                                            'expense_created': '📝',
                                            'payment_made': '✅',
                                            'advance_paid': '💰'
                                        }[activity.type] || '📌'">
                                        </span>
                                        <span x-show="!activity.is_read" class="w-2.5 h-2.5 bg-blue-500 rounded-full flex-shrink-0"></span>
                                    </div>

                                    <!-- Activity Details Based on Type -->
                                    <template x-if="activity.type === 'expense_created'">
                                        <div class="space-y-2">
                                            <p class="text-xs sm:text-sm text-gray-700 line-clamp-1" x-text="`${activity.title}`"></p>
                                            <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                                                <div>
                                                    <p class="text-xs text-gray-500 mb-0.5">Total</p>
                                                    <p class="text-base sm:text-lg font-bold text-blue-600" x-text="`₹${activity.amount ? parseFloat(activity.amount).toFixed(2) : '0.00'}`"></p>
                                                </div>
                                                <template x-if="activity.user_share !== null">
                                                    <div class="text-right">
                                                        <p class="text-xs text-gray-500 mb-0.5">You owe</p>
                                                        <p class="text-base sm:text-lg font-bold text-blue-700" x-text="`₹${parseFloat(activity.user_share).toFixed(2)}`"></p>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="activity.type === 'payment_made'">
                                        <div class="space-y-2">
                                            <p class="text-xs sm:text-sm text-gray-600">Marked payment as complete</p>
                                            <p class="text-base sm:text-lg font-bold text-green-600" x-text="`₹${activity.amount ? parseFloat(activity.amount).toFixed(2) : '0.00'}`"></p>
                                        </div>
                                    </template>

                                    <template x-if="activity.type === 'advance_paid'">
                                        <div class="space-y-2">
                                            <p class="text-xs sm:text-sm text-gray-600">Paid advance</p>
                                            <p class="text-base sm:text-lg font-bold text-amber-600" x-text="`₹${activity.amount ? parseFloat(activity.amount).toFixed(2) : '0.00'} per person`"></p>
                                        </div>
                                    </template>

                                    <template x-if="activity.type !== 'expense_created' && activity.type !== 'payment_made' && activity.type !== 'advance_paid'">
                                        <p class="text-xs sm:text-sm text-gray-700" x-text="activity.title"></p>
                                    </template>

                                    <!-- Timestamp -->
                                    <p class="text-xs text-gray-400 mt-2 sm:mt-3 pt-2 sm:pt-3 border-t border-gray-100" x-text="formatTime(activity.created_at)"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Notification Component Script -->
    <script nonce="{{ request()->attributes->get('nonce', '') }}">
    function notificationComponent() {
        return {
            open: false,
            filter: 'unread',
            activities: [],
            unreadCount: {{ $unreadCount }},

            loadNotifications() {
                fetch(`/notifications?filter=${this.filter}`)
                    .then(res => res.json())
                    .then(data => {
                        this.activities = data.activities || [];
                        this.unreadCount = data.unread_count || 0;
                    })
                    .catch(err => console.error('Error loading notifications:', err));
            },

            markAsRead(id) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch(`/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                }).then(() => this.loadNotifications());
            },

            markAllAsRead() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch('/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                }).then(() => this.loadNotifications());
            },

            formatTime(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);

                if (diffMins < 1) return 'Just now';
                if (diffMins < 60) return `${diffMins}m ago`;
                if (diffHours < 24) return `${diffHours}h ago`;
                if (diffDays < 7) return `${diffDays}d ago`;
                return date.toLocaleDateString();
            }
        }
    }
    </script>

    <!-- Main Content -->
    <main class="flex-1 py-6 sm:py-8 lg:py-10">
        <!-- Alerts -->
        @if($errors->any())
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <span>⚠️</span>
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
                            <span>✅</span>
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
                            <span>⚠️</span>
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
                <p>&copy; {{ date('Y') }} SettleX. Track shared expenses with ease.</p>
            </div>
        </div>
    </footer>


    <!-- Toast Notifications -->
    @include('components.toast')

    <!-- Confetti Animation -->
    @include('components.confetti')

    <!-- Firebase Cloud Messaging for Capacitor/Mobile -->
    <script nonce="{{ request()->attributes->get('nonce', '') }}">
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
            const title = notification?.title || 'SettleX';
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
