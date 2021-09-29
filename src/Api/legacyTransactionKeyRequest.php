<?php

namespace App\Api\Payzen;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class legacyTransactionKeyRequest
{
    /** @var string Identifiant de la transaction à rechercher */
    public $transactionId; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var int Numéro de séquence de la transaction à rechercher */
    public $sequenceNumber; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var datetime Date de création de la transaction au format ISO 8601 définit par W3C */
    public $creationDate; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
