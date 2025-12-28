<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExpenseSettle - Split Expenses with Friends</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; }

        .gradient-hero {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        }
        .gradient-cta {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        }
        .feature-card {
            transition: all 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .step-number {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            flex-shrink: 0;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 bg-white shadow-sm z-50 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-14">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">💰</span>
                    <span class="text-lg font-bold text-blue-600">ExpenseSettle</span>
                </div>
                <div class="flex gap-2 sm:gap-3">
                    @guest
                        <a href="{{ route('login') }}" class="px-3 sm:px-4 py-2 text-gray-700 hover:text-blue-600 font-medium text-sm transition">Login</a>
                        <a href="{{ route('register') }}" class="px-4 sm:px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-sm">Sign Up</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="px-4 sm:px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-sm">Dashboard</a>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-hero text-white pt-28 pb-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <h1 class="text-4xl sm:text-5xl md:text-5xl font-bold mb-4 leading-tight">
                        Split Expenses,<br><span class="text-yellow-300">Settle Up Easily</span>
                    </h1>
                    <p class="text-base sm:text-lg text-blue-100 mb-6">
                        Track shared expenses fairly and settle up with friends instantly.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 mb-10">
                        @guest
                            <a href="{{ route('register') }}" class="px-6 py-3 bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-bold transition text-center text-sm">
                                Get Started Free
                            </a>
                            <a href="#features" class="px-6 py-3 border-2 border-white text-white rounded-lg hover:bg-white hover:text-blue-600 font-bold transition text-center text-sm">
                                See Features
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="px-6 py-3 bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-bold transition text-center text-sm">
                                Go to Dashboard
                            </a>
                        @endguest
                    </div>
                    <div class="grid grid-cols-3 gap-4 text-blue-100">
                        <div>
                            <div class="text-xl font-bold">10K+</div>
                            <p class="text-xs">Users</p>
                        </div>
                        <div>
                            <div class="text-xl font-bold">₹100M+</div>
                            <p class="text-xs">Settled</p>
                        </div>
                        <div>
                            <div class="text-xl font-bold">100%</div>
                            <p class="text-xs">Free</p>
                        </div>
                    </div>
                </div>
                <div class="hidden md:flex justify-center">
                    <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 w-full max-w-xs">
                        <div class="text-center text-white py-20">
                            <div class="text-5xl mb-3">📱</div>
                            <p class="text-sm font-semibold">App Preview</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Problem Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-3">The Problem</h2>
                <p class="text-gray-600">Splitting expenses shouldn't be complicated</p>
            </div>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                    <div class="text-4xl mb-4">😰</div>
                    <h3 class="text-xl font-bold mb-4 text-gray-900">For Groups</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex gap-2">
                            <span class="text-red-500 flex-shrink-0">•</span>
                            <span>Manual tracking becomes a nightmare</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-red-500 flex-shrink-0">•</span>
                            <span>Fair splits need spreadsheets and stress</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-red-500 flex-shrink-0">•</span>
                            <span>Who owes whom causes tension</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-red-500 flex-shrink-0">•</span>
                            <span>Settlement gets forgotten or delayed</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                    <div class="text-4xl mb-4">🤐</div>
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Between Friends</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex gap-2">
                            <span class="text-red-500 flex-shrink-0">•</span>
                            <span>Awkward money conversations</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-red-500 flex-shrink-0">•</span>
                            <span>Miscommunication & misunderstandings</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-red-500 flex-shrink-0">•</span>
                            <span>No proof of payments</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-red-500 flex-shrink-0">•</span>
                            <span>Disputes about who settled what</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Solution Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-3">The Solution</h2>
                <p class="text-gray-600">ExpenseSettle handles the complexity</p>
            </div>
            <div class="space-y-4 max-w-3xl mx-auto">
                <!-- Step 1 -->
                <div class="flex gap-4">
                    <div class="step-number w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md flex-shrink-0">1</div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200 flex-1">
                        <h3 class="font-bold text-gray-900 mb-1">Create a Group</h3>
                        <p class="text-sm text-gray-600">Start a group for your trip or event. Invite friends with a link—done in seconds.</p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="flex gap-4">
                    <div class="step-number w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md flex-shrink-0">2</div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200 flex-1">
                        <h3 class="font-bold text-gray-900 mb-1">Add Expenses</h3>
                        <p class="text-sm text-gray-600">Snap receipts or enter manually. AI extracts items. Split equally or customize.</p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="flex gap-4">
                    <div class="step-number w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md flex-shrink-0">3</div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200 flex-1">
                        <h3 class="font-bold text-gray-900 mb-1">See Balances</h3>
                        <p class="text-sm text-gray-600">Algorithm calculates who owes what. Color-coded for clarity.</p>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="flex gap-4">
                    <div class="step-number w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md flex-shrink-0">4</div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200 flex-1">
                        <h3 class="font-bold text-gray-900 mb-1">Settle Up</h3>
                        <p class="text-sm text-gray-600">Record payments. Get instant confirmations. Everyone stays in sync.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-3">Powerful Features</h2>
                <p class="text-gray-600">Everything you need to manage group expenses</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="feature-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="text-4xl mb-3">📸</div>
                    <h3 class="font-bold text-gray-900 mb-2">Receipt Scanning</h3>
                    <p class="text-sm text-gray-600">Snap a photo and AI extracts items, prices, quantities instantly.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="text-4xl mb-3">🧮</div>
                    <h3 class="font-bold text-gray-900 mb-2">Smart Splitting</h3>
                    <p class="text-sm text-gray-600">Split equally, by amount, or assign items to members.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="text-4xl mb-3">👥</div>
                    <h3 class="font-bold text-gray-900 mb-2">Unlimited Groups</h3>
                    <p class="text-sm text-gray-600">Create groups for trips, roommates, events, and more.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="text-4xl mb-3">⚖️</div>
                    <h3 class="font-bold text-gray-900 mb-2">Settlement Matrix</h3>
                    <p class="text-sm text-gray-600">See exactly who owes whom with color-coded clarity.</p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="text-4xl mb-3">💳</div>
                    <h3 class="font-bold text-gray-900 mb-2">Advance Payments</h3>
                    <p class="text-sm text-gray-600">Track advance payments and auto-adjust settlements.</p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="text-4xl mb-3">📄</div>
                    <h3 class="font-bold text-gray-900 mb-2">PDF Reports</h3>
                    <p class="text-sm text-gray-600">Generate professional reports with settlement matrix.</p>
                </div>

                <!-- Feature 7 -->
                <div class="feature-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="text-4xl mb-3">📊</div>
                    <h3 class="font-bold text-gray-900 mb-2">Clear Dashboard</h3>
                    <p class="text-sm text-gray-600">See your balance across all groups at a glance.</p>
                </div>

                <!-- Feature 8 -->
                <div class="feature-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="text-4xl mb-3">🔔</div>
                    <h3 class="font-bold text-gray-900 mb-2">Real-Time Updates</h3>
                    <p class="text-sm text-gray-600">Get instant notifications when payments are made.</p>
                </div>

                <!-- Feature 9 -->
                <div class="feature-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="text-4xl mb-3">🌍</div>
                    <h3 class="font-bold text-gray-900 mb-2">Multi-Currency</h3>
                    <p class="text-sm text-gray-600">Perfect for international trips with different currencies.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Preview -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-3">How It Works</h2>
                <p class="text-gray-600">Simple workflow, powerful results</p>
            </div>
            <div class="space-y-6">
                <div class="flex gap-4 items-start bg-white p-6 rounded-lg border border-gray-200">
                    <div class="text-3xl flex-shrink-0">👥</div>
                    <div>
                        <h3 class="font-bold text-gray-900">Create a Group</h3>
                        <p class="text-sm text-gray-600">Set name, currency, add members. Shareable link for instant onboarding.</p>
                    </div>
                </div>
                <div class="flex gap-4 items-start bg-white p-6 rounded-lg border border-gray-200">
                    <div class="text-3xl flex-shrink-0">📸</div>
                    <div>
                        <h3 class="font-bold text-gray-900">Add Expenses</h3>
                        <p class="text-sm text-gray-600">Snap receipts or enter manually. AI extracts items automatically.</p>
                    </div>
                </div>
                <div class="flex gap-4 items-start bg-white p-6 rounded-lg border border-gray-200">
                    <div class="text-3xl flex-shrink-0">⚖️</div>
                    <div>
                        <h3 class="font-bold text-gray-900">View Balances</h3>
                        <p class="text-sm text-gray-600">Algorithm calculates who owes whom. Color-coded for clarity.</p>
                    </div>
                </div>
                <div class="flex gap-4 items-start bg-white p-6 rounded-lg border border-gray-200">
                    <div class="text-3xl flex-shrink-0">💳</div>
                    <div>
                        <h3 class="font-bold text-gray-900">Settle Up</h3>
                        <p class="text-sm text-gray-600">Record payments. Everyone gets instant confirmations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-3">Simple Pricing</h2>
                <p class="text-gray-600">No hidden fees. Pay only for what you need.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Free Plan -->
                <div class="bg-white p-6 rounded-lg border-2 border-gray-200 hover:border-blue-500 transition">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold mb-2">Free Forever</h3>
                        <div class="text-4xl font-bold text-gray-900">$0</div>
                    </div>
                    <ul class="space-y-2 mb-6 text-sm">
                        <li class="flex gap-2">
                            <span class="text-green-500">✓</span>
                            <span>Unlimited groups</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-green-500">✓</span>
                            <span>Unlimited members</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-green-500">✓</span>
                            <span>Manual entry</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-green-500">✓</span>
                            <span>Basic OCR (5/group)</span>
                        </li>
                    </ul>
                    @guest
                        <a href="{{ route('register') }}" class="block w-full py-2 px-4 text-center bg-gray-100 text-gray-900 rounded font-semibold hover:bg-gray-200">
                            Get Started
                        </a>
                    @endguest
                </div>

                <!-- Trip Pass -->
                <div class="bg-gradient-to-br from-blue-600 to-purple-600 text-white p-6 rounded-lg shadow-lg transform md:scale-105 relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-900 px-3 py-1 rounded-full text-xs font-bold">
                        POPULAR
                    </div>
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold mb-2">Trip Pass</h3>
                        <div class="text-4xl font-bold">$1.99</div>
                        <p class="text-sm text-blue-100">per trip</p>
                    </div>
                    <ul class="space-y-2 mb-6 text-sm">
                        <li class="flex gap-2">
                            <span class="text-yellow-300">✓</span>
                            <span>All Free features</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-yellow-300">✓</span>
                            <span><strong>Unlimited OCR</strong></span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-yellow-300">✓</span>
                            <span><strong>Unlimited items</strong></span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-yellow-300">✓</span>
                            <span>PDF/Excel export</span>
                        </li>
                    </ul>
                    @guest
                        <a href="{{ route('register') }}" class="block w-full py-2 px-4 text-center bg-white text-blue-600 rounded font-bold hover:bg-gray-100">
                            Start Trip
                        </a>
                    @endguest
                </div>

                <!-- Lifetime -->
                <div class="bg-white p-6 rounded-lg border-2 border-purple-300 hover:border-purple-500 transition">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold mb-2">Lifetime</h3>
                        <div class="text-4xl font-bold text-gray-900">$14.99</div>
                        <p class="text-sm text-gray-600">one-time</p>
                    </div>
                    <ul class="space-y-2 mb-6 text-sm">
                        <li class="flex gap-2">
                            <span class="text-purple-600">✓</span>
                            <span>All Trip Pass features</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-purple-600">✓</span>
                            <span><strong>Unlimited trips</strong></span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-purple-600">✓</span>
                            <span><strong>Forever access</strong></span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-purple-600">✓</span>
                            <span>Priority support</span>
                        </li>
                    </ul>
                    @guest
                        <a href="{{ route('register') }}" class="block w-full py-2 px-4 text-center bg-purple-600 text-white rounded font-bold hover:bg-purple-700">
                            Unlock
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-3">FAQ</h2>
                <p class="text-gray-600">Common questions answered</p>
            </div>

            <div class="space-y-3" x-data="{ openFaq: null }">
                <div class="bg-white rounded-lg border border-gray-200">
                    <button @click="openFaq = openFaq === 1 ? null : 1" class="w-full flex justify-between items-center p-4 hover:bg-gray-50">
                        <h3 class="font-semibold text-gray-900 text-left">Is ExpenseSettle really free?</h3>
                        <span class="text-gray-400 flex-shrink-0" :class="{ 'transform rotate-180': openFaq === 1 }">▼</span>
                    </button>
                    <div x-show="openFaq === 1" class="px-4 pb-4 text-sm text-gray-600 border-t">
                        Yes! Core features are free forever. Premium features (unlimited OCR, PDF export) via Trip Pass ($1.99/trip) or Lifetime ($14.99).
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200">
                    <button @click="openFaq = openFaq === 2 ? null : 2" class="w-full flex justify-between items-center p-4 hover:bg-gray-50">
                        <h3 class="font-semibold text-gray-900 text-left">How does OCR scanning work?</h3>
                        <span class="text-gray-400 flex-shrink-0" :class="{ 'transform rotate-180': openFaq === 2 }">▼</span>
                    </button>
                    <div x-show="openFaq === 2" class="px-4 pb-4 text-sm text-gray-600 border-t">
                        Take a receipt photo. AI extracts items, prices, quantities. Assign to members. App calculates fair splits.
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200">
                    <button @click="openFaq = openFaq === 3 ? null : 3" class="w-full flex justify-between items-center p-4 hover:bg-gray-50">
                        <h3 class="font-semibold text-gray-900 text-left">Can I use different currencies?</h3>
                        <span class="text-gray-400 flex-shrink-0" :class="{ 'transform rotate-180': openFaq === 3 }">▼</span>
                    </button>
                    <div x-show="openFaq === 3" class="px-4 pb-4 text-sm text-gray-600 border-t">
                        Yes! Each group has its own currency. Perfect for international trips.
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200">
                    <button @click="openFaq = openFaq === 4 ? null : 4" class="w-full flex justify-between items-center p-4 hover:bg-gray-50">
                        <h3 class="font-semibold text-gray-900 text-left">How do I invite friends?</h3>
                        <span class="text-gray-400 flex-shrink-0" :class="{ 'transform rotate-180': openFaq === 4 }">▼</span>
                    </button>
                    <div x-show="openFaq === 4" class="px-4 pb-4 text-sm text-gray-600 border-t">
                        Create a group and get a shareable link. Send via WhatsApp, email, etc. Friends click to join instantly.
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200">
                    <button @click="openFaq = openFaq === 5 ? null : 5" class="w-full flex justify-between items-center p-4 hover:bg-gray-50">
                        <h3 class="font-semibold text-gray-900 text-left">Is my data secure?</h3>
                        <span class="text-gray-400 flex-shrink-0" :class="{ 'transform rotate-180': openFaq === 5 }">▼</span>
                    </button>
                    <div x-show="openFaq === 5" class="px-4 pb-4 text-sm text-gray-600 border-t">
                        Yes. Industry-standard encryption protects your data. You control who sees your expenses.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="gradient-cta text-white py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Simplify Expenses?</h2>
            <p class="text-gray-100 mb-8">Join thousands managing group expenses fairly and transparently.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @guest
                    <a href="{{ route('register') }}" class="px-6 py-3 bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-bold transition inline-block">
                        Get Started Free
                    </a>
                    <a href="{{ route('login') }}" class="px-6 py-3 border-2 border-white text-white rounded-lg hover:bg-white hover:text-blue-600 font-bold transition inline-block">
                        Sign In
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="px-6 py-3 bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-bold transition inline-block">
                        Go to Dashboard
                    </a>
                @endguest
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xl">💰</span>
                        <span class="font-bold text-white">ExpenseSettle</span>
                    </div>
                    <p class="text-xs">Split expenses fairly.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm">Product</h4>
                    <ul class="space-y-1 text-xs">
                        <li><a href="#features" class="hover:text-white">Features</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">Get Started</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm">Company</h4>
                    <ul class="space-y-1 text-xs">
                        <li><a href="#" class="hover:text-white">About</a></li>
                        <li><a href="#" class="hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm">Legal</h4>
                    <ul class="space-y-1 text-xs">
                        <li><a href="#" class="hover:text-white">Privacy</a></li>
                        <li><a href="#" class="hover:text-white">Terms</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-6 text-center text-xs">
                <p>&copy; {{ date('Y') }} ExpenseSettle. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
