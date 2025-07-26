/**
 * Script de debug pour le système DPE
 * À supprimer après diagnostic
 */

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier les éléments essentiels
    const sendLettersBtn = document.getElementById('send-letters-btn');
    const selectedCountSpan = document.getElementById('selected-count');
    
    // Vérifier les scripts chargés
    
    // Vérifier les checkboxes
    const checkboxes = document.querySelectorAll('.send-letter-checkbox');
    
    // Attacher des événements de debug aux checkboxes
    checkboxes.forEach((checkbox, index) => {
        checkbox.addEventListener('change', function(e) {
            // Vérifier le système de sélection
            if (typeof window.dpeLettre !== 'undefined') {
                window.dpeLettre.getAll();
                window.dpeLettre.getCount();
            }
        });
    });
    
    // Fonction de test pour forcer la mise à jour
    window.testDPESelection = function() {
        if (typeof window.dpeLettre !== 'undefined') {
            window.dpeLettre.getAll();
            window.dpeLettre.getCount();
            window.dpeLettre.updateUI();
        }
    };
    
    // Fonction de test pour ajouter une sélection
    window.testAddDPESelection = function(numero_dpe) {
        if (typeof window.dpeLettre !== 'undefined') {
            window.dpeLettre.add(numero_dpe);
            window.dpeLettre.getAll();
        }
    };
    
    // Fonction de test pour effacer toutes les sélections
    window.testClearDPESelections = function() {
        if (typeof window.dpeLettre !== 'undefined') {
            window.dpeLettre.clear();
            window.dpeLettre.getAll();
        }
    };
    
    // Vérifier périodiquement l'état du système
    setInterval(function() {
        if (typeof window.dpeLettre !== 'undefined') {
            const count = window.dpeLettre.getCount();
            const selectedCountSpan = document.getElementById('selected-count');
            if (selectedCountSpan && selectedCountSpan.textContent !== count.toString()) {
                // Incohérence détectée
            }
        }
    }, 2000);
}); 