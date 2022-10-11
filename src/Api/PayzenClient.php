<?php

namespace Antilop\SyliusPayzenBundle\Api;

use App\Entity\Order\Order;
use App\Entity\Subscription\SubscriptionDraftOrder;
use App\Entity\Subscription\Subscription;
use Lyra\Client as LyraClient;

class PayzenClient
{
    /** @var LyraClient */
    protected $client;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /**
     * PayzenClient constructor.
     *
     * @param Payment $payment
     */
    public function __construct($username, $password, $endpoint)
    {
        $this->username = $username;
        $this->password = $password;
        $this->endpoint = $endpoint;
    }

    public function processPayment(SubscriptionDraftOrder $subscriptionDraftOrder)
    {
        $authorizationResultCode = 99;
        $result = [];
        $success = false;
        $timestamp = time();
        $transactionCode = 99;
        $transactionId = '';
        $transactionUuid = '';

        $subscription = $subscriptionDraftOrder->getSubscription();
        $customer = $subscription->getCustomer();
        $payment = $subscriptionDraftOrder->getLastPayment();

        if (empty($payment) || $payment->getMethod()->getCode() !== 'PAYZEN') {
            return [
                'success' => false,
                'message' => sprintf('Moyen de paiement associé incorrect ou inexistant ABO : %s', $subscription->getId())
            ];
        }

        $token = $subscription->getCardToken();

        $params = [
            'amount' => $subscriptionDraftOrder->getTotal(),
            'currency' => $subscriptionDraftOrder->getCurrencyCode(),
            'orderId' => $subscriptionDraftOrder->getNumber(),
            'customer' => [
                'email' => $customer->getEmail()
            ],
            'paymentMethodToken' => $token,
            'formAction' => 'SILENT'
        ];

        // Add Metadata
        $params['metadata'] = [
            'subscription_id' => $subscription->getId(),
            'order_id' => $subscriptionDraftOrder->getId(),
            'payment_id' => $payment->getId(),
            'customer_id' => $customer->getId()
        ];

        $response = $this->client->post('V4/Charge/CreatePayment', $params);
        $answer = $response['answer'];

        if ($response['status'] !== 'SUCCESS') {
            return [
                'error' => $answer['errorMessage'],
                'formToken' => false,
                'success' => false,
            ];
        }

        if (array_key_exists('transactions', $answer) && is_array($answer['transactions'])) {
            $transaction = current($answer['transactions']);

            if (!empty($transaction)) {
                $transactionDetails = $transaction['transactionDetails'];
                $paymentMethodDetails = $transactionDetails['paymentMethodDetails'];
                $authorizationResponse = $paymentMethodDetails['authorizationResponse'];
                $cardDetails = $transactionDetails['cardDetails'];
                $transactionCode = intval($transaction['errorCode']);
                $authorizationResultCode = $authorizationResponse['authorizationResult'];
                $authorizationDate = $authorizationResponse['authorizationDate'];

                if ($authorizationResultCode == '0') {
                    $success = true;
                    $timestamp = strtotime($authorizationDate . ' UTC');
                    $cardNumber = $cardDetails['pan'];
                    $cardBrand = $cardDetails['effectiveBrand'];
                    $expiryMonth = $cardDetails['expiryMonth'];
                    $expiryYear = $cardDetails['expiryYear'];
                    $transactionUuid = $transaction['uuid'];
                    $transactionId = $cardDetails['legacyTransId'];

                    $result['vads_card_number'] = $cardNumber;
                    $result['vads_expiry_month'] = $expiryMonth;
                    $result['vads_expiry_year'] = $expiryYear;
                    $result['vads_card_brand'] = $cardBrand;
                }
            }
        }

        $message = $this->getCodeDetail($transactionCode) . ' (CODE:' . $transactionCode . ')';
        $message .= ' | ' . $this->codeDetailAuthorizationResponse($authorizationResultCode) . ' (CODE : ' . $authorizationResultCode . ')';

        $result['success'] = $success;
        $result['response_code'] = $transactionCode;
        $result['message'] = $message;
        $result['vads_trans_id'] = $transactionId;
        $result['vads_trans_uuid'] = $transactionUuid;
        $result['timestamp'] = $timestamp;

        return $result;
    }

