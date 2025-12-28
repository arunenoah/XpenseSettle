<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Member Settlements - {{ $group->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #4F46E5;
        }
        .header h1 {
            color: #4F46E5;
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        .header p {
            margin: 3px 0;
            color: #666;
            font-size: 10px;
        }
        .member-section {
            page-break-inside: avoid;
            margin-bottom: 30px;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            padding: 15px;
            background-color: #FAFAFA;
        }
        .member-header {
            background-color: #4F46E5;
            color: white;
            padding: 10px;
            margin: -15px -15px 15px -15px;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
            font-size: 13px;
        }
        .subsection {
            margin-bottom: 15px;
        }
        .subsection-title {
            background-color: #E0E7FF;
            color: #4338CA;
            padding: 6px 8px;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 11px;
        }
        .expense-item {
            padding: 6px 0;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
        }
        .expense-item:last-child {
            border-bottom: none;
        }
        .expense-title {
            flex: 1;
            color: #374151;
        }
        .expense-amount {
            text-align: right;
            font-weight: bold;
            min-width: 80px;
            padding-left: 10px;
        }
        .subtotal-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-top: 2px solid #D1D5DB;
            border-bottom: 2px solid #D1D5DB;
            font-weight: bold;
            margin-top: 8px;
        }
        .subtotal-label {
            flex: 1;
        }
        .subtotal-amount {
            text-align: right;
            min-width: 80px;
            padding-left: 10px;
        }
        .red-section {
            background-color: #FEE2E2;
            border: 1px solid #FECACA;
        }
        .green-section {
            background-color: #DCFCE7;
            border: 1px solid #BBF7D0;
        }
        .blue-section {
            background-color: #DBEAFE;
            border: 1px solid #BAE6FD;
        }
        .summary-box {
            background-color: #F0F9FF;
            border: 2px solid #0284C7;
            padding: 10px;
            margin-top: 15px;
            border-radius: 4px;
        }
        .summary-amount {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            color: #0284C7;
            margin: 5px 0;
        }
        .owed-details {
            margin-top: 10px;
            font-size: 10px;
        }
        .owed-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px solid #BAE6FD;
        }
        .owed-item:last-child {
            border-bottom: none;
        }
        .empty-message {
            color: #9CA3AF;
            font-style: italic;
            padding: 10px;
            text-align: center;
        }
        .red-text {
            color: #DC2626;
        }
        .green-text {
            color: #059669;
        }
        .adjustment-label {
            color: #7C3AED;
            font-weight: bold;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #D1D5DB;
            color: #9CA3AF;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Member Settlement Report</h1>
        <p><strong>{{ $group->name }}</strong></p>
        <p>Generated on {{ $exportDate }}</p>
    </div>

    @foreach($memberSettlements as $memberId => $data)
        <div class="member-section">
            <div class="member-header">
                {{ $data['user']->name }}
            </div>

            @php
                $settlement = $data['settlement'];
                $hasPaidExpenses = false;
                $hasParticipatedExpenses = false;

                // Check if member has any expenses
                foreach ($settlement as $otherId => $settleData) {
                    if (!empty($settleData['expenses'])) {
                        foreach ($settleData['expenses'] as $exp) {
                            if ($exp['type'] === 'they_owe') {
                                $hasPaidExpenses = true;
                            } else {
                                $hasParticipatedExpenses = true;
                            }
                        }
                    }
                }
            @endphp

            <!-- Expenses Paid Section -->
            @if($hasPaidExpenses)
                <div class="subsection red-section">
                    <div class="subsection-title">Expenses Paid by {{ $data['user']->name }}</div>

                    @php
                        $totalPaidAmount = 0;
                    @endphp

                    @foreach($settlement as $otherId => $settleData)
                        @php
                            $paidByThisMember = [];
                            if (!empty($settleData['expenses'])) {
                                foreach ($settleData['expenses'] as $exp) {
                                    if ($exp['type'] === 'they_owe') {
                                        $paidByThisMember[] = $exp;
                                        $totalPaidAmount += $exp['amount'];
                                    }
                                }
                            }
                        @endphp

                        @if(!empty($paidByThisMember))
                            <div style="margin-bottom: 10px;">
                                <div style="font-weight: bold; color: #374151; margin-bottom: 5px;">For {{ $settleData['user']->name }}:</div>
                                @foreach($paidByThisMember as $exp)
                                    <div class="expense-item">
                                        <span class="expense-title">• {{ $exp['title'] }}</span>
                                        <span class="expense-amount red-text">${{ number_format($exp['amount'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    @if($totalPaidAmount > 0)
                        <div class="subtotal-row">
                            <span class="subtotal-label">Subtotal (Paid by {{ $data['user']->name }}):</span>
                            <span class="subtotal-amount red-text">${{ number_format($totalPaidAmount, 2) }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Expenses Participated Section -->
            @if($hasParticipatedExpenses)
                <div class="subsection green-section">
                    <div class="subsection-title">Expenses for {{ $data['user']->name }} (Paid by Others)</div>

                    @php
                        $totalParticipatedAmount = 0;
                    @endphp

                    @foreach($settlement as $otherId => $settleData)
                        @php
                            $participatedInExpenses = [];
                            if (!empty($settleData['expenses'])) {
                                foreach ($settleData['expenses'] as $exp) {
                                    if ($exp['type'] === 'you_owe') {
                                        $participatedInExpenses[] = $exp;
                                        $totalParticipatedAmount += $exp['amount'];
                                    }
                                }
                            }
                        @endphp

                        @if(!empty($participatedInExpenses))
                            <div style="margin-bottom: 10px;">
                                <div style="font-weight: bold; color: #374151; margin-bottom: 5px;">Paid by {{ $settleData['user']->name }}:</div>
                                @foreach($participatedInExpenses as $exp)
                                    <div class="expense-item">
                                        <span class="expense-title">• {{ $exp['title'] }}</span>
                                        <span class="expense-amount green-text">${{ number_format($exp['amount'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    @if($totalParticipatedAmount > 0)
                        <div class="subtotal-row">
                            <span class="subtotal-label">Subtotal (Others Paid for {{ $data['user']->name }}):</span>
                            <span class="subtotal-amount green-text">${{ number_format($totalParticipatedAmount, 2) }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Advances Section -->
            @php
                $advances = \App\Models\Advance::where('group_id', $group->id)
                    ->with(['senders', 'sentTo'])
                    ->get();

                $advancesReceived = [];
                $totalAdvanceReceived = 0;

                foreach ($advances as $advance) {
                    if ($advance->sent_to_user_id === $data['user']->id) {
                        $senderNames = $advance->senders->pluck('name')->implode(', ');
                        // Calculate total amount for this advance (amount_per_person × number of senders)
                        $totalAdvanceAmount = $advance->amount_per_person * $advance->senders->count();
                        $advancesReceived[] = [
                            'senders' => $senderNames,
                            'amount' => $totalAdvanceAmount,
                        ];
                        $totalAdvanceReceived += $totalAdvanceAmount;
                    }
                }
            @endphp

            @if(!empty($advancesReceived))
                <div class="subsection blue-section">
                    <div class="subsection-title">Advances Received</div>

                    @foreach($advancesReceived as $advance)
                        <div class="expense-item">
                            <span class="expense-title">• Advance from {{ $advance['senders'] }}</span>
                            <span class="expense-amount adjustment-label">-${{ number_format($advance['amount'], 2) }}</span>
                        </div>
                    @endforeach

                    @if($totalAdvanceReceived > 0)
                        <div class="subtotal-row">
                            <span class="subtotal-label">Subtotal (Advances):</span>
                            <span class="subtotal-amount adjustment-label">-${{ number_format($totalAdvanceReceived, 2) }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Payments Received Section -->
            @if($data['receivedPayments']->count() > 0)
                <div class="subsection blue-section">
                    <div class="subsection-title">Payments Received</div>

                    @php
                        $totalPaymentsReceived = 0;
                    @endphp

                    @foreach($data['receivedPayments'] as $payment)
                        @php
                            $totalPaymentsReceived += $payment->amount;
                        @endphp
                        <div class="expense-item">
                            <span class="expense-title">• From {{ $payment->fromUser->name }} on {{ $payment->received_date->format('M d, Y') }}</span>
                            <span class="expense-amount adjustment-label">-${{ number_format($payment->amount, 2) }}</span>
                        </div>
                    @endforeach

                    @if($totalPaymentsReceived > 0)
                        <div class="subtotal-row">
                            <span class="subtotal-label">Subtotal (Payments Received):</span>
                            <span class="subtotal-amount adjustment-label">-${{ number_format($totalPaymentsReceived, 2) }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Summary Section -->
            <div class="summary-box">
                <div style="text-align: center; font-size: 12px; color: #666; margin-bottom: 5px;">Total Amount Owed</div>
                <div class="summary-amount">
                    @if($data['totalOwed'] > 0)
                        <span class="red-text">${{ number_format($data['totalOwed'], 2) }}</span>
                    @else
                        <span class="green-text">$0.00</span>
                    @endif
                </div>

                @if($data['totalOwed'] > 0 && !empty($data['detailedOwings']))
                    <div class="owed-details">
                        <div style="font-weight: bold; margin-bottom: 5px; color: #0284C7;">Breakdown of Owed Amounts:</div>
                        @foreach($data['detailedOwings'] as $owed)
                            <div class="owed-item">
                                <span>{{ $owed['name'] }}:</span>
                                <span style="font-weight: bold; color: #DC2626;">${{ number_format($owed['amount'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif($data['totalOwed'] == 0)
                    <div class="owed-details" style="text-align: center; color: #059669; font-weight: bold;">
                        ✓ Fully Settled
                    </div>
                @endif
            </div>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <div class="footer">
        <p>This report was automatically generated on {{ $exportDate }} from ExpenseSettle</p>
        <p>{{ $group->name }} - Settlement Report for All Members</p>
    </div>
</body>
</html>
