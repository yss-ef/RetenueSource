<?php
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modRetenueSource extends DolibarrModules
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->numero = 500005; // Assurez-vous que cet ID est unique
        $this->rights_class = 'retenuesource';
        $this->family = "financial";
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Applique une retenue à la source sur les factures fournisseurs.";
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'fas fa-hand-holding-usd';
		$this->editor_name = 'Youssef Fellah';

        // On active les triggers et on charge notre fichier JS
        $this->module_parts = array(
            'triggers' => 1,
            'js' => array('/retenuesource/js/invoice_card.js.php')
        );
    }

    public function init($options = '') { $sql = array(); return $this->_init($sql, $options); }
    public function remove($options = '') { $sql = array(); return $this->_remove($sql, $options); }

    /**
     * Trigger qui s'exécute au moment de la validation d'une facture fournisseur
     */
    public function run_trigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        // On agit uniquement sur l'événement de validation
        if ($action == 'INVOICE_SUPPLIER_VALIDATE') {
            
            $object->fetch_optionals();

            // !!! IMPORTANT : Remplacez 'ras_rate' ci-dessous par le code exact de votre extrafield si différent.
            if (!empty($object->array_options['options_ras_rate']) && is_numeric($object->array_options['options_ras_rate'])) {
                
                $ras_rate = $object->array_options['options_ras_rate'];
                
                $alreadyExists = false;
                foreach ($object->lines as $line) {
                    if (strpos($line->description, 'Retenue à la source') !== false) {
                        $alreadyExists = true;
                        break;
                    }
                }

                if (!$alreadyExists && $ras_rate > 0) {
                    $ras_amount = $object->total_ht * ($ras_rate / 100);

                    // On ajoute une ligne de service NÉGATIVE pour déduire la retenue
                    $object->addline(
                        'Retenue à la source ('.$ras_rate.'%)',
                        -$ras_amount,
                        1, 0, 0, 0, 'SERVICE'
                    );
                }
            }
        }
        return 0;
    }
}