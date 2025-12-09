<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExpenseSettle - Split Expenses with Friends</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <span class="text-2xl">💰</span>
                    <span class="ml-2 text-xl font-bold text-blue-600">ExpenseSettle</span>
                </div>
                <div class="flex gap-4">
                    @guest
                        <a href="{{ route('login') }}" class="px-4 py-2 text-gray-700 hover:text-blue-600 font-medium">Login</a>
                        <a href="{{ route('register') }}" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Sign Up</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Dashboard</a>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    Split Expenses with Friends,<br>Settle Up Easily
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-gray-100">
                    Track group expenses, split bills fairly, and settle up with friends - all in one place
                </p>
                <div class="flex gap-4 justify-center">
                    @guest
                        <a href="{{ route('register') }}" class="px-8 py-4 bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-bold text-lg shadow-lg">
                            Get Started Free
                        </a>
                        <a href="#features" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg hover:bg-white hover:text-blue-600 font-bold text-lg">
                            Learn More
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-bold text-lg shadow-lg">
                            Go to Dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Everything You Need to Manage Group Expenses</h2>
                <p class="text-xl text-gray-600">Simple, transparent, and fair expense splitting</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 0: Advance Payments -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="text-4xl mb-4">💸</div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-900">Advance Payments</h3>
                    <p class="text-gray-600 mb-4">Record advance payments for trips or events. Track who paid in advance and automatically adjust settlements.</p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li>✓ Record advance payments</li>
                        <li>✓ Multiple payers support</li>
                        <li>✓ Auto-adjust settlements</li>
                    </ul>
                </div>

                <!-- Feature 1: Dashboard -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="text-4xl mb-4">📊</div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-900">Clear Dashboard</h3>
                    <p class="text-gray-600 mb-4">See at a glance who owes what. Track amounts you owe and amounts owed to you across all groups.</p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li>✓ You Owe summary</li>
                        <li>✓ They Owe You summary</li>
                        <li>✓ Your Balance overview</li>
                    </ul>
                </div>

                <!-- Feature 2: Groups -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="text-4xl mb-4">👥</div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-900">Multiple Groups</h3>
                    <p class="text-gray-600 mb-4">Create groups for trips, roommates, events, or any shared expenses. Manage multiple groups effortlessly.</p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li>✓ Unlimited groups</li>
                        <li>✓ Add multiple members</li>
                        <li>✓ Group admin controls</li>
                    </ul>
                </div>

                <!-- Feature 3: Smart Splitting -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="text-4xl mb-4">🧮</div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-900">Smart Splitting</h3>
                    <p class="text-gray-600 mb-4">Split expenses equally or customize shares. Perfect for any situation - dinners, trips, or household bills.</p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li>✓ Equal split</li>
                        <li>✓ Custom amounts</li>
                        <li>✓ Per-item assignment</li>
                    </ul>
                </div>

                <!-- Feature 4: OCR Receipt Scanning -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="text-4xl mb-4">📸</div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-900">OCR Receipt Scanning</h3>
                    <p class="text-gray-600 mb-4">Snap a photo of your receipt and let AI extract line items automatically. Save time on manual entry!</p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li>✓ Auto-extract items</li>
                        <li>✓ Multiple receipt formats</li>
                        <li>✓ Batch upload support</li>
                    </ul>
                </div>

                <!-- Feature 5: Settlement -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="text-4xl mb-4">⚖️</div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-900">Smart Settlement</h3>
                    <p class="text-gray-600 mb-4">See exactly who needs to pay whom with color-coded indicators. View detailed settlement matrix across all group members.</p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li>✓ Settlement summary (green = gets money, red = needs to pay)</li>
                        <li>✓ Overall group settlement matrix</li>
                        <li>✓ Mark payments as paid</li>
                    </ul>
                </div>

                <!-- Feature 6: Activity Timeline -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="text-4xl mb-4">📋</div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-900">Complete History</h3>
                    <p class="text-gray-600 mb-4">Track all group activities with detailed history. View expenses, advances paid, and all transactions in one place.</p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li>✓ Recent activity feed</li>
                        <li>✓ Advances paid tracking</li>
                        <li>✓ All group transactions</li>
                    </ul>
                </div>

                <!-- Feature 7: Settlement Report -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="text-4xl mb-4">📄</div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-900">Settlement Report</h3>
                    <p class="text-gray-600 mb-4">Generate professional PDF reports with complete group information, settlement matrix, and transaction history.</p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li>✓ PDF export</li>
                        <li>✓ Group information summary</li>
                        <li>✓ Settlement matrix & transactions</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- See It In Action -->
    <section class="py-20 bg-gradient-to-br from-blue-50 to-purple-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">See It In Action</h2>
                <p class="text-xl text-gray-600">A visual walkthrough of ExpenseSettle's powerful features</p>
            </div>

            <div class="space-y-20">
                <!-- Feature 1: Dashboard Overview -->
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="order-2 md:order-1">
                        <div class="bg-white p-8 rounded-2xl shadow-xl">
                            <div class="text-3xl mb-4">📊</div>
                            <h3 class="text-3xl font-bold mb-4 text-gray-900">Your Financial Overview</h3>
                            <p class="text-lg text-gray-600 mb-6">
                                See all your group expenses at a glance. Track who owes you money (green) and who you need to pay (red). 
                                Your balance is always up-to-date across all groups.
                            </p>
                            <ul class="space-y-3 text-gray-700">
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>You Owe:</strong> See total amount you need to pay across all groups</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>They Owe You:</strong> Track money owed to you</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Your Balance:</strong> Net balance showing if you're ahead or behind</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl p-8 shadow-xl">
                            <div class="text-center text-gray-500">
                                <div class="text-6xl mb-4">📱</div>
                                <p class="text-lg">Dashboard Screenshot</p>
                                <p class="text-sm mt-2">Shows: You Owe ₹10,028 | They Owe You ₹1,336 | Your Balance ₹8,692</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 2: Group Dashboard -->
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl p-8 shadow-xl">
                            <div class="text-center text-gray-500">
                                <div class="text-6xl mb-4">👥</div>
                                <p class="text-lg">Group Dashboard Screenshot</p>
                                <p class="text-sm mt-2">Shows: Squad Members with balances, Settlement Summary, Recent Activity</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="bg-white p-8 rounded-2xl shadow-xl">
                            <div class="text-3xl mb-4">👥</div>
                            <h3 class="text-3xl font-bold mb-4 text-gray-900">Smart Group Management</h3>
                            <p class="text-lg text-gray-600 mb-6">
                                Each group has its own dashboard showing member balances, settlement summary, and recent activity. 
                                Color-coded indicators make it crystal clear who needs to pay whom.
                            </p>
                            <ul class="space-y-3 text-gray-700">
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Squad Members:</strong> See each member's paid, share, advance, and total balance</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Settlement Summary:</strong> Quick view of who owes whom</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Recent Activity:</strong> Latest expenses and payments</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Feature 3: OCR Receipt Scanning -->
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="order-2 md:order-1">
                        <div class="bg-white p-8 rounded-2xl shadow-xl">
                            <div class="text-3xl mb-4">📸</div>
                            <h3 class="text-3xl font-bold mb-4 text-gray-900">AI-Powered Receipt Scanning</h3>
                            <p class="text-lg text-gray-600 mb-6">
                                Snap a photo of your receipt and watch the magic happen! Our AI extracts line items, prices, 
                                and quantities automatically. Works with multiple receipt formats and supports batch uploads.
                            </p>
                            <ul class="space-y-3 text-gray-700">
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Auto-Extract:</strong> Items, quantities, and prices extracted instantly</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Multiple Formats:</strong> Works with retail, restaurant, and service receipts</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Batch Upload:</strong> Process multiple receipts at once</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl p-8 shadow-xl">
                            <div class="text-center text-gray-500">
                                <div class="text-6xl mb-4">🔍</div>
                                <p class="text-lg">OCR Scanning Screenshot</p>
                                <p class="text-sm mt-2">Shows: Extracted items with quantities, prices, and assignment options</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 4: Settlement Report -->
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl p-8 shadow-xl">
                            <div class="text-center text-gray-500">
                                <div class="text-6xl mb-4">📄</div>
                                <p class="text-lg">Settlement Report Screenshot</p>
                                <p class="text-sm mt-2">Shows: Professional PDF with group info, settlement matrix, and transactions</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="bg-white p-8 rounded-2xl shadow-xl">
                            <div class="text-3xl mb-4">📄</div>
                            <h3 class="text-3xl font-bold mb-4 text-gray-900">Professional Reports</h3>
                            <p class="text-lg text-gray-600 mb-6">
                                Generate beautiful PDF reports for your group. Perfect for trips, events, or shared living expenses. 
                                Includes complete group information, settlement matrix, and full transaction history.
                            </p>
                            <ul class="space-y-3 text-gray-700">
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Group Information:</strong> Name, currency, members, total expenses</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Settlement Matrix:</strong> Who owes whom at a glance</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-green-600 font-bold text-xl">✓</span>
                                    <span><strong>Transaction History:</strong> Complete list of all expenses</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Simple, Trip-Based Pricing</h2>
                <p class="text-xl text-gray-600">No monthly subscriptions. Pay only when you travel.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Free Plan -->
                <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 hover:border-blue-500 transition-all">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Free Forever</h3>
                        <div class="text-5xl font-bold text-gray-900 mb-2">$0</div>
                        <p class="text-gray-600">Perfect for casual use</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 text-xl">✓</span>
                            <span class="text-gray-700">Unlimited trips</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 text-xl">✓</span>
                            <span class="text-gray-700">Unlimited friends</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 text-xl">✓</span>
                            <span class="text-gray-700">Add expenses manually</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 text-xl">✓</span>
                            <span class="text-gray-700">Basic OCR (5 scans/trip)</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 text-xl">✓</span>
                            <span class="text-gray-700">Basic settlement</span>
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full py-3 px-6 text-center bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 font-semibold transition-colors">
                        Get Started Free
                    </a>
                </div>

                <!-- Trip Pass -->
                <div class="bg-gradient-to-br from-blue-600 to-purple-600 text-white rounded-2xl p-8 transform scale-105 shadow-2xl relative">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-900 px-4 py-1 rounded-full text-sm font-bold">
                        MOST POPULAR
                    </div>
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold mb-2">Trip Pass</h3>
                        <div class="text-5xl font-bold mb-2">$1.99</div>
                        <p class="text-blue-100">per trip</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-300 text-xl">✓</span>
                            <span>Everything in Free, plus:</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-300 text-xl">✓</span>
                            <span><strong>Unlimited OCR scans</strong></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-300 text-xl">✓</span>
                            <span><strong>Unlimited line items</strong></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-300 text-xl">✓</span>
                            <span><strong>Unlimited attachments</strong></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-300 text-xl">✓</span>
                            <span>Auto-correct settlement</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-300 text-xl">✓</span>
                            <span>Export PDF/Excel</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-300 text-xl">✓</span>
                            <span>Smart simplified payment</span>
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full py-3 px-6 text-center bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-bold transition-colors">
                        Start Your Trip
                    </a>
                </div>

                <!-- Lifetime -->
                <div class="bg-white border-2 border-purple-300 rounded-2xl p-8 hover:border-purple-500 transition-all">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Lifetime Unlock</h3>
                        <div class="text-5xl font-bold text-gray-900 mb-2">$14.99</div>
                        <p class="text-gray-600">One-time payment</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start gap-3">
                            <span class="text-purple-600 text-xl">✓</span>
                            <span class="text-gray-700">Everything in Trip Pass</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-purple-600 text-xl">✓</span>
                            <span class="text-gray-700"><strong>Unlimited trips forever</strong></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-purple-600 text-xl">✓</span>
                            <span class="text-gray-700"><strong>No recurring fees</strong></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-purple-600 text-xl">✓</span>
                            <span class="text-gray-700">Priority support</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-purple-600 text-xl">✓</span>
                            <span class="text-gray-700">Early access to features</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-purple-600 text-xl">✓</span>
                            <span class="text-gray-700">Best value for frequent travelers</span>
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full py-3 px-6 text-center bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold transition-colors">
                        Unlock Lifetime
                    </a>
                </div>
            </div>

            <div class="text-center mt-12">
                <p class="text-gray-600 text-lg">💡 <strong>Why Trip Pass?</strong> Most people don't travel every month. Pay only when you need it!</p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">How It Works</h2>
                <p class="text-xl text-gray-600">Get started in 3 simple steps</p>
            </div>

            <div class="grid md:grid-cols-3 gap-12">
                <div class="text-center">
                    <div class="w-20 h-20 bg-blue-600 text-white rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-6">1</div>
                    <h3 class="text-2xl font-bold mb-3">Create a Group</h3>
                    <p class="text-gray-600">Start a new group for your trip, event, or shared living space. Invite friends to join.</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 bg-blue-600 text-white rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-6">2</div>
                    <h3 class="text-2xl font-bold mb-3">Add Expenses</h3>
                    <p class="text-gray-600">Record expenses as they happen. Use OCR to scan receipts or enter manually. Split equally or customize.</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 bg-blue-600 text-white rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-6">3</div>
                    <h3 class="text-2xl font-bold mb-3">Settle Up</h3>
                    <p class="text-gray-600">See who owes what and settle up easily. Track payments and keep everyone in sync.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">Ready to Simplify Your Group Expenses?</h2>
            <p class="text-xl mb-8 text-gray-100">Join thousands of users who trust ExpenseSettle for fair and easy expense splitting</p>
            @guest
                <a href="{{ route('register') }}" class="inline-block px-8 py-4 bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-bold text-lg shadow-lg">
                    Get Started Free
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="inline-block px-8 py-4 bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-bold text-lg shadow-lg">
                    Go to Dashboard
                </a>
            @endguest
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="flex items-center justify-center mb-4">
                    <span class="text-2xl">💰</span>
                    <span class="ml-2 text-xl font-bold text-white">ExpenseSettle</span>
                </div>
                <p class="text-sm mb-4">© 2025 ExpenseSettle. All rights reserved.</p>
                <p class="text-sm">Split expenses fairly and settle up easily with friends.</p>
            </div>
        </div>
    </footer>
</body>
</html>