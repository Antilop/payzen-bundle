<?php

namespace Antilop\SyliusPayzenBundle\Api;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class commonRequest
{
    /** @var datetime Date et heure UTC de la transaction exprimée au format ISO 8601 définit par W3C */
    public $submissionDate; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /**
     * @var string Origine de la transaction
     * Les valeurs possibles sont EC, MOTO, CC, OTHER
     */
    public $paymentSource; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Numéro de contrat commerçant utilisé */
    public $contractNumber; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Commentaire libre */
    public $comment; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
