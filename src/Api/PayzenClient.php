<?php

namespace App\Api\Payzen;

use App\Entity\Payment\Payment;
use DOMDocument;
use DOMXPath;
use SoapFault;
use SoapClient;
use SoapHeader;
use DateTime;

/**
 * @link https://sogecommerce.societegenerale.eu/doc/fr-FR/webservices-payment/implementation-webservices-v5/tla1414490232969.pdf Documentation de l'API
 */
class PayzenClient
{
    protected $client;
    protected $key;
    protected $siteId;
    protected $wsdl;
    protected $requestId;
    protected $authToken;
    protected $environment;

    /**
     * PayzenClient constructor.
     *
     * @param Payment $payment
     */
    public function __construct(Payment $payment)
    {
        $config = $payment->getMethod()->getGatewayConfig()->getConfig();

        $this->wsdl = $config['webservice_endpoint'];
        $this->siteId = $config['site_id'];
        $this->key = $config['certificate'];
        $this->environment = $config['ctx_mode'];
    }

    public function createPayment(createPayment $createPayment)
    {
        $result = [];
        $createPaymentResponse = null;
        $success = 0;
        $responseCode = null;
        $message = '';
        $transactionId = '';
        $data = [];
        $timestamp = time();

        try {
            $createPaymentResponse = $this->client->createPayment($createPayment);
        } catch (SoapFault $fault) {
            //Gestion des exceptions
            trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
        }

        $dom = new DOMDocument();
        $dom->loadXML($this->client->__getLastResponse(), LIBXML_NOWARNING);
        $path = new DOMXPath($dom);
        $headers = $path->query('//*[local-name()="Header"]/*');
        $responseHeader = array();
        foreach ($headers as $headerItem) {
            $responseHeader[$headerItem->nodeName] = $headerItem->nodeValue;
        }

        //Calcul du jeton d'authentification de la réponse
        $authTokenResponse = base64_encode(hash_hmac('sha256', $responseHeader['timestamp'] . $responseHeader['requestId'], $this->key, true));
        if ($authTokenResponse !== $responseHeader['authToken']) {
            $message = 'Erreur interne rencontrée : erreur de calcul ou tentative de fraude';
        } else {
            $responseCode = $createPaymentResponse->createPaymentResult->commonResponse->responseCode;
            $message = $this->getCodeDetail($responseCode) . ' (CODE:' . $responseCode . ')';

            //Analyse de la réponse
            if ($createPaymentResponse->createPaymentResult->commonResponse->responseCode != "0") {
                $success = 0;
            } else {
                if ($createPaymentResponse->createPaymentResult->commonResponse->transactionStatusLabel == 'AUTHORISED'
                    || $createPaymentResponse->createPaymentResult->commonResponse->transactionStatusLabel == 'WAITING_AUTORISATION'
                    || $createPaymentResponse->createPaymentResult->commonResponse->transactionStatusLabel == 'AUTHORISED_TO_VALIDATE'
                    || $createPaymentResponse->createPaymentResult->commonResponse->transactionStatusLabel == 'WAITING_AUTORISATION_TO_VALIDATE'
                ) {
                    $success = 1;
                } else {
                    $success = 0;
                }

                $transactionId = $createPaymentResponse->createPaymentResult->paymentResponse->transactionId;

                $cardNumber = $createPaymentResponse->createPaymentResult->cardResponse->number;
                $cardBrand = $createPaymentResponse->createPaymentResult->cardResponse->brand;
                $expiryMonth = $createPaymentResponse->createPaymentResult->cardResponse->expiryMonth;
                $expiryYear = $createPaymentResponse->createPaymentResult->cardResponse->expiryYear;

                $data = array(
                    'card_number' => $cardNumber,
                    'card_brand' => $cardBrand,
                    'expiry_month' => $expiryMonth,
                    'expiry_year' => $expiryYear
                );

                if (!empty($createPaymentResponse->createPaymentResult->authorizationResponse)) {
                    $resultTimestamp = $createPaymentResponse->createPaymentResult->authorizationResponse->date;
                    $timestamp = strtotime($resultTimestamp . ' UTC');

                    $message .= ' | ' . $this->codeDetailAuthorizationResponse($createPaymentResponse->createPaymentResult->authorizationResponse->result) . ' (CODE : ' . $createPaymentResponse->createPaymentResult->authorizationResponse->result . ')';
                } else {
                    $resultTimestamp = new DateTime('now');
                    $timestamp = $resultTimestamp->format('Y-m-d H:i:s');

                    $message .= ' |  (CODE : )';
                }
            }
        }

        $result['success'] = $success;
        $result['response_code'] = $responseCode;
        $result['message'] = $message;
        $result['transaction_id'] = $transactionId;
        $result['data'] = $data;
        $result['timestamp'] = $timestamp;

        return $result;
    }

