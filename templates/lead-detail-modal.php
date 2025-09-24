<?php
/**
 * Template pour le modal de détails du lead
 * 
 * Ce template gère l'affichage du modal avec les détails complets d'un lead
 * incluant les informations SCI/DPE, notes, actions et historique
 */

// Sécurité : vérifier que ce fichier est appelé depuis WordPress
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Modal d'affichage des détails d'un lead - Design Moderne -->
<div id="lead-detail-modal" class="my-istymo-modal my-istymo-hidden">
    <div class="my-istymo-modal-overlay"></div>
    <div class="my-istymo-modal-content my-istymo-lead-detail-modal">
        <!-- En-tête du modal -->
        <div class="my-istymo-modal-header">
            <div class="my-istymo-modal-header-left">
                <div class="my-istymo-lead-icon">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                <div class="my-istymo-lead-header-info">
                    <h3 id="lead-modal-title">Détails du Lead</h3>
                    <p class="my-istymo-lead-subtitle">Informations complètes et actions</p>
                    <p id="lead-creation-date" class="my-istymo-creation-date-header">Créé le --</p>
                </div>
            </div>
            <div class="my-istymo-modal-header-actions">
                <button type="button" id="save-lead-header-btn" class="my-istymo-btn my-istymo-btn-primary my-istymo-btn-header" onclick="saveLeadChangesFromHeader()">
                    <i class="fas fa-save"></i> Sauvegarder
                </button>
                <button type="button" class="my-istymo-modal-close" onclick="closeLeadDetailModal()">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="my-istymo-modal-body">
            <div id="lead-detail-content">
                <!-- État de chargement -->
                <div class="my-istymo-loading-state">
                    <div class="my-istymo-loading-spinner"></div>
                    <p>🔄 Chargement des détails...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ PHASE 3 : Modals pour les actions et workflow -->
<div id="add-action-modal" class="my-istymo-modal my-istymo-hidden">
    <div class="my-istymo-modal-overlay"></div>
    <div class="my-istymo-modal-content">
        <h3>📝 Ajouter une Action</h3>
        <form id="add-action-form">
            <input type="hidden" name="lead_id" id="action-lead-id">
            <div class="action-form-group">
                <label for="action-type">Type d'action :</label>
                <select name="action_type" id="action-type" required>
                    <option value="">Sélectionner un type</option>
                    <option value="appel">📞 Appel téléphonique</option>
                    <option value="email">📧 Email</option>
                    <option value="sms">💬 SMS</option>
                    <option value="rdv">📅 Rendez-vous</option>
                    <option value="note">📝 Note</option>
                </select>
            </div>
            <div class="action-form-group">
                <label for="action-description">Description :</label>
                <textarea name="description" id="action-description" rows="4" placeholder="Décrivez l'action..." required></textarea>
            </div>
            <div class="action-form-group">
                <label for="action-result">Résultat :</label>
                <select name="result" id="action-result">
                    <option value="en_attente">⏳ En attente</option>
                    <option value="reussi">✅ Réussi</option>
                    <option value="echec">❌ Échec</option>
                    <option value="reporte">📅 Reporté</option>
                </select>
            </div>
            <div class="action-form-group">
                <label for="action-scheduled-date">Date programmée (optionnel) :</label>
                <input type="datetime-local" name="scheduled_date" id="action-scheduled-date">
            </div>
            <div class="my-istymo-modal-actions">
                <button type="submit" class="my-istymo-btn my-istymo-btn-primary">Ajouter l'action</button>
                <button type="button" class="my-istymo-btn my-istymo-btn-secondary my-istymo-modal-close">Annuler</button>
            </div>
        </form>
    </div>
</div>

<div id="change-status-modal" class="my-istymo-modal my-istymo-hidden">
    <div class="my-istymo-modal-overlay"></div>
    <div class="my-istymo-modal-content">
        <h3>🔄 Changer le Statut</h3>
        <form id="change-status-form">
            <input type="hidden" name="lead_id" id="status-lead-id">
            <input type="hidden" name="current_status" id="current-status">
            <div class="workflow-form-group">
                <label for="new-status">Nouveau statut :</label>
                <select name="new_status" id="new-status" required>
                    <option value="">Sélectionner un statut</option>
                    <option value="nouveau">🆕 Nouveau</option>
                    <option value="en_cours">🔄 En cours</option>
                    <option value="qualifie">✅ Qualifié</option>
                    <option value="proposition">📋 Proposition</option>
                    <option value="negocie">💼 Négociation</option>
                    <option value="gagne">🏆 Gagné</option>
                    <option value="perdu">❌ Perdu</option>
                    <option value="en_attente">⏳ En attente</option>
                </select>
            </div>
            <div class="workflow-form-group">
                <label for="status-notes">Notes (optionnel) :</label>
                <textarea name="notes" id="status-notes" rows="3" placeholder="Notes sur le changement de statut..."></textarea>
            </div>
            <div class="my-istymo-modal-actions">
                <button type="submit" class="my-istymo-btn my-istymo-btn-primary">Changer le statut</button>
                <button type="button" class="my-istymo-btn my-istymo-btn-secondary my-istymo-modal-close">Annuler</button>
            </div>
        </form>
    </div>
</div>
