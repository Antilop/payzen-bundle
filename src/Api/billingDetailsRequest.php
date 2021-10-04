<?php

namespace Antilop\SyliusPayzenBundle\Api;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class billingDetailsRequest
{
    /** @var string Référence de l'acheteur */
    public $reference;

    /** @var string Civilité de l'acheteur */
    public $title;

    /** @var string Type d'acheteur PRIVATE pour un particulier et COMPANY pour une entreprise */
    public $type;

    /** @var string Nom de l'acheteur */
    public $firstName; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Prénom de l'acheteur */
    public $lastName; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Numéro de téléphone de l'acheteur */
    public $phoneNumber; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string E-mail de l'acheteur */
    public $email;

    /** @var string Numéro de rue de l'acheteur */
    public $streetNumber; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Adresse de l'acheteur */
    public $address;

    /** @var string Quartier de l'acheteur */
    public $district;

    /** @var string Code postal de l'acheteur */
    public $zipCode; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Ville de l'acheteur */
    public $city;

    /** @var string Etat/Région de l'acheteur */
    public $state;

    /** @var string Pays de l'acheteur selon la norme ISO 3166 */
    public $country;

    /** @var string Langue de l'acheteur selon la norme ISO 639-1 */
    public $language;

    /** @var string Numéro de téléphone mobile */
    public $cellPhoneNumber; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Raison sociale de la société */
    public $legalName; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Permet d'identifier de façon unique chaque citoyen au sein d'un pays */
    public $identityCode; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
