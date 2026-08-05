// AmaderPay Dynamic Frontend & Interactive Experience Engine

document.addEventListener('DOMContentLoaded', () => {
    // ─── 1. Copy to Clipboard ──────────────────────────────────────────────
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.copyTarget;
            const targetEl = targetId ? document.getElementById(targetId) : btn.parentElement.querySelector('input, textarea, span, code');
            
            if (targetEl) {
                const text = targetEl.value !== undefined ? targetEl.value : targetEl.innerText;
                navigator.clipboard.writeText(text).then(() => {
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-check" style="color:#34d399;"></i> Copied!';
                    showToast('ক্লিপবোর্ডে কপি করা হয়েছে!', 'success');
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                    }, 2000);
                }).catch(() => {
                    showToast('কপি করতে ব্যর্থ হয়েছে!', 'error');
                });
            }
        });
    });

    // ─── 2. Password & Key Masking Toggle ───────────────────────────────────
    document.querySelectorAll('.toggle-mask-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input = document.getElementById(targetId);
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.innerHTML = '<i class="fa-regular fa-eye-slash"></i> Hide';
                } else {
                    input.type = 'password';
                    btn.innerHTML = '<i class="fa-regular fa-eye"></i> Show';
                }
            }
        });
    });

    // ─── 3. Tabs Controller ────────────────────────────────────────────────
    document.querySelectorAll('.tabs-nav').forEach(tabNav => {
        const buttons = tabNav.querySelectorAll('.tab-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.dataset.tab;
                const container = tabNav.closest('.tabs-container') || document;
                
                tabNav.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                container.querySelectorAll('.tab-content').forEach(content => {
                    if (content.id === targetTab) {
                        content.style.display = 'block';
                        content.classList.add('fade-in');
                    } else {
                        content.style.display = 'none';
                    }
                });
            });
        });
    });
});

// ─── Toast Notification Engine ──────────────────────────────────────────────
function showToast(message, type = 'info') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = 'position:fixed;bottom:28px;right:28px;z-index:9999;display:flex;flex-direction:column;gap:12px;';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' 
                  : type === 'error' ? 'linear-gradient(135deg, #ef4444, #dc2626)' 
                  : 'linear-gradient(135deg, #8b5cf6, #6d28d9)';
                  
    const icon = type === 'success' ? 'fa-circle-check' : type === 'error' ? 'fa-circle-xmark' : 'fa-circle-info';

    toast.style.cssText = `background:${bgColor};color:white;padding:14px 24px;border-radius:12px;font-weight:700;font-size:0.95rem;box-shadow:0 12px 35px rgba(0,0,0,0.4);display:flex;align-items:center;gap:10px;transition:all 0.35s cubic-bezier(0.4, 0, 0.2, 1);transform:translateY(30px) scale(0.9);opacity:0;border:1px solid rgba(255,255,255,0.2);`;
    toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;
    
    toastContainer.appendChild(toast);
    
    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0) scale(1)';
        toast.style.opacity = '1';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(30px) scale(0.9)';
        setTimeout(() => toast.remove(), 350);
    }, 3800);
}

// ─── Filter Table Rows Helper ───────────────────────────────────────────────
function filterTable(inputId, tableId) {
    const query = document.getElementById(inputId).value.toLowerCase();
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}
