<?php
/**
 * Template pour la page des logs API
 * Variables attendues dans $context :
 * - $log_file : chemin vers le fichier de log
 * - $log_content : contenu des logs (si disponible)
 * - $log_stats : statistiques du fichier de log (taille, date de modification)
 */
?>

<div class="wrap">
    <h1>📋 Logs API La Poste</h1>
    <p>Consultez ici les logs détaillés des appels à l'API La Poste pour diagnostiquer les erreurs.</p>
    
    <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h3>🔍 Derniers logs</h3>
        <?php if (file_exists($log_file)): ?>
            <div style="background: #fff; padding: 10px; border: 1px solid #ccc; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 12px; white-space: pre-wrap;">
                <?php echo esc_html($log_content); ?>
            </div>
            
            <p><strong>Fichier de log :</strong> <?php echo esc_html($log_file); ?></p>
            <p><strong>Taille :</strong> <?php echo size_format($log_stats['size']); ?></p>
            <p><strong>Dernière modification :</strong> <?php echo date('Y-m-d H:i:s', $log_stats['modified']); ?></p>
        <?php else: ?>
            <p>Aucun fichier de log trouvé. Les logs apparaîtront après le premier envoi de lettre.</p>
        <?php endif; ?>
    </div>
    
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;">
        <h4>💡 Comment utiliser ces logs :</h4>
        <ul>
            <li><strong>Payload envoyé :</strong> Vérifiez que toutes les données sont correctement formatées</li>
            <li><strong>Code HTTP :</strong> 
                <ul>
                    <li>200-299 = Succès</li>
                    <li>400-499 = Erreur client (données invalides, authentification, etc.)</li>
                    <li>500-599 = Erreur serveur</li>
                </ul>
            </li>
            <li><strong>Body de réponse :</strong> Contient les détails de l'erreur retournée par l'API</li>
            <li><strong>Headers :</strong> Informations sur l'authentification et le format des données</li>
        </ul>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="<?php echo admin_url('admin.php?page=sci-logs&clear=1'); ?>" 
           class="button button-secondary"
           onclick="return confirm('Êtes-vous sûr de vouloir effacer tous les logs ?')">
            🗑️ Effacer les logs
        </a>
    </div>
</div> 