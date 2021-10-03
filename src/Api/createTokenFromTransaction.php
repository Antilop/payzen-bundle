<?php

namespace Antilop\SyliusPayzenBundle\Api;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class createTokenFromTransaction
{
    /**
     * @var queryRequest (requis) permet d’interroger un alias (identifiant de compte) pour en connaître ses différents
     * attributs
     */
    public $queryRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var cardRequest (optionnel) permet de transmettre les informations sur la carte de paiement */
    public $cardRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var commonRequest (optionnel) permet de transmettre des informations générales sur une opération */
    public $commonRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
