<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SettleX</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Mobile-first critical styles - only on mobile */
        @media (max-width: 768px) {
            * {
                -webkit-tap-highlight-color: transparent;
                -webkit-touch-callout: none;
                -webkit-user-select: none;
                user-select: none;
            }
            
            body {
                overscroll-behavior: none;
                -webkit-overflow-scrolling: touch;
                touch-action: manipulation;
            }
            
            input, textarea {
                -webkit-user-select: text;
                user-select: text;
            }
        }
        
        .ripple {
            position: relative;
            overflow: hidden;
        }
        
        .ripple::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .ripple:active::before {
            width: 300px;
            height: 300px;
        }
        
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .safe-area-top {
            padding-top: env(safe-area-inset-top);
        }
        
        .safe-area-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
        
        /* Bottom tab bar - mobile only */
        .bottom-tab-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: none;
            justify-content: space-around;
            padding: 8px 0 max(8px, env(safe-area-inset-bottom));
            z-index: 50;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 1024px) {
            .bottom-tab-bar {
                display: flex;
            }
        }
        
        .tab-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px;
            color: #6b7280;
            text-decoration: none;
            transition: color 0.2s;
            position: relative;
        }
        
        .tab-item.active {
            color: #374151;
        }
        
        .tab-item.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 3px;
            background: #374151;
            border-radius: 0 0 3px 3px;
        }
        
        /* Toast notifications */
        .toast-container {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            pointer-events: none;
        }
        
        .toast {
            background: #323232;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            margin-bottom: 8px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s;
            pointer-events: auto;
        }
        
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Mobile top bar - hide on desktop */
        .mobile-top-bar {
            display: none !important;
            visibility: hidden !important;
            position: absolute !important;
            top: -9999px !important;
            left: -9999px !important;
        }
        
        @media (max-width: 1024px) {
            .mobile-top-bar {
                display: block !important;
                visibility: visible !important;
                position: relative !important;
                top: auto !important;
                left: auto !important;
            }
        }
        
        /* Desktop header - hide on mobile */
        .desktop-header {
            display: block !important;
        }
        
        @media (max-width: 1024px) {
            .desktop-header {
                display: none !important;
                visibility: hidden !important;
                position: absolute !important;
                top: -9999px !important;
                left: -9999px !important;
                width: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        }
    </style>
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
<body class="bg-[#fefefe] safe-area-top">
    <!-- Mobile Top App Bar -->
    <header class="mobile-top-bar bg-white shadow-sm sticky top-0 z-40 safe-area-top">
        <div class="flex items-center justify-between px-4 py-3">
            <!-- Back Button (conditional) -->
            <button class="p-2 -ml-2 rounded-full ripple" onclick="history.back()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            
            <!-- Title -->
            <div class="flex-1 text-center mr-8">
                <img width="120" src="{{ url('SettleX_logo.png') }}" alt="SettleX Logo" class="h-auto mx-auto">
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-2">
                <!-- Notifications -->
                @php
                    $unreadCount = \App\Models\Activity::where('user_id', '<>', auth()->id())
                        ->whereIn('group_id', auth()->user()->groups()->pluck('groups.id'))
                        ->unreadFor(auth()->id())
                        ->count();
                @endphp
                <a href="#" onclick="openNotificationModal(); return false;" class="p-2 rounded-full ripple relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    @if($unreadCount > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center border-2 border-white">{{ $unreadCount }}</span>
                    @endif
                </a>
            </div>
        </div>
    </header>

    <!-- Desktop Header - Logo Only -->
    <header class="desktop-header bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Logo Section -->
            <div class="flex justify-center items-center h-32 border-b border-gray-100">
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img width="300" src="{{ url('SettleX_logo.png') }}" alt="SettleX Logo">
                </a>
            </div>

            <!-- User Info & Navigation Menu & Notifications Bar (Combined) -->
            <div class="flex justify-between items-center py-2 gap-2 sm:gap-4 px-2 border-b border-gray-100">
                <!-- Left: User Name -->
                <span class="text-sm text-gray-700 font-medium whitespace-nowrap">{{ auth()->user()->name }}</span>

                <!-- Center: Menu Items -->
                <div class="flex justify-center items-center gap-1 sm:gap-4 overflow-x-auto flex-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                    <span>🏠</span>
                    <span class="hidden xs:inline sm:inline">Home</span>
                </a>
                <a href="{{ route('groups.index') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('groups.index') ? 'bg-gray-800 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
                    <span>👥</span>
                    <span class="hidden xs:inline sm:inline">Groups</span>
                </a>
                <a href="{{ route('auth.show-update-pin') }}" class="flex items-center gap-1 px-2 py-2 sm:px-4 {{ request()->routeIs('auth.show-update-pin') ? 'bg-gray-800 text-white shadow-lg' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap">
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

                <div class="relative">
                    <a href="#" onclick="openNotificationModal(); return false;" class="flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span>Notifications</span>
                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ $unreadCount }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="pb-20 md:pb-20 safe-area-bottom">
        @yield('content')
    </main>

    <!-- Bottom Tab Bar -->
    <nav class="bottom-tab-bar">
        <a href="{{ route('dashboard') }}" class="tab-item {{ request()->routeIs('dashboard') ? 'active' : '' }} ripple">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span class="text-xs">Home</span>
        </a>
        
        <a href="{{ route('groups.index') }}" class="tab-item {{ request()->routeIs('groups.*') ? 'active' : '' }} ripple">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span class="text-xs">Groups</span>
        </a>
        
        <a href="{{ route('groups.create') }}" class="tab-item ripple">
            <div class="w-12 h-12 bg-gray-800 rounded-full flex items-center justify-center -mt-2">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
        </a>
        
        <a href="{{ route('auth.show-update-pin') }}" class="tab-item {{ request()->routeIs('auth.*') ? 'active' : '' }} ripple">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <span class="text-xs">PIN</span>
        </a>
        
        <a href="{{ route('logout') }}" class="tab-item ripple" onclick="return confirm('Are you sure you want to logout?')">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span class="text-xs">Logout</span>
        </a>
    </nav>

    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Notification Modal -->
    <div id="notification-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full max-h-[85vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 flex-shrink-0">
                <h2 class="text-lg font-semibold text-gray-900">Notifications</h2>
                <button onclick="closeNotificationModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Body - Scrollable -->
            <div class="flex-1 overflow-y-auto" id="notification-content" style="max-height: 60vh;">
                <!-- Notifications will be loaded here -->
                <div class="text-center text-gray-500 py-8">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <p>Loading notifications...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Touch & Keyboard Scripts -->
    <script>
        // Toast notification system
        function showToast(message, duration = 3000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            container.appendChild(toast);
            
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Notification Modal Functions
        function openNotificationModal() {
            console.log('Opening notification modal...');
            const modal = document.getElementById('notification-modal');
            if (!modal) {
                console.error('Modal not found!');
                alert('Modal not found!');
                return;
            }
            console.log('Modal found:', modal);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden'; // Prevent background scroll
            loadNotifications();
        }

        function closeNotificationModal() {
            console.log('Closing notification modal...');
            const modal = document.getElementById('notification-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto'; // Restore background scroll
        }

        // Test function - call this to test modal
        function testModal() {
            console.log('Testing modal...');
            openNotificationModal();
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('notification-modal');
            if (event.target === modal) {
                closeNotificationModal();
            }
        });

        function loadNotifications() {
            const content = document.getElementById('notification-content');
            
            // Show loading state
            content.innerHTML = `
                <div class="p-4 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
                    <p class="text-gray-500">Loading notifications...</p>
                </div>
            `;
            
            // Fetch notifications page
            fetch('/notifications?filter=unread')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    console.log('Raw HTML response:', html.substring(0, 500));
                    
                    // Try to find the activities JSON data
                    let activities = [];
                    
                    // 1. Look for activities JSON in the response
                    const activitiesMatch = html.match(/"activities":\s*\[([\s\S]*?)\]/);
                    if (activitiesMatch) {
                        try {
                            const activitiesJson = '{"activities":[' + activitiesMatch[1] + ']}';
                            const data = JSON.parse(activitiesJson);
                            activities = data.activities || [];
                            console.log('Found activities via regex:', activities.length);
                        } catch (e) {
                            console.log('Failed to parse activities JSON');
                        }
                    }
                    
                    // 2. Try to find complete JSON object
                    if (activities.length === 0) {
                        const jsonMatches = html.match(/\{[\s\S]*?\}/g);
                        if (jsonMatches) {
                            for (const jsonStr of jsonMatches) {
                                try {
                                    const data = JSON.parse(jsonStr);
                                    if (data.activities) {
                                        activities = data.activities;
                                        console.log('Found activities in JSON:', activities.length);
                                        break;
                                    }
                                } catch (e) {
                                    // Continue trying
                                }
                            }
                        }
                    }
                    
                    // 3. Fallback HTML parsing
                    if (activities.length === 0) {
                        console.log('Falling back to HTML parsing...');
                        activities = parseHtmlForNotifications(html);
                    }
                    
                    console.log('Total activities found:', activities.length);
                    
                    if (activities.length > 0) {
                        displayActivities(activities);
                    } else {
                        // Show debug info
                        content.innerHTML = `
                            <div class="p-4">
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                    <h4 class="text-sm font-medium text-yellow-800 mb-2">Debug Info</h4>
                                    <p class="text-sm text-yellow-700">Found ${activities.length} activities</p>
                                    <p class="text-xs text-yellow-600 mt-2">Raw response length: ${html.length} characters</p>
                                </div>
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 max-h-64 overflow-y-auto">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Raw Content (first 1000 chars):</h4>
                                    <div class="text-xs text-gray-600 font-mono whitespace-pre-wrap">${html.substring(0, 1000)}${html.length > 1000 ? '...' : ''}</div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    content.innerHTML = `
                        <div class="p-4 text-center">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-500 mb-4">Unable to load notifications</p>
                            <button onclick="loadNotifications()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                Try Again
                            </button>
                        </div>
                    `;
                });
        }

        function displayActivities(activities) {
            const content = document.getElementById('notification-content');
            
            let activitiesHtml = '<div class="p-4 space-y-3">';
            
            activities.forEach((activity, index) => {
                const timeAgo = activity.created_at ? formatTimeAgo(activity.created_at) : 'Just now';
                const type = activity.type || 'info';
                const icon = getActivityIcon(type, activity.icon);
                const color = getActivityColor(type);
                
                // Parse metadata if available
                let metadataInfo = '';
                try {
                    if (activity.metadata) {
                        const metadata = JSON.parse(activity.metadata);
                        if (metadata.payer_name) metadataInfo += `Paid by: ${metadata.payer_name}`;
                        if (metadata.split_count) metadataInfo += ` • Split: ${metadata.split_count} people`;
                        if (activity.user_share) metadataInfo += ` • Your share: $${activity.user_share}`;
                    }
                } catch (e) {
                    // Ignore metadata parsing errors
                }
                
                activitiesHtml += `
                    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="handleActivityClick('${activity.id}')">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 ${color.bg} rounded-full flex items-center justify-center text-lg">
                                    ${activity.icon || icon}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900">${activity.title || 'Activity'}</p>
                                    <span class="text-xs text-gray-500">${timeAgo}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">${activity.description || 'No description'}</p>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center space-x-2">
                                        ${activity.amount ? `<span class="text-sm font-semibold text-green-600">$${activity.amount}</span>` : ''}
                                        ${activity.group_name ? `<span class="text-xs text-gray-500">📍 ${activity.group_name}</span>` : ''}
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        ${activity.user_name ? `<span class="text-xs text-gray-500">👤 ${activity.user_name}</span>` : ''}
                                        ${activity.is_read === false ? '<div class="w-2 h-2 bg-blue-500 rounded-full"></div>' : ''}
                                    </div>
                                </div>
                                ${metadataInfo ? `<p class="text-xs text-gray-500 mt-1">${metadataInfo}</p>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            activitiesHtml += '</div>';
            content.innerHTML = activitiesHtml;
        }

        function getActivityIcon(type, emojiIcon) {
            // Use the emoji icon if available, otherwise fallback to type-based icons
            if (emojiIcon) {
                return emojiIcon;
            }
            
            const icons = {
                'expense_created': '💰',
                'payment': '💳',
                'group': '👥',
                'default': '📢'
            };
            return icons[type] || icons['default'];
        }

        function getActivityColor(type) {
            const colors = {
                'expense_created': { bg: 'bg-green-100', dot: 'bg-green-500' },
                'payment': { bg: 'bg-blue-100', dot: 'bg-blue-500' },
                'group': { bg: 'bg-purple-100', dot: 'bg-purple-500' },
                'default': { bg: 'bg-gray-100', dot: 'bg-gray-500' }
            };
            return colors[type] || colors['default'];
        }

        function handleActivityClick(id) {
            console.log('Activity clicked:', id);
            // You can add navigation logic here
            closeNotificationModal();
        }

        function parseHtmlForNotifications(html) {
            const notifications = [];
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Look for various notification patterns
            const selectors = [
                'a[href*="activity"]',
                '.activity',
                '.notification',
                '.list-group-item',
                'tr',
                '[class*="item"]',
                '[class*="notification"]',
                '[class*="activity"]',
                'div[class*="p-3"]',
                'div[class*="p-4"]'
            ];
            
            for (const selector of selectors) {
                const items = temp.querySelectorAll(selector);
                items.forEach((item, index) => {
                    const text = item.textContent.trim();
                    if (text && text.length > 5 && !text.includes('Logout') && !text.includes('Login')) {
                        notifications.push({
                            id: `html-${index}`,
                            title: `Notification ${index + 1}`,
                            message: text,
                            created_at: new Date().toISOString(),
                            type: 'info'
                        });
                    }
                });
                
                if (notifications.length > 0) break;
            }
            
            return notifications.slice(0, 50); // Limit to 50 notifications
        }

        function displayFormattedNotifications(data) {
            const content = document.getElementById('notification-content');
            
            let notificationsHtml = '<div class="p-4 space-y-3">';
            
            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach((notification, index) => {
                    const timeAgo = notification.created_at ? formatTimeAgo(notification.created_at) : 'Just now';
                    const type = notification.type || 'info';
                    const icon = getNotificationIcon(type);
                    const color = getNotificationColor(type);
                    
                    notificationsHtml += `
                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="handleNotificationClick('${notification.id || index}')">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 ${color.bg} rounded-full flex items-center justify-center">
                                        ${icon}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">${notification.title || 'Notification'}</p>
                                        <span class="text-xs text-gray-500">${timeAgo}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">${notification.message || notification.description || 'No message'}</p>
                                    ${notification.group_name ? `<p class="text-xs text-gray-500 mt-1">Group: ${notification.group_name}</p>` : ''}
                                    ${notification.user_name ? `<p class="text-xs text-gray-500">From: ${notification.user_name}</p>` : ''}
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="w-2 h-2 ${color.dot} rounded-full mt-2"></div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                notificationsHtml += `
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-gray-500">No new notifications</p>
                    </div>
                `;
            }
            
            notificationsHtml += '</div>';
            content.innerHTML = notificationsHtml;
        }

        function parseHtmlNotifications(html) {
            const content = document.getElementById('notification-content');
            
            // Create a temporary div to parse HTML
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Look for notification items
            const items = temp.querySelectorAll('a, .activity, .notification, .list-group-item, tr, [class*="item"]');
            
            if (items.length > 0) {
                let notificationsHtml = '<div class="p-4 space-y-3">';
                items.forEach((item, index) => {
                    const text = item.textContent.trim();
                    if (text && text.length > 10) {
                        notificationsHtml += `
                            <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Notification #${index + 1}</p>
                                        <p class="text-sm text-gray-600 mt-1">${text}</p>
                                        <p class="text-xs text-gray-500 mt-1">Just now</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                });
                notificationsHtml += '</div>';
                content.innerHTML = notificationsHtml;
            } else {
                // Show raw content for debugging
                content.innerHTML = `
                    <div class="p-4">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 max-h-64 overflow-y-auto">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Raw Content:</h4>
                            <div class="text-xs text-gray-600 font-mono whitespace-pre-wrap">${html.substring(0, 1000)}${html.length > 1000 ? '...' : ''}</div>
                        </div>
                    </div>
                `;
            }
        }

        function getNotificationIcon(type) {
            const icons = {
                'expense': '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>',
                'payment': '<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
                'group': '<svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
                'default': '<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };
            return icons[type] || icons['default'];
        }

        function getNotificationColor(type) {
            const colors = {
                'expense': { bg: 'bg-green-100', dot: 'bg-green-500' },
                'payment': { bg: 'bg-blue-100', dot: 'bg-blue-500' },
                'group': { bg: 'bg-purple-100', dot: 'bg-purple-500' },
                'default': { bg: 'bg-blue-100', dot: 'bg-blue-500' }
            };
            return colors[type] || colors['default'];
        }

        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000); // seconds
            
            if (diff < 60) return 'Just now';
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            return `${Math.floor(diff / 86400)}d ago`;
        }

        function handleNotificationClick(id) {
            console.log('Notification clicked:', id);
            // You can add navigation logic here
            closeNotificationModal();
        }

        // Handle keyboard avoid for mobile
        if ('visualViewport' in window) {
            window.visualViewport.addEventListener('resize', () => {
                const height = window.visualViewport.height;
                document.documentElement.style.setProperty('--vh', `${height * 0.01}px`);
            });
        }

        // Pull to refresh
        let startY = 0;
        let isPulling = false;
        
        document.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        });
        
        document.addEventListener('touchmove', (e) => {
            if (!isPulling) return;
            
            const currentY = e.touches[0].clientY;
            const diff = currentY - startY;
            
            if (diff > 0 && window.scrollY === 0) {
                e.preventDefault();
                if (diff > 100) {
                    // Trigger refresh
                    window.location.reload();
                }
            }
        });
        
        document.addEventListener('touchend', () => {
            isPulling = false;
        });

        // Remove focus outline on touch devices
        document.addEventListener('touchstart', () => {
            if (document.activeElement) {
                document.activeElement.blur();
            }
        });
    </script>
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
                            class="relative text-gray-700 hover:text-gray-900 font-medium transition-colors p-2 cursor-pointer"
                            title="Notifications">
                        <span>🔔</span>
                        <span x-show="unreadCount > 0"
                              x-text="unreadCount"
                              class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center px-1.5 py-0.5 min-w-fit">
                        </span>
                    </button>

                    <!-- Notification Panel -->
                    <div x-show="open"
                         @click.outside="open = false"
                         x-transition
                         style="width: calc(95vw); max-width: 350px; max-height: 85vh; overflow-y: auto;"
                         class="fixed left-1/2 -translate-x-1/2 sm:!left-auto sm:!right-4 sm:!translate-x-0 top-20 bg-white rounded-lg shadow-2xl border border-gray-200 z-[9999]">

                        <!-- Header with Tabs -->
                        <div class="sticky top-0 px-3 py-2.5 sm:p-4 border-b border-gray-200 bg-white z-10">
                            <div class="flex items-center justify-between mb-2 sm:mb-3 gap-1.5 sm:gap-2">
                                <div class="flex gap-1.5 sm:gap-4">
                                    <button @click="filter = 'unread'; loadNotifications()"
                                            :class="filter === 'unread' ? 'text-gray-900 font-bold border-b-2 border-gray-900' : 'text-gray-600'"
                                            class="pb-1 transition-colors text-xs sm:text-sm">
                                        Unread <span x-text="unreadCount"></span>
                                    </button>
                                    <button @click="filter = 'all'; loadNotifications()"
                                            :class="filter === 'all' ? 'text-gray-900 font-bold border-b-2 border-gray-900' : 'text-gray-600'"
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
                        <div class="bg-gray-50 p-2">
                            <template x-if="activities.length === 0">
                                <div class="p-4 sm:p-8 text-center text-gray-500">
                                    <span class="text-2xl">🔔</span>
                                    <p class="text-xs sm:text-sm mt-2">No notifications</p>
                                </div>
                            </template>

                            <template x-for="activity in activities" :key="activity.id">
                                <div @click="markAsRead(activity.id)"
                                     :class="{
                                         'border-l-4 border-gray-500': activity.type === 'expense_created',
                                         'border-l-4 border-green-500': activity.type === 'payment_made',
                                         'border-l-4 border-amber-500': activity.type === 'advance_paid',
                                         'border-l-4 border-purple-500': activity.type !== 'expense_created' && activity.type !== 'payment_made' && activity.type !== 'advance_paid'
                                     }"
                                     class="mx-1 my-1 p-2.5 sm:mx-1.5 sm:my-1.5 sm:p-3 bg-white rounded-lg transition-all cursor-pointer hover:shadow-md">
                                    <!-- Header: Icon + User/Group + Badge + Unread Dot -->
                                    <div class="flex items-center gap-2 sm:gap-3 mb-2">
                                        <div :class="{
                                            'bg-gray-100 border border-gray-300': activity.type === 'expense_created',
                                            'bg-green-100 border border-green-300': activity.type === 'payment_made',
                                            'bg-amber-100 border border-amber-300': activity.type === 'advance_paid',
                                            'bg-purple-100 border border-purple-300': activity.type !== 'expense_created' && activity.type !== 'payment_made' && activity.type !== 'advance_paid'
                                        }"
                                        class="w-7 sm:w-9 h-7 sm:h-9 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span x-text="activity.icon" class="text-sm sm:text-lg"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs sm:text-sm font-semibold text-gray-900 line-clamp-1" x-text="`${activity.user_name} • ${activity.group_name}`"></p>
                                        </div>
                                        <span :class="{
                                            'bg-gray-100 text-gray-700': activity.type === 'expense_created',
                                            'bg-green-100 text-green-700': activity.type === 'payment_made',
                                            'bg-amber-100 text-amber-700': activity.type === 'advance_paid',
                                            'bg-purple-100 text-purple-700': activity.type !== 'expense_created' && activity.type !== 'payment_made' && activity.type !== 'advance_paid'
                                        }"
                                        class="text-xs font-bold px-1.5 py-0.5 rounded-full whitespace-nowrap flex-shrink-0"
                                        x-text="{
                                            'expense_created': '📝',
                                            'payment_made': '✅',
                                            'advance_paid': '💰'
                                        }[activity.type] || '📌'">
                                        </span>
                                        <span x-show="!activity.is_read" class="w-2.5 h-2.5 bg-gray-500 rounded-full flex-shrink-0"></span>
                                    </div>

                                    <!-- Activity Details Based on Type -->
                                    <template x-if="activity.type === 'expense_created'">
                                        <div class="space-y-1">
                                            <p class="text-xs sm:text-sm text-gray-700 line-clamp-1" x-text="`${activity.title}`"></p>
                                            <div class="grid grid-cols-2 gap-3 pt-1 border-t border-gray-100">
                                                <div>
                                                    <p class="text-xs text-gray-500 mb-0.5">Total</p>
                                                    <p class="text-base sm:text-lg font-bold text-gray-900" x-text="`₹${activity.amount ? parseFloat(activity.amount).toFixed(2) : '0.00'}`"></p>
                                                </div>
                                                <template x-if="activity.user_share !== null">
                                                    <div>
                                                        <p class="text-xs text-gray-500 mb-0.5">You owe</p>
                                                        <p class="text-base sm:text-lg font-bold text-gray-900" x-text="`₹${parseFloat(activity.user_share).toFixed(2)}`"></p>
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
                                    <p class="text-xs text-gray-400 mt-1 pt-1 border-t border-gray-100" x-text="formatTime(activity.created_at)"></p>
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
        // Global initialization guard
        if (window.appScriptLoaded) {
            console.log('⚠️ App script already loaded, preventing duplication');
            // Stop all further execution
            throw new Error('Script already loaded');
        }
        window.appScriptLoaded = true;
        console.log('🚀 App script loading for the first time');
        
        // Setup Firebase messaging when Capacitor is available and user is authenticated
        // Prevent multiple initializations
        if (window.appInitialized) {
            console.log('⚠️ App already initialized, skipping...');
            return;
        }
        window.appInitialized = true;
        
        document.addEventListener('DOMContentLoaded', async function() {
            // Track page loads
            if (!window.pageLoadCount) {
                window.pageLoadCount = 0;
            }
            window.pageLoadCount++;
            console.log(`🔄 Page loaded #${window.pageLoadCount}, modal functions ready`);
            
            // Prevent multiple Firebase setups
            if (window.firebaseSetup) {
                console.log('⚠️ Firebase already setup, skipping...');
                return;
            }
            window.firebaseSetup = true;
            
            // Modal functions are ready
            setTimeout(() => {
                console.log(`📱 Modal test ready - click notification icon or call testModal() (Load #${window.pageLoadCount})`);
            }, 1000);
            
            // Setup Firebase if available
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