    /**
     * Init keys for SDK
     *
     * @return void
     */
    public function init()
    {
        LyraClient::setDefaultUsername($this->username);
        LyraClient::setDefaultPassword($this->password);
        LyraClient::setDefaultEndpoint($this->endpoint);

        $this->client = new LyraClient();
    }


    public function getCodeDetail($code)
    {
        $codeDetail = [
            '0' => 'Action réalisée avec succès',
            '1' => 'Action non autorisée.',
            '2' => 'Attribut invalide.',
            '3' => 'La requête n\'a pu être traitée.',
            '10' => 'Transaction non trouvée.',
            '11' => 'Statut de la transaction incorrect.',
            '12' => 'Transaction existe déjà.',
            '13' => 'Mauvaise date (la valeur de l\'attribut \'submissionDate\' est trop loin de la date actuelle).',
            '14' => 'Aucun changement.',
            '15' => 'Trop de résultats.',
            '20' => 'Montant invalide dans l\'attribut \'amount\'.',
            '21' => 'Devise invalide dans l\'attribut \'currency\'.',
            '22' => 'Type de carte inconnu.',
            '23' => 'Date invalide dans les attributs \'expiryMonth\' et/ou \'expiryYear\'.',
            '24' => 'Le \'cvv\' est obligatoire.',
            '25' => 'Numéro de contrat inconnu.',
            '26' => 'Le numéro de carte est invalide.',
            '30' => 'L\'alias n\'est pas trouvé.',
            '31' => 'L\'alias est invalide (Résilié, vide…).',
            '32' => 'Attribut \'subscriptionId\' non trouvé.',
            '33' => 'Attribut \'rrule\' invalide',
            '34' => 'L\'alias existe déjà.',
            '35' => 'Création de l\'alias refusé.',
            '36' => 'Attribut \'paymentToken\' purgé.',
            '40' => 'Attribut \'amount\' non autorisé',
            '41' => 'Plage de carte non trouvée',
            '42' => 'Le solde du moyen de paiement n\'est pas suffisant.',
            '43' => 'Le remboursement n\'est pas autorisé pour ce contrat.',
            '50' => 'Aucune brand localisée.',
            '51' => 'Marchand non enrôlé.',
            '52' => 'Signature de l\'ACS invalide.',
            '53' => 'Erreur technique 3DS.',
            '54' => 'Paramètre 3DS incorrect.',
            '55' => '3DS désactivé.',
            '56' => 'PAN non trouvé.',
            '97' => 'OneyWs Erreur.',
            '98' => 'Attribut RequestId invalide.',
            '99' => 'Erreur inconnue.'
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
            '34' => 'Suspicion de fraude',
            '38' => 'Date de validité de la carte dépassée',
            '41' => 'Carte perdue',
            '43' => 'Carte volée',
            '51' => 'Provision insuffisante ou crédit dépassé',
            '54' => 'Date de validité de la carte dépassée',
            '55' => 'Code confidentiel erroné',
            '56' => 'Carte absente du fichier',
            '57' => 'Transaction non permise à ce porteur',
            '58' => 'Transaction non permise à ce porteur',
            '59' => 'Suspicion de fraude',
            '60' => 'L’accepteur de carte doit contacter l’acquéreur',
            '61' => 'Montant de retrait hors limite',
            '63' => 'Règles de sécurité non respectées',
            '68' => 'Réponse non parvenue ou reçue trop tard',
            '75' => 'Nombre d’essais code confidentiel dépassé',
            '76' => 'Porteur déjà en opposition, ancien enregistrement conservé',
            '80' => 'Le paiement sans contact n’est pas admis par l’émetteur',
            '81' => 'Le paiement non sécurisé n’est pas admis par l’émetteur',
            '82' => 'Révocation paiement récurrent pour la carte chez le commerçant ou pour le MCC et la carte',
            '83' => 'Révocation tous paiements récurrents pour la carte',
            '90' => 'Arrêt momentané du système',
            '91' => 'Émetteur de cartes inaccessible',
            '94' => 'Transaction dupliquée',
            '96' => 'Mauvais fonctionnement du système',
            '97' => 'Échéance de la temporisation de surveillance globale',
            '98' => 'Serveur indisponible routage réseau demandé à nouveau',
            '99' => 'Incident domaine initiateur'
        ];

        if (!array_key_exists($code, $codeDetail)) {
            return 'Code d’erreur non répertorié';
        }

        return $codeDetail[$code];
    }
}
