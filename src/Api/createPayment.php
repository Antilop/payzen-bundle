<?php

namespace App\Api\Payzen;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class createPayment
{
    /** @var commonRequest permet de transmettre des informations générales sur une opération */
    public $commonRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var paymentRequest permet de transmettre des informations liées au paiement */
    public $paymentRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var orderRequest permet de transmettre des informations liées à la commande */
    public $orderRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var cardRequest permet de transmettre les informations sur la carte de paiement */
    public $cardRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /**
     * @var threeDSRequest (optionnel) déterminer si un paiement est réalisé avec ou sans authentification 3D Secure,
     * transmettre des informations liées à 3D Secure
     */
    public $threeDSRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /**
     * @var customerRequest (optionnel) permet de transmettre des informations liées à la livraison, à la facturation et
     * des données techniques liées à l'acheteur
     */
    public $customerRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /**
     * @var techRequest (optionnel) permet de transmettre des informations techniques à propos du navigateur de
     * l'acheteur */
    public $techRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var shoppingCartRequest (optionnel) permet de transmettre le contenu du panier */
    public $shoppingCartRequest; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
