<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Group History - {{ $group->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4F46E5;
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
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #4F46E5;
            color: white;
            padding: 8px 10px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: bold;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #F3F4F6;
            color: #374151;
            font-weight: bold;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #D1D5DB;
            font-size: 10px;
        }
        td {
            padding: 6px;
            border: 1px solid #E5E7EB;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #F9FAFB;
        }
        .amount {
            text-align: right;
            font-weight: bold;
        }
        .amount-positive {
            color: #059669;
        }
        .amount-negative {
            color: #DC2626;
        }
        .members-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 5px;
        }
        .member-badge {
            background-color: #E0E7FF;
            color: #4338CA;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
            display: inline-block;
        }
        .settlement-matrix {
            font-size: 9px;
        }
        .settlement-matrix td {
            text-align: center;
            padding: 4px;
        }
        .owes {
            background-color: #FEE2E2;
            color: #991B1B;
            font-weight: bold;
        }
        .owed {
            background-color: #D1FAE5;
            color: #065F46;
            font-weight: bold;
        }
        .transaction-expense {
            background-color: #FEF3C7;
        }
        .transaction-payment {
            background-color: #DBEAFE;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #D1D5DB;
            text-align: center;
            font-size: 9px;
            color: #6B7280;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $group->name }}</h1>
        <p>Group Payment History Report</p>
        <p>Generated on {{ $exportDate }}</p>
    </div>

    <!-- Group Information -->
    <div class="section">
        <div class="section-title">Group Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Group Name:</div>
                <div class="info-value">{{ $group->name }}</div>
            </div>
            @if($group->description)
            <div class="info-row">
                <div class="info-label">Description:</div>
                <div class="info-value">{{ $group->description }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Currency:</div>
                <div class="info-value">{{ $group->currency ?? 'USD' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Members:</div>
                <div class="info-value">{{ count($overallSettlement) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Expenses:</div>
                <div class="info-value">${{ number_format($totalExpenses, 2) }}</div>
            </div>
        </div>

        <!-- Members List -->
        <div style="margin-top: 10px;">
            <strong>Members:</strong>
            <div class="members-list">
                @foreach($overallSettlement as $gmId => $data)
                    <span class="member-badge">{{ $data['user']->name }}@if($data['is_contact']) ✨@endif</span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Overall Group Settlement -->
    <div class="section">
        <div class="section-title">Overall Group Settlement</div>
        <table class="settlement-matrix">
            <thead>
                <tr>
                    <th>Person</th>
                    @foreach($overallSettlement as $toGmId => $toData)
                        <th>{{ substr($toData['user']->name, 0, 10) }}@if($toData['is_contact'])✨@endif</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($overallSettlement as $fromGmId => $fromData)
                    <tr>
                        <td style="font-weight: bold; text-align: left;">{{ $fromData['user']->name }}@if($fromData['is_contact'])✨@endif</td>
                        @foreach($overallSettlement as $toGmId => $toData)
                            <td>
                                @if($fromGmId === $toGmId)
                                    —
                                @else
                                    @php
                                        $amount = 0;
                                        $class = '';

                                        // Check if fromMember owes toMember
                                        if (isset($fromData['owes'][$toGmId])) {
                                            $amount = $fromData['owes'][$toGmId]['amount'];
                                            $class = 'owes';
                                        }
                                        // Check if toMember owes fromMember
                                        elseif (isset($toData['owes'][$fromGmId])) {
                                            $amount = $toData['owes'][$fromGmId]['amount'];
                                            $class = 'owed';
                                        }
                                    @endphp

                                    @if($amount > 0)
                                        <span class="{{ $class }}">{{ number_format($amount, 2) }}</span>
                                    @else
                                        —
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="font-size: 9px; color: #6B7280; margin-top: 5px;">
            <span style="background-color: #FEE2E2; padding: 2px 6px; border-radius: 3px;">Red</span> = Row person owes column person &nbsp;|&nbsp;
            <span style="background-color: #D1FAE5; padding: 2px 6px; border-radius: 3px;">Green</span> = Column person owes row person
        </p>
    </div>

    <!-- Page break before transactions if needed -->
    @if(count($transactionHistory) > 15)
        <div class="page-break"></div>
    @endif

    <!-- All Group Transactions -->
    <div class="section">
        <div class="section-title">All Group Transactions ({{ count($transactionHistory) }} total)</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 18%;">From</th>
                    <th style="width: 18%;">To</th>
                    <th style="width: 30%;">Description</th>
                    <th style="width: 12%;" class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactionHistory as $transaction)
                    @php
                        $isExpense = $transaction['type'] === 'expense';
                        $rowClass = $isExpense ? 'transaction-expense' : 'transaction-payment';
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>{{ $transaction['timestamp']->format('M d, Y') }}</td>
                        <td>{{ ucfirst($transaction['type']) }}</td>
                        <td>{{ $transaction['payer']->name }}</td>
                        <td>
                            @if($isExpense)
                                {{ $transaction['participants_count'] }} member(s)
                            @else
                                {{ $transaction['recipient']->name }}
                            @endif
                        </td>
                        <td>
                            @if($isExpense)
                                {{ $transaction['title'] }}
                            @else
                                Payment for {{ $transaction['title'] }}
                            @endif
                        </td>
                        <td class="amount {{ $isExpense ? 'amount-negative' : 'amount-positive' }}">
                            ${{ number_format($transaction['amount'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This report was generated automatically from the Expense Settlement System.</p>
        <p>Page generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>
