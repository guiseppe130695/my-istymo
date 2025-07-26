// JS extrait de dpe-panel-simple.php
// ... tout le code JS du <script> du template ... 
// ✅ NOUVEAU : Fonction pour forcer la désactivation du bouton
function forceDisableSendButton() {
    const sendLettersBtn = document.getElementById('send-letters-btn');
    if (sendLettersBtn) {
        sendLettersBtn.disabled = true;
        sendLettersBtn.setAttribute('disabled', 'disabled');
        sendLettersBtn.classList.add('disabled');
        sendLettersBtn.style.background = '#6c757d';
        sendLettersBtn.style.color = '#ffffff';
        sendLettersBtn.style.cursor = 'not-allowed';
        sendLettersBtn.style.opacity = '0.6';
        sendLettersBtn.style.pointerEvents = 'none';
        console.log('🔒 Bouton d\'envoi forcé à désactivé');
    }
} 