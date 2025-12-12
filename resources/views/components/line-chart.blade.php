<!-- Line Chart Component -->
<div class="relative">
    <canvas id="{{ $id ?? 'line-chart' }}" height="{{ $height ?? '100' }}"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script nonce="@nonce()">
(function() {
    const ctx = document.getElementById('{{ $id ?? 'line-chart' }}').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($labels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']) !!},
            datasets: [{
                label: '{{ $label ?? 'Spending' }}',
                data: {!! json_encode($data ?? [0, 0, 0, 0, 0, 0]) !!},
                borderColor: '{{ $color ?? '#6366F1' }}',
                backgroundColor: function(context) {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                    gradient.addColorStop(0, '{{ $gradientStart ?? 'rgba(99, 102, 241, 0.3)' }}');
                    gradient.addColorStop(1, '{{ $gradientEnd ?? 'rgba(99, 102, 241, 0)' }}');
                    return gradient;
                },
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: '#FFFFFF',
                pointBorderColor: '{{ $color ?? '#6366F1' }}',
                pointBorderWidth: 3,
                pointHoverBackgroundColor: '{{ $color ?? '#6366F1' }}',
                pointHoverBorderColor: '#FFFFFF',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return '₹' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            weight: '600'
                        },
                        color: '#6B7280',
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            weight: '600'
                        },
                        color: '#6B7280'
                    }
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeInOutQuart'
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
})();
</script>
