<?php

namespace Antilop\SyliusPayzenBundle\Api;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class queryRequest
{
    /** @var string Référence unique de la transaction */
    public $uuid;

    /** @var string Référence de la commande */
    public $orderId; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Identifiant de l'abonnement */
    public $subscriptionId; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Alias (paiement par identifiant) */
    public $paymentToken; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
