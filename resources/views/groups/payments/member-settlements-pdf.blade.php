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

            <!-- Cleaner Single-Column Layout with Table Format -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px;">
                <thead>
                    <tr style="background-color: #F3F4F6;">
                        <th style="border: 1px solid #D1D5DB; padding: 8px; text-align: left; font-weight: bold;">Expense Description</th>
                        <th style="border: 1px solid #D1D5DB; padding: 8px; text-align: left; font-weight: bold;">Paid By</th>
                        <th style="border: 1px solid #D1D5DB; padding: 8px; text-align: right; font-weight: bold;">Amount</th>
                        <th style="border: 1px solid #D1D5DB; padding: 8px; text-align: center; font-weight: bold;">Type</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalPaidAmount = 0;
                        $totalParticipatedAmount = 0;
                        $rowCount = 0;
                    @endphp

                    @foreach($settlement as $otherId => $settleData)
                        @if(!empty($settleData['expenses']))
                            @foreach($settleData['expenses'] as $exp)
                                @php
                                    $bgColor = $rowCount % 2 === 0 ? '#FFFFFF' : '#F9FAFB';
                                    $type = $exp['type'];
                                    $isPaid = ($type === 'they_owe');

                                    if ($isPaid) {
                                        $totalPaidAmount += $exp['amount'];
                                        $typeLabel = '👤 They Owe';
                                        $typeColor = '#DC2626';
                                    } else {
                                        $totalParticipatedAmount += $exp['amount'];
                                        $typeLabel = '✓ You Owe';
                                        $typeColor = '#059669';
                                    }
                                    $rowCount++;
                                @endphp
                                <tr style="background-color: {{ $bgColor }};">
                                    <td style="border: 1px solid #E5E7EB; padding: 6px 8px;">{{ $exp['title'] }}</td>
                                    <td style="border: 1px solid #E5E7EB; padding: 6px 8px;">{{ $settleData['user']->name }}</td>
                                    <td style="border: 1px solid #E5E7EB; padding: 6px 8px; text-align: right; font-weight: bold;">${{ number_format($exp['amount'], 2) }}</td>
                                    <td style="border: 1px solid #E5E7EB; padding: 6px 8px; text-align: center; color: {{ $typeColor }}; font-weight: bold;">{{ $typeLabel }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>

            <!-- Summary Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                <!-- Left: What User Paid -->
                @if($totalPaidAmount > 0)
                    <div style="border: 2px solid #FECACA; background-color: #FEE2E2; padding: 10px; border-radius: 4px;">
                        <div style="font-weight: bold; color: #7F1D1D; margin-bottom: 6px; font-size: 11px;">💰 What {{ $data['user']->name }} Paid:</div>
                        <div style="font-size: 14px; font-weight: bold; color: #DC2626;">${{ number_format($totalPaidAmount, 2) }}</div>
                        <div style="font-size: 9px; color: #9CA3AF; margin-top: 4px;">Amount paid for others who owe you</div>
                    </div>
                @endif

                <!-- Right: What User Owes -->
                @if($totalParticipatedAmount > 0)
                    <div style="border: 2px solid #BBF7D0; background-color: #DCFCE7; padding: 10px; border-radius: 4px;">
                        <div style="font-weight: bold; color: #15803D; margin-bottom: 6px; font-size: 11px;">💳 What {{ $data['user']->name }} Owes:</div>
                        <div style="font-size: 14px; font-weight: bold; color: #059669;">${{ number_format($totalParticipatedAmount, 2) }}</div>
                        <div style="font-size: 9px; color: #9CA3AF; margin-top: 4px;">Amount you owe for expenses others paid</div>
                    </div>
                @endif
            </div>

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
                        <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 10px;">
                            <span style="flex: 1;">• Advance from {{ $advance['senders'] }}</span>
                            <span style="text-align: right; font-weight: bold; color: #7C3AED; min-width: 70px; padding-left: 10px;">-${{ number_format($advance['amount'], 2) }}</span>
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
                        <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 10px;">
                            <span style="flex: 1;">• From {{ $payment->fromUser->name }} on {{ $payment->received_date->format('M d, Y') }}</span>
                            <span style="text-align: right; font-weight: bold; color: #7C3AED; min-width: 70px; padding-left: 10px;">-${{ number_format($payment->amount, 2) }}</span>
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