    public function init()
    {
        // Exemple d'Initialisation d'un client SOAP sans proxy
        $this->client = new SoapClient(
            $this->wsdl,
            $options = array(
                'trace' => 1,
                'exceptions' => 0,
                'encoding' => 'UTF-8',
                'soapaction' => ''
            )
        );

        $this->requestId = $this->genUuid();
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        $this->authToken = base64_encode(hash_hmac('sha256', $this->requestId . $timestamp, $this->key, true));
        $this->setHeaders($this->siteId, $this->requestId, $timestamp, $this->environment, $this->key);
    }

    public function genUuid()
    {
        if (function_exists('random_bytes')) {
            // PHP 7
            $data = random_bytes(16);
        } else {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
        }

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6 & 7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function generateToken(createTokenFromTransaction $createTokenRequest)
    {
        $result = [];

        $message = '';
        $responseCode = null;
        $success = 0;
        $paymentToken = '';
        $createTokenResponse = null;

        //Appel de l'opération createToken
        try {
            $createTokenResponse = $this->client->createTokenFromTransaction($createTokenRequest);
        } catch (SoapFault $fault) {
            //Gestion des exceptions
            trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
        }

        //Analyse de la réponse
        //Récupération du SOAP Header de la réponse afin de stocker les en-têtes dans un tableau (ici $responseHeader)
        $dom = new DOMDocument;
        $dom->loadXML($this->client->__getLastResponse(), LIBXML_NOWARNING);
        $path = new DOMXPath($dom);
        $headers = $path->query('//*[local-name()="Header"]/*');
        $responseHeader = array();
        foreach ($headers as $headerItem) {
            $responseHeader[$headerItem->nodeName] = $headerItem->nodeValue;
        }

        // Calcul du jeton d'authentification de la réponse
        $authTokenResponse = base64_encode(hash_hmac('sha256', $responseHeader['timestamp'] . $responseHeader['requestId'], $this->key, true));

        if ($authTokenResponse !== $responseHeader['authToken']) {
            // Erreur de calcul ou tentative de fraude
            $success = 0;
            $message = 'Erreur interne rencontrée';
        } else {
            $responseCode = $createTokenResponse->createTokenFromTransactionResult->commonResponse->responseCode;

            if ($responseCode != "0") {
                $success = 0;
                $message = $this->getCodeDetail($responseCode);
            } else {
                $success = 1;
                $paymentToken = $createTokenResponse->createTokenFromTransactionResult->commonResponse->paymentToken;

                $message = $createTokenResponse->createTokenFromTransactionResult->commonResponse->responseCodeDetail . '(' . $paymentToken . ')';
            }
        }

        $result['success'] = $success;
        $result['response_code'] = $responseCode;
        $result['message'] = $message;
        $result['payment_token'] = $paymentToken;

        return $result;
    }

    public function getAuthToken($requestId, $timestamp, $key)
    {
        $data = "";
        $data = $requestId . $timestamp;
        $authToken = hash_hmac("sha256", $data, $key, true);
        $authToken = base64_encode($authToken);

        return $authToken;
    }

    public function setHeaders($siteId, $requestId, $timestamp, $mode, $key)
    {
        // Création des en-têtes shopId, requestId, timestamp, mode et authToken
        $ns = 'http://v5.ws.vads.lyra.com/Header/';
        $headerShopId = new SoapHeader($ns, 'shopId', $siteId);
        $headerRequestId = new SoapHeader($ns, 'requestId', $requestId);
        $headerTimestamp = new SoapHeader($ns, 'timestamp', $timestamp);
        $headerMode = new SoapHeader($ns, 'mode', $mode);
        $authToken = $this->getAuthToken($requestId, $timestamp, $key);

        $headerAuthToken = new SoapHeader($ns, 'authToken', $authToken);
        // Ajout des en-têtes dans le SOAP Header
        $headers = array(
            $headerShopId,
            $headerRequestId,
            $headerTimestamp,
            $headerMode,
            $headerAuthToken
        );

        $this->client->__setSoapHeaders($headers);
    }

    public function getCodeDetail($code)
    {
        $codeDetail = [
            "0" => "Action réalisée avec succès",
            "1" => "Action non autorisée.",
            "2" => "Attribut invalide.",
            "3" => "La requête n'a pu être traitée.",
            "10" => "Transaction non trouvée.",
            "11" => "Statut de la transaction incorrect.",
            "12" => "Transaction existe déjà.",
            "13" => "Mauvaise date (la valeur de l'attribut 'submissionDate' est trop loin de la date actuelle).",
            "14" => "Aucun changement.",
            "15" => "Trop de résultats.",
            "20" => "Montant invalide dans l'attribut 'amount'.",
            "21" => "Devise invalide dans l'attribut 'currency'.",
            "22" => "Type de carte inconnu.",
            "23" => "Date invalide dans les attributs 'expiryMonth' et/ou 'expiryYear'.",
            "24" => "Le 'cvv' est obligatoire.",
            "25" => "Numéro de contrat inconnu.",
            "26" => "Le numéro de carte est invalide.",
            "30" => "L'alias n'est pas trouvé.",
            "31" => "L'alias est invalide (Résilié, vide…).",
            "32" => "Attribut 'subscriptionId' non trouvé.",
            "33" => "Attribut 'rrule' invalide",
            "34" => "L'alias existe déjà.",
            "35" => "Création de l'alias refusé.",
            "36" => "Attribut 'paymentToken' purgé.",
            "40" => "Attribut 'amount' non autorisé",
            "41" => "Plage de carte non trouvée",
            "42" => "Le solde du moyen de paiement n'est pas suffisant.",
            "43" => "Le remboursement n'est pas autorisé pour ce contrat.",
            "50" => "Aucune brand localisée.",
            "51" => "Marchand non enrôlé.",
            "52" => "Signature de l'ACS invalide.",
            "53" => "Erreur technique 3DS.",
            "54" => "Paramètre 3DS incorrect.",
            "55" => "3DS désactivé.",
            "56" => "PAN non trouvé.",
            "97" => "OneyWs Erreur.",
            "98" => "Attribut RequestId invalide.",
            "99" => "Erreur inconnue."
        ];

        return $codeDetail[$code];
    }

    public function codeDetailAuthorizationResponse($code)
    {
        $codeDetail = [
            '0' => 'Transaction approuvée ou traitée avec succès',
            '2' => 'Contacter l’émetteur de carte',
            '3' => 'Accepteur invalide',
            '4' => 'Conserver la carte',
            '5' => 'Ne pas honorer',
            '7' => 'Conserver la carte, conditions spéciales',
            '8' => 'Approuver après identification',
            '12' => 'Transaction invalide',
            '13' => 'Montant invalide',
            '14' => 'Numéro de porteur invalide',
            '15' => 'Emetteur de carte inconnu',
            '17' => 'Annulation acheteur',
            '19' => 'Répéter la transaction ultérieurement',
            '20' => 'Réponse erronée (erreur dans le domaine serveur)',
            '24' => 'Mise à jour de fichier non supportée',
            '25' => 'Impossible de localiser l’enregistrement dans le fichier',
            '26' => 'Enregistrement dupliqué, ancien enregistrement remplacé',
            '27' => 'Erreur en « edit » sur champ de liste à jour fichier',
            '28' => 'Accès interdit au fichier',
            '29' => 'Mise à jour impossible',
            '30' => 'Erreur de format',
            '31' => 'Identifiant de l’organisme acquéreur inconnu',
            '33' => 'Date de validité de la carte dépassée',
            '34' => 'Suspicion de fraude	 ',
            '38' => 'Date de validité de la carte dépassée	 ',
            '41' => 'Carte perdue	 ',
            '43' => 'Carte volée	 ',
            '51' => 'Provision insuffisante ou crédit dépassé	 ',
            '54' => 'Date de validité de la carte dépassée	 ',
            '55' => 'Code confidentiel erroné	 ',
            '56' => 'Carte absente du fichier	 ',
            '57' => 'Transaction non permise à ce porteur	 ',
            '58' => 'Transaction non permise à ce porteur	 ',
            '59' => 'Suspicion de fraude	 ',
            '60' => 'L’accepteur de carte doit contacter l’acquéreur	 ',
            '61' => 'Montant de retrait hors limite	 ',
            '63' => 'Règles de sécurité non respectées	 ',
            '68' => 'Réponse non parvenue ou reçue trop tard	 ',
            '75' => 'Nombre d’essais code confidentiel dépassé	 ',
            '76' => 'Porteur déjà en opposition, ancien enregistrement conservé	 ',
            '90' => 'Arrêt momentané du système	 ',
            '91' => 'Émetteur de cartes inaccessible	 ',
            '94' => 'Transaction dupliquée	 ',
            '96' => 'Mauvais fonctionnement du système	 ',
            '97' => 'Échéance de la temporisation de surveillance globale	 ',
            '98' => 'Serveur indisponible routage réseau demandé à nouveau	 ',
            '99' => 'Incident domaine initiateur	 ',

        ];

        return $codeDetail[$code];
    }
}
