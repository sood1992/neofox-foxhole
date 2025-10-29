document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.timer-btn');
    const toast = (message, isError = false) => {
        const notice = document.createElement('div');
        notice.textContent = message;
        notice.style.position = 'fixed';
        notice.style.bottom = '32px';
        notice.style.right = '32px';
        notice.style.padding = '14px 18px';
        notice.style.borderRadius = '12px';
        notice.style.fontWeight = '600';
        notice.style.background = isError ? 'rgba(255,107,107,0.9)' : 'rgba(91,192,190,0.9)';
        notice.style.color = '#04101f';
        document.body.appendChild(notice);
        setTimeout(() => notice.remove(), 3500);
    };

    buttons.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const taskId = btn.dataset.taskId;
            const action = btn.classList.contains('stop') ? 'stop' : 'start';
            let note = '';
            if (action === 'stop') {
                note = window.prompt('Capture a quick note about what you wrapped up (optional):', '') || '';
            }
            btn.disabled = true;
            try {
                const response = await fetch('api/time.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, task_id: taskId, note })
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Unable to update timer');
                }
                toast(action === 'start' ? 'Timer started. Stay in flow!' : 'Timer stopped. Great work!');
                setTimeout(() => window.location.reload(), 900);
            } catch (error) {
                toast(error.message, true);
                btn.disabled = false;
            }
        });
    });
});
