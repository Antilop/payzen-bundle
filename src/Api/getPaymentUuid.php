<?php

namespace App\Api\Payzen;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class getPaymentUuid
{
    /**
     * @var legacyTransactionKeyRequest permet de traduire les anciens attributs transactionId,
     * sequenceNumber et transmissionDate en transactionUuid (référence unique de transaction)
     *
     */
    public $legacyTransactionKeyRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
