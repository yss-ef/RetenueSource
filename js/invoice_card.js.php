<?php
// ... (l'en-tête PHP avec le bloc de chargement reste le même) ...
require '../../../main.inc.php';
header('Content-Type: application/javascript');
?>
$(document).ready(function() {
    if (window.location.pathname.includes('/fourn/facture/card.php')) {
        
        console.log("--- MODULE RETENUESOURCE ---");
        console.log("LOG: Script de calcul dynamique initialisé.");

        function recalculateTotalWithRAS() {
            console.log("LOG: Lancement du recalcul des totaux...");

            // --- NOUVELLE MÉTHODE DE LECTURE ---
            // On cherche la cellule <td> dont l'ID commence par "invoice_supplier_extras_ras_rate_"
            var ras_rate_cell = $('td[id^="invoice_supplier_extras_ras_rate_"]');
            var ras_rate = 0;
            if (ras_rate_cell.length > 0) {
                ras_rate = parseFloat(ras_rate_cell.text());
            }
            console.log("LOG: Taux de RAS lu depuis la page :", ras_rate);

            // Le reste de la logique ne change pas
            var total_ttc_text = $('td:contains("Montant TTC")').next().text();
            var total_ttc = parseFloat(total_ttc_text.replace(/[^\d,.-]/g, '').replace(',', '.'));
            var total_ht_text = $('td:contains("Montant HT")').next().text();
            var total_ht = parseFloat(total_ht_text.replace(/[^\d,.-]/g, '').replace(',', '.'));
            var remainToPayCell = $('td.amountremaintopay');
            
            if (remainToPayCell.length > 0 && !isNaN(total_ttc)) {
                if (typeof remainToPayCell.data('original-amount') === 'undefined') {
                    remainToPayCell.data('original-amount', total_ttc);
                }
                var originalAmount = remainToPayCell.data('original-amount');

                if (!isNaN(ras_rate) && ras_rate > 0 && !isNaN(total_ht)) {
                    var ras_amount = total_ht * (ras_rate / 100);
                    var new_remaintopay = originalAmount - ras_amount;

                    remainToPayCell.html(
                        new_remaintopay.toFixed(2).replace('.', ',') + ' <?php echo $conf->currency; ?>' +
                        '<br><span class="opacitymedium">(Total ' + originalAmount.toFixed(2).replace('.', ',') + ' - RAS ' + ras_amount.toFixed(2).replace('.', ',') + ')</span>'
                    );
                } else {
                    remainToPayCell.html(originalAmount.toFixed(2).replace('.', ',') + ' <?php echo $conf->currency; ?>');
                }
            }
        }

        // On écoute les changements sur le champ (si l'utilisateur clique sur "Modifier")
        $('body').on('keyup change', 'input[name="options_ras_rate"]', recalculateTotalWithRAS);
        
        // On observe les changements dans le tableau des lignes
        var observer = new MutationObserver(recalculateTotalWithRAS);
        var targetNode = document.getElementById('tablelines');
        if (targetNode) {
            observer.observe(targetNode, { childList: true, subtree: true, characterData: true });
        }

        // On lance un premier calcul au chargement de la page
        setTimeout(recalculateTotalWithRAS, 300);
    }
});