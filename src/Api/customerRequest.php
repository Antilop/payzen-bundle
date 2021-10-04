<?php

namespace Antilop\SyliusPayzenBundle\Api;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class customerRequest
{
    /** @var billingDetailsRequest Données de facturation de l'acheteur */
    public $billingDetailsRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var shippingDetailsRequest (optionnel) Données de livraison de l'acheteur */
    public $shippingDetailsRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var extraDetailsRequest (optionnel) Données techniques liées à l'acheteur */
    public $extraDetailsRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
