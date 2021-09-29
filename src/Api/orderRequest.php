<?php

namespace App\Api\Payzen;

// @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
class orderRequest
{
    /** @var string Référence de la commande */
    public $orderId; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen

    /**
     * @var extInfo (optionnel) Champs personnalisables permettant d'ajouter des données supplémentaires (champ
     * supplémentaire qui sera persisté dans la transaction et sera retourné dans la réponse).
     * L'attribut extInfo est composé de sous objets :
     * • key : nom de la donnée. Son format est "string".
     * • value : valeur de la donnée. Son format est "string".
     * Exemple : <extInfo><key>keyData</key><value>valuedata</value></extInfo>
     */
    public $extInfo; // @codingStandardsIgnoreLine - Modèle distant des données pour le WS SOAP payzen
}
