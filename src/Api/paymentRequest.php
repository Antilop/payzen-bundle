<?php

namespace Antilop\SyliusPayzenBundle\Api;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class paymentRequest
{
    /** @var long Montant de la transaction dans sa plus petite unité monétaire (le centime pour l'euro) */
    public $amount;

    /** @var int Code de la devise de la transaction (norme ISO 4217) */
    public $currency;

    /** @var string (optionnel) Identifiant de la transaction lors de la création ou la modification d'une transaction de paiement. */
    public $transactionId; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var datetime (optionnel) Date de remise demandée exprimée au format ISO 8601 définit par W3C */
    public $expectedCaptureDate; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var int (optionnel) Permet de valider manuellement une transaction tant que la date de remise en banque souhaitée n’est pas dépassée */
    public $manualValidation; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string (optionnel) Permet de spécifier l'identifiant unique de la transaction afin de réitérer la demande du paiement
     * refusée */
    public $retryUuid; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
