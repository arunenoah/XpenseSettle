<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $group->name }} - Timeline</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: white;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 2.5em;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 0.95em;
            color: #6b7280;
        }

        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 40px;
            position: relative;
        }

        .timeline-dot {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
            margin-right: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .timeline-content {
            flex: 1;
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }

        .timeline-date {
            font-size: 0.85em;
            font-weight: 600;
            color: #3b82f6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .timeline-title {
            font-size: 1.2em;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .timeline-description {
            font-size: 0.95em;
            color: #4b5563;
            margin-bottom: 10px;
        }

        .timeline-amount {
            font-size: 1.1em;
            font-weight: 600;
            color: #10b981;
            margin-bottom: 10px;
        }

        .timeline-members {
            font-size: 0.9em;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .member-tag {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 10px;
            border-radius: 20px;
            margin-right: 6px;
            margin-bottom: 6px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .timeline-actor {
            font-size: 0.85em;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            margin-top: 10px;
        }

        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 0.9em;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .group-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            font-size: 0.9em;
        }

        .info-item {
            flex: 1;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .info-value {
            color: #1f2937;
            font-size: 1.1em;
            font-weight: 500;
        }

        @media print {
            body {
                background: white;
            }
            .container {
                padding: 20px;
            }
            .timeline-item {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📋 {{ $group->name }}</h1>
            <p>Group Timeline</p>
        </div>

        <!-- Group Info -->
        <div class="group-info">
            <div class="info-item">
                <div class="info-label">Members</div>
                <div class="info-value">{{ $group->members->count() }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Total Activities</div>
                <div class="info-value">{{ $activities->count() }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Exported</div>
                <div class="info-value">{{ $exportedAt->format('M d, Y') }}</div>
            </div>
        </div>

        <!-- Timeline -->
        @if($activities->count() > 0)
        <div class="timeline">
            @foreach($activities as $activity)
            @php
                $metadata = is_array($activity->metadata) ? $activity->metadata : json_decode($activity->metadata ?? '{}', true);
            @endphp
            <div class="timeline-item">
                <div class="timeline-dot">{{ $activity->icon }}</div>
                <div class="timeline-content">
                    <div class="timeline-title">
                        {{ $activity->icon }}
                        @if($activity->type === 'expense_created')
                            {{ $activity->title }}
                        @elseif($activity->type === 'advance_paid')
                            Advance to {{ $metadata['sent_to_name'] ?? 'Member' }}
                        @else
                            {{ $activity->title }}
                        @endif
                    </div>

                    @if($activity->type === 'expense_created')
                        <div style="margin-top: 8px; font-size: 0.9em; color: #6b7280;">
                            <strong>👤 Paid by:</strong> {{ $metadata['payer_name'] ?? 'Unknown' }}
                            <br>
                            @if(isset($metadata['split_count']))
                            <strong>👥 Split among:</strong> {{ $metadata['split_count'] }} {{ $metadata['split_count'] === 1 ? 'person' : 'people' }}
                            <br>
                            @endif
                            <strong>📅 Date:</strong> {{ $metadata['date'] ?? $activity->created_at->format('M d, Y') }}
                        </div>
                        @if($activity->amount)
                        <div style="margin-top: 12px; font-size: 1.3em; font-weight: 600; color: #3b82f6;">
                            ₹{{ number_format($activity->amount, 0) }}
                        </div>
                        @endif
                        @if(isset($metadata['split_type']))
                        <div style="margin-top: 8px; display: inline-block; background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 20px; font-size: 0.85em; font-weight: 500;">
                            {{ ucfirst(str_replace('_', ' ', $metadata['split_type'])) }} split
                        </div>
                        @endif
                    @elseif($activity->type === 'advance_paid')
                        <div style="margin-top: 8px; font-size: 0.9em; color: #6b7280;">
                            <strong>💸 Paid by:</strong> {{ $metadata['senders'] ?? 'Members' }}
                            <br>
                            <strong>📅 Date:</strong> {{ $metadata['date'] ?? $activity->created_at->format('M d, Y') }}
                        </div>
                        @if($activity->amount)
                        <div style="margin-top: 12px; font-size: 1.3em; font-weight: 600; color: #06b6d4;">
                            ₹{{ number_format($activity->amount, 0) }} per person
                        </div>
                        @endif
                        <div style="margin-top: 8px; display: inline-block; background: #cffafe; color: #0c7792; padding: 4px 10px; border-radius: 20px; font-size: 0.85em; font-weight: 500;">
                            🚀 Advance
                        </div>
                    @endif

                    @if($activity->description)
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 0.9em; color: #666; font-style: italic;">
                        💬 "{{ $activity->description }}"
                    </div>
                    @endif

                    @if($activity->user)
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 0.85em; color: #9ca3af;">
                        by <strong>{{ $activity->user->name }}</strong>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="empty-state">
            <p>No activities recorded yet for this group.</p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>
                Generated on {{ $exportedAt->format('F d, Y \a\t H:i') }}<br>
                ExpenseSettle - Group Settlement Management
            </p>
        </div>
    </div>

    <script nonce="{{ request()->attributes->get('nonce', '') }}">
        // Auto-trigger print when page loads
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
