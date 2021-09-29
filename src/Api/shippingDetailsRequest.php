<?php

namespace App\Api\Payzen;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class shippingDetailsRequest
{
    /** @var string Type d'acheteur PRIVATE pour un particulier et COMPANY pour une entreprise */
    public $type;

    /** @var string Nom de l'acheteur */
    public $firstName; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Prénom de l'acheteur */
    public $lastName; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Numéro de téléphone de l'acheteur */
    public $phoneNumber; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Numéro de rue de l'acheteur */
    public $streetNumber; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Adresse de livraison */
    public $address;

    /** @var string Complément d'adresse de livraison */
    public $address2;

    /** @var string Quartier de livraison */
    public $district;

    /** @var string Code postal de livraison */
    public $zipCode; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Ville de livraison */
    public $city;

    /** @var string Etat/Région de livraison */
    public $state;

    /** @var string Pays de livraison */
    public $country;

    /** @var string Informations sur le transporteur */
    public $deliveryCompanyName; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Mode de livraison sélectionné */
    public $shippingSpeed; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Méthode de livraison utilisée */
    public $shippingMethod; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Raison sociale de la société */
    public $legalName; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Permet d'identifier de façon unique chaque citoyen au sein d'un pays */
    public $identityCode; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
