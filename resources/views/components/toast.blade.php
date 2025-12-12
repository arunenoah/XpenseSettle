<!-- Toast Notification Component -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<script nonce="@nonce()">
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    const colors = {
        success: 'from-green-500 to-emerald-500',
        error: 'from-red-500 to-pink-500',
        warning: 'from-yellow-500 to-orange-500',
        info: 'from-blue-500 to-cyan-500'
    };
    
    toast.className = `flex items-center gap-3 px-6 py-4 bg-gradient-to-r ${colors[type]} text-white rounded-xl shadow-2xl transform transition-all duration-300 translate-x-full`;
    toast.innerHTML = `
        <span class="text-2xl">${icons[type]}</span>
        <span class="font-bold">${message}</span>
    `;
    
    container.appendChild(toast);
    
    // Slide in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 10);
    
    // Slide out and remove
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Make it globally available
window.showToast = showToast;

// Listen for Laravel flash messages
@if(session('success'))
    showToast('{{ session('success') }}', 'success');
@endif

@if(session('error'))
    showToast('{{ session('error') }}', 'error');
@endif

@if(session('warning'))
    showToast('{{ session('warning') }}', 'warning');
@endif

@if(session('info'))
    showToast('{{ session('info') }}', 'info');
@endif
</script>
