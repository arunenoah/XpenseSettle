<!-- Confetti Animation Component -->
<div id="confetti-container" class="fixed inset-0 pointer-events-none z-50 hidden">
    <canvas id="confetti-canvas" class="w-full h-full"></canvas>
</div>

<script>
function showConfetti() {
    const container = document.getElementById('confetti-container');
    const canvas = document.getElementById('confetti-canvas');
    const ctx = canvas.getContext('2d');
    
    container.classList.remove('hidden');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    
    const confetti = [];
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE'];
    
    // Create confetti pieces
    for (let i = 0; i < 150; i++) {
        confetti.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height - canvas.height,
            r: Math.random() * 6 + 4,
            d: Math.random() * 150 + 10,
            color: colors[Math.floor(Math.random() * colors.length)],
            tilt: Math.random() * 10 - 10,
            tiltAngleIncremental: Math.random() * 0.07 + 0.05,
            tiltAngle: 0
        });
    }
    
    let animationFrame;
    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        confetti.forEach((c, i) => {
            ctx.beginPath();
            ctx.lineWidth = c.r / 2;
            ctx.strokeStyle = c.color;
            ctx.moveTo(c.x + c.tilt + c.r, c.y);
            ctx.lineTo(c.x + c.tilt, c.y + c.tilt + c.r);
            ctx.stroke();
            
            c.tiltAngle += c.tiltAngleIncremental;
            c.y += (Math.cos(c.d) + 3 + c.r / 2) / 2;
            c.tilt = Math.sin(c.tiltAngle - i / 3) * 15;
            
            if (c.y > canvas.height) {
                confetti.splice(i, 1);
            }
        });
        
        if (confetti.length > 0) {
            animationFrame = requestAnimationFrame(draw);
        } else {
            container.classList.add('hidden');
            cancelAnimationFrame(animationFrame);
        }
    }
    
    draw();
}

// Auto-trigger on payment success
document.addEventListener('payment-success', showConfetti);
</script>
