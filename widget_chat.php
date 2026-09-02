<?php
/**
 * widget_chat.php — Ventana interna del Widget de Chat (se incrusta vía Iframe)
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat de Soporte</title>
    <!-- Google Font Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-body: #0c1020;
            --bg-chat: rgba(18, 24, 48, 0.95);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --color-primary: #06b6d4;
            --color-secondary: #a855f7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Cabecera del Chat */
        .chat-header {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            padding: 1rem;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
        }

        .chat-avatar {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .chat-header h3 {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .chat-header p {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Lista de Mensajes */
        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .message-bubble {
            max-width: 80%;
            padding: 0.6rem 0.85rem;
            border-radius: 12px;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .message-bubble.lead {
            align-self: flex-end;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: #fff;
        }

        .message-bubble.agent {
            align-self: flex-start;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .message-bubble.ai {
            align-self: flex-start;
            background-color: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.2);
            color: #e9d5ff;
        }

        .message-meta {
            font-size: 0.6rem;
            color: var(--text-muted);
            margin-top: 0.15rem;
            display: block;
        }

        /* Formulario de registro (Pre-chat) */
        .pre-chat-form {
            padding: 1rem;
            background: var(--bg-chat);
            border-top: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 50;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.3);
            border-radius: 12px 12px 0 0;
        }

        .pre-chat-form h4 {
            font-size: 0.85rem;
            color: #fff;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            background-color: rgba(7, 10, 19, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 0.8rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .btn-start {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
        }

        /* Entrada de Mensajes */
        .chat-input-area {
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 0.5rem;
            background-color: rgba(7, 10, 19, 0.4);
        }

        .chat-input-area input {
            flex-grow: 1;
            background-color: rgba(7, 10, 19, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
        }

        .chat-input-area input:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .btn-send {
            background: none;
            border: none;
            color: var(--color-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 0.5rem;
        }

        .btn-send:hover {
            color: var(--color-secondary);
        }
    </style>
</head>
<body>

    <!-- Cabecera -->
    <div class="chat-header">
        <div class="chat-avatar">💬</div>
        <div>
            <h3>Soporte B2B TIPS S.A.</h3>
            <p>En línea • Asistente Virtual IA</p>
        </div>
    </div>

    <!-- Mensajes -->
    <div class="chat-messages" id="messages-container">
        <!-- Cargado dinámicamente -->
    </div>

    <!-- Formulario Pre-chat para capturar datos de contacto -->
    <div class="pre-chat-form" id="pre-chat-form" style="display:none;">
        <h4>Antes de chatear, compártenos tus datos de contacto:</h4>
        <input type="text" id="lead-name" class="form-input" placeholder="Nombre completo *" required>
        <input type="email" id="lead-email" class="form-input" placeholder="Correo electrónico *" required>
        <button class="btn-start" onclick="saveLeadDetails()">Comenzar Chat</button>
    </div>

    <!-- Entrada de Texto -->
    <div class="chat-input-area">
        <input type="text" id="widget-input" placeholder="Escribe un mensaje..." onkeypress="handleKeyPress(event)">
        <button class="btn-send" onclick="sendWidgetMessage()">
            <i data-lucide="send" style="width:18px; height:18px;"></i>
        </button>
    </div>

    <script>
        lucide.createIcons();
        
        let sessionKey = localStorage.getItem('tips_chat_session') || '';
        let leadName = '';
        let leadEmail = '';
        let isLeadInfoCollected = false;

        // Iniciar chat al cargar
        document.addEventListener("DOMContentLoaded", () => {
            initChatSession();
            setInterval(fetchMessages, 2500); // Polling cada 2.5s
        });

        // Registrar sesión
        function initChatSession() {
            fetch('api_chat.php?action=init_session', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_key: sessionKey })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sessionKey = data.session_key;
                    localStorage.setItem('tips_chat_session', sessionKey);
                    
                    leadName = data.lead_name;
                    leadEmail = data.lead_email;
                    
                    if (leadName && leadEmail) {
                        isLeadInfoCollected = true;
                    }
                    
                    fetchMessages();
                }
            });
        }

        // Obtener mensajes
        function fetchMessages() {
            if (!sessionKey) return;
            
            fetch(`api_chat.php?action=get_messages&session_key=${sessionKey}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('messages-container');
                    let html = '';
                    
                    data.messages.forEach(msg => {
                        let senderClass = 'ai';
                        if (msg.sender === 'Lead') senderClass = 'lead';
                        else if (msg.sender === 'Agent') senderClass = 'agent';
                        
                        html += `
                            <div class="message-bubble ${senderClass}">
                                ${msg.message.replace(/\n/g, '<br>')}
                                <span class="message-meta">${msg.created_at.substring(11, 16)}</span>
                            </div>
                        `;
                    });
                    
                    const isScrolled = container.scrollHeight - container.clientHeight <= container.scrollTop + 50;
                    container.innerHTML = html;
                    
                    if (isScrolled) {
                        container.scrollTop = container.scrollHeight;
                    }
                }
            });
        }

        // Enviar mensaje
        function sendWidgetMessage() {
            const input = document.getElementById('widget-input');
            const message = input.value.trim();
            if (!message || !sessionKey) return;

            // Si es el segundo mensaje y no tenemos sus datos, forzamos registro
            if (!isLeadInfoCollected) {
                document.getElementById('pre-chat-form').style.display = 'flex';
                return;
            }

            input.value = '';

            // Agregar mensaje localmente de inmediato para mejorar la respuesta visual
            const container = document.getElementById('messages-container');
            container.innerHTML += `
                <div class="message-bubble lead">
                    ${message}
                    <span class="message-meta">Ahora</span>
                </div>
            `;
            container.scrollTop = container.scrollHeight;

            fetch('api_chat.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_key: sessionKey,
                    message: message,
                    name: leadName,
                    email: leadEmail
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    fetchMessages();
                }
            });
        }

        // Guardar detalles del lead en el formulario flotante
        function saveLeadDetails() {
            const nameInput = document.getElementById('lead-name');
            const emailInput = document.getElementById('lead-email');
            
            leadName = nameInput.value.trim();
            leadEmail = emailInput.value.trim();
            
            if (!leadName || !leadEmail) {
                alert("Por favor completa los campos.");
                return;
            }
            
            isLeadInfoCollected = true;
            document.getElementById('pre-chat-form').style.display = 'none';
            
            // Re-enviar mensaje en cola
            sendWidgetMessage();
        }

        function handleKeyPress(e) {
            if (e.key === 'Enter') {
                sendWidgetMessage();
            }
        }
    </script>
</body>
</html>
