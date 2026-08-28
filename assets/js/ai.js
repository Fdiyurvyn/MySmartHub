(() => {
    const form = document.getElementById('ai-form');
    const input = document.getElementById('ai-input');
    const messages = document.getElementById('ai-messages');
    const submit = document.getElementById('ai-submit');
    if (!form || !input || !messages || !window.smartHubAi) return;

    // Custom robust markdown parser
    const formatReply = (text) => {
        if (!text) return '';
        
        // 1. Escape HTML first to prevent XSS
        let html = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // 2. Extract code blocks
        const codeBlocks = [];
        html = html.replace(/```(?:[a-zA-Z0-9]+)?([\s\S]*?)```/g, (match, code) => {
            const index = codeBlocks.length;
            codeBlocks.push(code.trim());
            return `__CODE_BLOCK_${index}__`;
        });

        // 3. Parse inline code
        html = html.replace(/`([^`\n]+)`/g, '<code>$1</code>');

        // 4. Parse bold & italic
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
        html = html.replace(/_(.*?)_/g, '<em>$1</em>');

        // 5. Parse block elements line-by-line (headers, lists)
        const lines = html.split('\n');
        let inUl = false;
        let inOl = false;
        const resultLines = [];

        for (let line of lines) {
            const trimmed = line.trim();
            
            // Bullet list item
            const ulMatch = line.match(/^([\s]*)[-*+]\s+(.+)$/);
            if (ulMatch) {
                if (inOl) {
                    resultLines.push('</ol>');
                    inOl = false;
                }
                if (!inUl) {
                    resultLines.push('<ul class="ai-list">');
                    inUl = true;
                }
                resultLines.push(`<li>${ulMatch[2]}</li>`);
                continue;
            }

            // Numbered list item
            const olMatch = line.match(/^([\s]*)\d+\.\s+(.+)$/);
            if (olMatch) {
                if (inUl) {
                    resultLines.push('</ul>');
                    inUl = false;
                }
                if (!inOl) {
                    resultLines.push('<ol class="ai-list">');
                    inOl = true;
                }
                resultLines.push(`<li>${olMatch[2]}</li>`);
                continue;
            }

            // Headers
            if (trimmed.startsWith('### ')) {
                line = `<h3>${trimmed.substring(4)}</h3>`;
            } else if (trimmed.startsWith('## ')) {
                line = `<h2>${trimmed.substring(3)}</h2>`;
            } else if (trimmed.startsWith('# ')) {
                line = `<h1>${trimmed.substring(2)}</h1>`;
            }

            // Close lists on non-list lines
            if (inUl) {
                resultLines.push('</ul>');
                inUl = false;
            }
            if (inOl) {
                resultLines.push('</ol>');
                inOl = false;
            }

            resultLines.push(line);
        }

        if (inUl) resultLines.push('</ul>');
        if (inOl) resultLines.push('</ol>');

        let formatted = resultLines.join('\n');

        // Restore code blocks
        formatted = formatted.replace(/__CODE_BLOCK_(\d+)__/g, (match, index) => {
            const code = codeBlocks[parseInt(index, 10)];
            return `<pre class="ai-code"><code>${code}</code></pre>`;
        });

        return formatted;
    };

    // Format existing messages on page load
    document.querySelectorAll('.ai-message.assistant').forEach((bubble) => {
        bubble.innerHTML = formatReply(bubble.textContent);
    });

    // Helper to add user message bubble
    const addUserMessage = (text) => {
        const empty = messages.querySelector('.ai-empty');
        if (empty) empty.remove();
        
        const bubble = document.createElement('div');
        bubble.className = 'ai-message user';
        bubble.textContent = text;
        
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
    };

    // Helper to create empty assistant bubble for typing effect
    const createAssistantBubble = () => {
        const bubble = document.createElement('div');
        bubble.className = 'ai-message assistant';
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    };

    // Typing indicator helpers
    const showTypingIndicator = () => {
        const empty = messages.querySelector('.ai-empty');
        if (empty) empty.remove();

        const bubble = document.createElement('div');
        bubble.className = 'ai-message assistant typing-indicator';
        bubble.innerHTML = '<span></span><span></span><span></span>';

        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    };

    const removeTypingIndicator = (bubble) => {
        if (bubble && bubble.parentNode) {
            bubble.remove();
        }
    };

    // Token-based typing effect to support rendering formatting tags seamlessly
    const typeHtml = (html, element, speed = 10) => {
        const tokens = [];
        let i = 0;
        while (i < html.length) {
            if (html[i] === '<') {
                const end = html.indexOf('>', i);
                if (end !== -1) {
                    tokens.push({ type: 'tag', content: html.substring(i, end + 1) });
                    i = end + 1;
                } else {
                    tokens.push({ type: 'text', content: html[i] });
                    i++;
                }
            } else {
                let nextOpen = html.indexOf('<', i);
                if (nextOpen === -1) {
                    tokens.push({ type: 'text', content: html.substring(i) });
                    break;
                } else {
                    tokens.push({ type: 'text', content: html.substring(i, nextOpen) });
                    i = nextOpen;
                }
            }
        }

        let tokenIndex = 0;
        let charIndex = 0;
        element.innerHTML = '';

        return new Promise((resolve) => {
            function typeNext() {
                if (tokenIndex >= tokens.length) {
                    resolve();
                    return;
                }

                const token = tokens[tokenIndex];
                if (token.type === 'tag') {
                    element.innerHTML += token.content;
                    tokenIndex++;
                    typeNext();
                } else {
                    if (charIndex < token.content.length) {
                        element.innerHTML += token.content[charIndex];
                        charIndex++;
                        messages.scrollTop = messages.scrollHeight;
                        setTimeout(typeNext, speed);
                    } else {
                        charIndex = 0;
                        tokenIndex++;
                        typeNext();
                    }
                }
            }
            typeNext();
        });
    };

    // Auto-grow textarea input
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    });

    // Auto-submit quick action prompts
    document.querySelectorAll('[data-prompt]').forEach((button) => {
        button.addEventListener('click', () => {
            const prompt = button.dataset.prompt;
            if (prompt) {
                input.value = prompt;
                input.style.height = 'auto';
                form.requestSubmit();
            }
        });
    });

    // Keyboard submit handler
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    // Form submission flow
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;

        addUserMessage(message);
        input.value = '';
        input.style.height = 'auto'; // Reset input height
        input.disabled = true;
        submit.disabled = true;

        const indicator = showTypingIndicator();

        try {
            const response = await fetch(window.smartHubAi.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message, csrf_token: window.smartHubAi.csrfToken })
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Permintaan gagal.');
            
            removeTypingIndicator(indicator);
            const bubble = createAssistantBubble();
            await typeHtml(formatReply(data.reply), bubble);
        } catch (error) {
            removeTypingIndicator(indicator);
            const bubble = createAssistantBubble();
            await typeHtml(error.message || 'Asisten tidak dapat dihubungi.', bubble);
        } finally {
            input.disabled = false;
            submit.disabled = false;
            input.focus();
        }
    });
})();
