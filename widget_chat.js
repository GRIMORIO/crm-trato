/**
 * widget_chat.js — Cargador dinámico del Widget de Chat en Vivo TIPS CRM
 * Incrustar este archivo al final de cualquier página HTML para mostrar el chat flotante.
 */
(function() {
    // Evitar múltiples cargas del widget
    if (window.TipsChatWidgetLoaded) return;
    window.TipsChatWidgetLoaded = true;

    // Crear estilos de la burbuja y ventana de chat
    const style = document.createElement('style');
    style.innerHTML = `
        /* Contenedor Flotante */
        #tips-chat-widget-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999999;
            font-family: 'Outfit', sans-serif;
        }

        /* Burbuja de Chat */
        #tips-chat-bubble {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #06b6d4, #a855f7);
            border-radius: 50%;
            box-shadow: 0 4px 16px rgba(6, 182, 212, 0.4);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #tips-chat-bubble:hover {
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.6);
        }

        #tips-chat-bubble svg {
            width: 28px;
            height: 28px;
            fill: #fff;
            transition: transform 0.3s ease;
        }

        #tips-chat-bubble.active svg {
            transform: rotate(90deg);
        }

        /* Iframe de la ventana de chat */
        #tips-chat-iframe {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            height: 480px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            background-color: #0c1020;
            display: none;
            opacity: 0;
            transform: translateY(20px) scale(0.95);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: bottom right;
        }

        #tips-chat-iframe.active {
            display: block;
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    `;
    document.head.appendChild(style);

    // Crear estructura HTML del widget en el Body
    const container = document.createElement('div');
    container.id = 'tips-chat-widget-container';
    
    // Obtener la URL base del script actual para cargar el iframe relativo
    const scripts = document.getElementsByTagName('script');
    const currentScript = scripts[scripts.length - 1];
    const scriptSrc = currentScript.src;
    const baseUrl = scriptSrc.substring(0, scriptSrc.lastIndexOf('/'));
    
    container.innerHTML = `
        <iframe id="tips-chat-iframe" src="${baseUrl}/widget_chat.php" frameborder="0"></iframe>
        <div id="tips-chat-bubble">
            <svg id="chat-icon-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    `;
    
    document.body.appendChild(container);

    // Controladores de Evento Click
    const bubble = document.getElementById('tips-chat-bubble');
    const iframe = document.getElementById('tips-chat-iframe');
    const svgIcon = document.getElementById('chat-icon-svg');

    bubble.addEventListener('click', () => {
        const isActive = iframe.classList.contains('active');
        
        if (isActive) {
            iframe.classList.remove('active');
            bubble.classList.remove('active');
            // Cambiar icono a burbuja normal
            svgIcon.innerHTML = `<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>`;
            setTimeout(() => {
                iframe.style.display = 'none';
            }, 300);
        } else {
            iframe.style.display = 'block';
            bubble.classList.add('active');
            // Cambiar icono a "Cerrar" (X)
            svgIcon.innerHTML = `<line x1="18" y1="6" x2="6" y2="18" stroke="#fff" stroke-width="2" stroke-linecap="round"/><line x1="6" y1="6" x2="18" y2="18" stroke="#fff" stroke-width="2" stroke-linecap="round"/>`;
            setTimeout(() => {
                iframe.classList.add('active');
            }, 10);
        }
    });
})();
