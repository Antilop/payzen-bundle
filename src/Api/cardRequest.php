<?php

namespace Antilop\SyliusPayzenBundle\Api;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class cardRequest
{
    /** @var string Identifiant unique (alias) associé à un moyen de paiement. */
    public $paymentToken; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Numéro de la carte */
    public $number;

    /**
     * @var string Types de cartes.
     * Les valeurs possibles sont AMEX, CB, MASTERCARD, VISA, VISA_ELECTRON, VPAY, MAESTRO, ECARTEBLEUE
     * ou JCB
     */
    public $scheme;

    /** @var int Mois d’expiration de la carte, entre 1 et 12 */
    public $expiryMonth; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var int Année d’expiration de la carte sur 4 digits */
    public $expiryYear; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var string Cryptogramme visuel à 3 chiffres (ou 4 pour Amex) */
    public $cardSecurityCode; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /** @var datetime Date de naissance du porteur au format YYYY-MM-DD */
    public $cardHolderBirthday; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
