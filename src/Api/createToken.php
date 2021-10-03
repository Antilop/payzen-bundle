<?php

namespace Antilop\SyliusPayzenBundle\Api;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class createToken
{
    /** @var commonRequest (optionnel) permet de transmettre des informations générales sur une opération */
    public $commonRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var cardRequest permet de transmettre les informations sur la carte de paiement */
    public $cardRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /**
     * @var customerRequest permet de transmettre des informations liées à la livraison, à la facturation et
     * des données techniques liées à l'acheteur
     *
     */
    public $customerRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
