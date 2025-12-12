<!-- Donut Chart Component -->
<div class="relative">
    <canvas id="{{ $id ?? 'donut-chart' }}" width="200" height="200"></canvas>
    @if(isset($centerText))
        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
            <p class="text-3xl font-black text-gray-900">{{ $centerValue ?? '0' }}</p>
            <p class="text-xs font-semibold text-gray-600">{{ $centerLabel ?? 'Total' }}</p>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script nonce="{{ request()->attributes->get(\'nonce\', \'\') }}">
(function() {
    const ctx = document.getElementById('{{ $id ?? 'donut-chart' }}').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($labels ?? ['You Owe', 'They Owe You', 'Settled']) !!},
            datasets: [{
                data: {!! json_encode($data ?? [0, 0, 0]) !!},
                backgroundColor: {!! json_encode($colors ?? ['#EF4444', '#10B981', '#3B82F6']) !!},
                borderWidth: 0,
                borderRadius: 8,
                spacing: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '70%',
            plugins: {
                legend: {
                    display: {{ $showLegend ?? 'true' }},
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12,
                            weight: 'bold'
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
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
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            return label + ': ₹' + value.toLocaleString();
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    });
})();
</script>
