// AmaderPay Frontend Interactivity Script

document.addEventListener('DOMContentLoaded', () => {
    // Copy code button handler
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.copyTarget;
            const targetEl = targetId ? document.getElementById(targetId) : btn.parentElement.nextElementSibling;
            
            if (targetEl) {
                const text = targetEl.value || targetEl.innerText;
                navigator.clipboard.writeText(text).then(() => {
                    const originalText = btn.innerText;
                    btn.innerText = '✓ Copied!';
                    btn.style.color = '#34d399';
                    setTimeout(() => {
                        btn.innerText = originalText;
                        btn.style.color = '';
                    }, 2000);
                });
            }
        });
    });

    // Tab switcher handler
    document.querySelectorAll('.tabs-nav').forEach(tabNav => {
        const buttons = tabNav.querySelectorAll('.tab-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.dataset.tab;
                const container = tabNav.closest('.tabs-container') || document;
                
                // Toggle active buttons
                tabNav.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Toggle active tab content
                container.querySelectorAll('.tab-content').forEach(content => {
                    if (content.id === targetTab) {
                        content.style.display = 'block';
                    } else {
                        content.style.display = 'none';
                    }
                });
            });
        });
    });
});

// Toast notification helper
function showToast(message, type = 'info') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#8b5cf6';
    toast.style.cssText = `background:${bgColor};color:white;padding:12px 20px;border-radius:8px;font-weight:600;font-size:0.9rem;box-shadow:0 10px 25px rgba(0,0,0,0.3);transition:all 0.3s ease;transform:translateY(20px);opacity:0;`;
    toast.innerText = message;
    
    toastContainer.appendChild(toast);
    
    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
