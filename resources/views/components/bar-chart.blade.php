<!-- Bar Chart Component -->
<div class="relative">
    <canvas id="{{ $id ?? 'bar-chart' }}" height="{{ $height ?? '100' }}"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script nonce="{{ request()->attributes->get('nonce', '') }}">
(function() {
    const ctx = document.getElementById('{{ $id ?? 'bar-chart' }}').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($labels ?? ['Food', 'Transport', 'Entertainment', 'Bills', 'Other']) !!},
            datasets: [{
                label: '{{ $label ?? 'Amount' }}',
                data: {!! json_encode($data ?? [0, 0, 0, 0, 0]) !!},
                backgroundColor: {!! json_encode($colors ?? ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6']) !!},
                borderRadius: 8,
                borderSkipped: false
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
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    });
})();
</script>
