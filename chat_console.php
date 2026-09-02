<?php
/**
 * chat_console.php — Consola de Control de Chats en Vivo del Asesor (CRM)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';
?>

<div class="card-section" style="padding:0; overflow:hidden; border-radius:12px; height: calc(100vh - 150px); display:flex; flex-direction:column;">
    
    <div style="display:grid; grid-template-columns: 320px 1fr; height: 100%; flex-grow:1;">
        
        <!-- COLUMNA IZQUIERDA: LISTADO DE CHATS -->
        <div style="border-right: 1px solid var(--border-color); background: rgba(12,16,32,0.4); display:flex; flex-direction:column;">
            <div style="padding:1.25rem; border-bottom:1px solid var(--border-color); font-weight:600; color:#fff; display:flex; justify-content:space-between; align-items:center;">
                <span>Chats de Clientes</span>
                <span class="badge badge-open" id="chat-count">0</span>
            </div>
            
            <div id="chats-list" style="overflow-y:auto; flex-grow:1; display:flex; flex-direction:column;">
                <!-- Cargado dinámicamente por JS -->
                <p style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.85rem;">Buscando chats activos...</p>
            </div>
        </div>
        
        <!-- COLUMNA DERECHA: VENTANA DE CONVERSACIÓN -->
        <div style="display:flex; flex-direction:column; background: rgba(7,10,19,0.2);">
            
            <!-- CABECERA DEL CHAT ACTIVO -->
            <div style="padding:1.25rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 id="active-chat-name" style="font-size:1rem; color:#fff; font-weight:600;">Selecciona un chat</h3>
                    <span id="active-chat-email" style="font-size:0.8rem; color:var(--text-muted);">para ver la conversación</span>
                </div>
                <span class="badge" id="active-chat-status" style="display:none;">Activo</span>
            </div>
            
            <!-- CUERPO DE MENSAJES -->
            <div id="chat-messages-container" style="flex-grow:1; overflow-y:auto; padding:1.5rem; display:flex; flex-direction:column; gap:1rem;">
                <div style="display:flex; align-items:center; justify-content:center; height:100%; color:var(--text-muted); font-size:0.9rem;">
                    <div style="text-align:center;">
                        <i data-lucide="message-square" style="width:40px; height:40px; display:block; margin:0 auto 1rem auto; opacity:0.15;"></i>
                        Ninguna conversación activa seleccionada.
                    </div>
                </div>
            </div>
            
            <!-- ENTRADA DE TEXTO -->
            <div style="padding:1.25rem; border-top:1px solid var(--border-color); background:rgba(7,10,19,0.4); display:flex; gap:1rem; align-items:center;">
                <input type="text" id="chat-input" placeholder="Escribe un mensaje de respuesta..." disabled style="flex-grow:1; padding:0.75rem 1rem; border-radius:8px;">
                <button class="btn-submit" id="btn-send-chat" disabled style="width:auto; padding:0.75rem 1.5rem;" onclick="sendAgentMessage()">
                    Enviar
                </button>
            </div>
            
        </div>
        
    </div>
    
</div>

<script>
    let activeChatId = null;
    let chatsPollInterval = null;
    let messagesPollInterval = null;
    
    // Iniciar polleos de chats al cargar la página
    document.addEventListener("DOMContentLoaded", () => {
        loadChatsList();
        chatsPollInterval = setInterval(loadChatsList, 3000);
    });

    // Cargar el listado de chats activos del backend
    function loadChatsList() {
        fetch('api_chat.php?action=admin_get_chats')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const listContainer = document.getElementById('chats-list');
                const chatCount = document.getElementById('chat-count');
                chatCount.innerText = data.chats.length;
                
                if (data.chats.length === 0) {
                    listContainer.innerHTML = `<p style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.85rem;">No hay chats registrados.</p>`;
                    return;
                }
                
                let html = '';
                data.chats.forEach(chat => {
                    const name = chat.lead_name || 'Prospecto Web #' + chat.id;
                    const email = chat.lead_email || 'Sin Correo';
                    const activeClass = (chat.id == activeChatId) ? 'background: rgba(6, 182, 212, 0.08); border-left: 3px solid var(--color-primary);' : '';
                    
                    html += `
                        <div onclick="selectChat(${chat.id}, '${name.replace(/'/g, "\\'")}', '${email}')" 
                             style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); cursor:pointer; transition: var(--transition); ${activeClass}"
                             class="chat-list-item">
                            <div class="flex-between">
                                <span style="font-weight:600; color:#fff; font-size:0.9rem;">${name}</span>
                                <span style="font-size:0.7rem; color:var(--text-dark);">${chat.created_at.substring(11, 16)}</span>
                            </div>
                            <p style="font-size:0.75rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:0.25rem;">
                                ${chat.last_message || 'Iniciando conversación...'}
                            </p>
                        </div>
                    `;
                });
                
                listContainer.innerHTML = html;
            }
        });
    }

    // Seleccionar un chat para chatear
    function selectChat(id, name, email) {
        activeChatId = id;
        
        document.getElementById('active-chat-name').innerText = name;
        document.getElementById('active-chat-email').innerText = email;
        document.getElementById('active-chat-status').style.display = 'inline-block';
        
        const input = document.getElementById('chat-input');
        const btn = document.getElementById('btn-send-chat');
        input.removeAttribute('disabled');
        btn.removeAttribute('disabled');
        
        // Cargar mensajes inmediatamente
        loadChatMessages();
        
        // Resetear y arrancar polleo de mensajes
        if (messagesPollInterval) clearInterval(messagesPollInterval);
        messagesPollInterval = setInterval(loadChatMessages, 2000);
        
        // Re-dibujar listado para aplicar clase activo
        loadChatsList();
    }

    // Cargar mensajes del chat activo
    function loadChatMessages() {
        if (!activeChatId) return;
        
        fetch(`api_chat.php?action=admin_get_messages&chat_id=${activeChatId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('chat-messages-container');
                let html = '';
                
                data.messages.forEach(msg => {
                    let bubbleStyle = '';
                    let alignStyle = '';
                    let senderLabel = '';
                    
                    if (msg.sender === 'Lead') {
                        alignStyle = 'align-self: flex-start;';
                        bubbleStyle = 'background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main);';
                        senderLabel = 'Prospecto';
                    } else if (msg.sender === 'Agent') {
                        alignStyle = 'align-self: flex-end;';
                        bubbleStyle = 'background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(168,85,247,0.2)); border: 1px solid var(--color-primary); color: #fff;';
                        senderLabel = 'Tú (Asesor)';
                    } else { // AI
                        alignStyle = 'align-self: flex-start;';
                        bubbleStyle = 'background-color: rgba(168,85,247,0.1); border: 1px solid rgba(168,85,247,0.3); color: #e9d5ff;';
                        senderLabel = 'Asistente IA TIPS';
                    }
                    
                    html += `
                        <div style="max-width: 70%; ${alignStyle} display:flex; flex-direction:column; gap:0.25rem;">
                            <span style="font-size:0.65rem; color:var(--text-dark);">${senderLabel} • ${msg.created_at.substring(11, 16)}</span>
                            <div style="padding:0.75rem 1rem; border-radius:12px; font-size:0.875rem; line-height:1.4; ${bubbleStyle}">
                                ${msg.message.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                });
                
                // Guardar posición del scroll antes de actualizar
                const isScrolledToBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 50;
                
                container.innerHTML = html;
                
                // Auto scroll al final si ya estaba abajo
                if (isScrolledToBottom) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        });
    }

    // Enviar mensaje del agente
    function sendAgentMessage() {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        if (!message || !activeChatId) return;
        
        input.value = '';
        
        fetch('api_chat.php?action=admin_send_message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                chat_id: activeChatId,
                message: message
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadChatMessages();
            } else {
                alert("Error al enviar mensaje: " + data.error);
            }
        });
    }

    // Permitir enviar con Enter
    document.getElementById('chat-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendAgentMessage();
        }
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
