<?php

namespace Antilop\SyliusPayzenBundle\Api;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class paymentResponse
{
    /** @var string Référence unique de la transaction générée par la plateforme de paiement */
    public $transactionUuid; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var long Montant de la transaction dans sa plus petite unité monétaire (le centime pour l'euro). */
    public $amount;

    /** @var int Code de la devise de la transaction (norme ISO 4217) */
    public $currency;

    /** @var long Montant de la transaction dans la devise réellement utilisée pour effectuer la remise en banque */
    public $effectiveAmount; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var int Devise réellement utilisée pour effectuer la remise en banque. */
    public $effectiveCurrency; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var datetime Date de remise en banque souhaitée */
    public $expectedCaptureDate; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var int Permet de valider manuellement une transaction tant que la date de remise en banque souhaitée n’est pas dépassée. */
    public $manualValidation; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var int 0 pour une opération de débit, 1 pour une opération de remboursement */
    public $operationType; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var datetime Date et heure de l’enregistrement de la transaction exprimée au format W3C */
    public $creationDate; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Référence fournie par un tiers : numéro de transaction pour PayPal, Boleto, RRN pour Prism, etc… */
    public $externalTransactionId; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Transfert de responsabilité. YES lorsque le paiement est garanti, NO lorsque le paiement n'est pas garanti */
    public $liabilityShift; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var int Devise réellement utilisée pour effectuer la remise en banque. */
    public $sequenceNumber; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Type de paiement. */
    public $paymentType; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var int Complément d’information en cas d’erreur technique. Retourne un code d'erreur associé à l'erreur technique * */
    public $paymentError; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
