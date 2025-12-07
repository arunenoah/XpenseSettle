<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExpenseSettle - Expense Sharing Made Easy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <h1 class="text-2xl font-bold text-blue-600">💰 ExpenseSettle</h1>
                <div class="flex gap-4">
                    <a href="{{ route('login') }}" class="px-4 py-2 text-blue-600 hover:text-blue-700 font-medium">Sign In</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 sm:px-6 sm:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
        <div class="text-center mb-12 sm:mb-16">
            <h2 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-4">
                Split Expenses
                <span class="text-blue-600">Effortlessly</span>
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 mb-8">
                Track shared expenses with friends, roommates, or colleagues. Settle up fairly and painlessly.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-lg">
                    Create Free Account
                </a>
                <a href="#features" class="px-8 py-3 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors font-semibold text-lg">
                    Learn More
                </a>
            </div>
        </div>

        <!-- Screenshot or Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2">∞</div>
                <p class="text-gray-700 font-semibold">Unlimited Groups</p>
                <p class="text-gray-600 text-sm">Create groups for trips, apartments, events, and more</p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2">🧮</div>
                <p class="text-gray-700 font-semibold">Smart Splitting</p>
                <p class="text-gray-600 text-sm">Equal, custom, or percentage-based splits</p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2">📱</div>
                <p class="text-gray-700 font-semibold">Mobile Friendly</p>
                <p class="text-gray-600 text-sm">Track expenses on the go from any device</p>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="bg-white py-12 sm:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h3 class="text-3xl sm:text-4xl font-bold text-gray-900 text-center mb-12">Key Features</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Feature 1 -->
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Add Expenses</h4>
                        <p class="text-gray-600">Easily log expenses with title, amount, date, and description. Upload receipts for proof.</p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM6 20h12a6 6 0 00-6-6 6 6 0 00-6 6z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Manage Groups</h4>
                        <p class="text-gray-600">Create groups for different purposes. Add and remove members with different roles.</p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Track Balances</h4>
                        <p class="text-gray-600">See who owes whom in real-time. Get instant settlement summaries for each group.</p>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Mark Payments</h4>
                        <p class="text-gray-600">Record when payments are made with proof of payment. Track payment status in real-time.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-blue-600 py-12 sm:py-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h3 class="text-3xl sm:text-4xl font-bold text-white mb-4">Ready to Get Started?</h3>
            <p class="text-lg text-blue-100 mb-8">Join thousands of users splitting expenses fairly and transparently.</p>
            <a href="{{ route('register') }}" class="inline-block px-8 py-3 bg-white text-blue-600 rounded-lg hover:bg-gray-100 transition-colors font-semibold text-lg">
                Create Your Free Account
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-8 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p>&copy; {{ date('Y') }} ExpenseSettle. All rights reserved.</p>
                <p class="text-sm mt-2">Track shared expenses. Split fairly. Settle up.</p>
            </div>
        </div>
    </footer>
</body>
</html>
