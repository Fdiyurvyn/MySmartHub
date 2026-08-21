(() => {
    const form = document.getElementById('ai-form');
    const input = document.getElementById('ai-input');
    const messages = document.getElementById('ai-messages');
    const status = document.getElementById('ai-status');
    const submit = document.getElementById('ai-submit');
    if (!form || !input || !messages || !window.smartHubAi) return;

    const addMessage = (text, role) => {
        const empty = messages.querySelector('.ai-empty');
        if (empty) empty.remove();
        const bubble = document.createElement('div');
        bubble.className = `ai-message ${role}`;
        bubble.textContent = text;
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;
        addMessage(message, 'user');
        input.value = '';
        input.disabled = true;
        submit.disabled = true;
        status.hidden = false;
        try {
            const response = await fetch(window.smartHubAi.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message, csrf_token: window.smartHubAi.csrfToken })
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Permintaan gagal.');
            addMessage(data.reply, 'assistant');
        } catch (error) {
            addMessage(error.message || 'Asisten tidak dapat dihubungi.', 'assistant');
        } finally {
            input.disabled = false;
            submit.disabled = false;
            status.hidden = true;
            input.focus();
        }
    });
})();
