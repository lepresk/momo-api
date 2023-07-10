# Librairie Momo API

La librairie **lepresk/momo-api** est une surcouche au-dessus de l'API officielle de Momo (Mobile Money). Elle facilite
l'interaction avec la plateforme Momo et fournit des fonctionnalités supplémentaires pour simplifier l'intégration et la
gestion des transactions financières.

## Fonctionnalités

La librairie **lepresk/momo-api** vous permet de :

| Produit      | Support                                                                                                                       |
|--------------|-------------------------------------------------------------------------------------------------------------------------------|
| Sandbox      | - Créer un api user<br/>- Créer un api key<br/>- Récupérer les informations du compte                                         |
| Collection   | - Récupérer le solde du compte<br/>- Faire un requestToPay<br/>- Vérifier le statut d'une transaction<br/>- Gérer le callback |
| Disbursement | - *En cours d'implémentation*                                                                                                 |

## Configuration requise

- PHP 7.4 ou supérieur.
- Avoir un compte sur [Momo Developper](https://momodeveloper.mtn.com/) et récupérer la `subscriptionKey` ou avoir les clés d'API fournit par MTN si vous êtes en production.

> 📢 En production la `subscriptionKey`, le `apiUser` et le `apiKey` vous sont directement fourni par MTN

## Installation

Pour installer la librairie **lepresk/momo-api**, vous pouvez utiliser [Composer](https://getcomposer.org/) :

```bash
composer require lepresk/momo-api
```

## Utilisation

Voici un exemple simple d'utilisation de la librairie :

```php
<?php
use Lepresk\MomoApi\MomoApi;
use Lepresk\MomoApi\Utilities;


require 'vendor/autoload.php';

// Récupérer la subscriptionKey dans son profile ou utiliser celui fournit par MTN si vous êtes en production
$subscriptionKey = 'SUBSCRIPTION KEY HERE';

// Récupérer le client Momo
$momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
```
> 📢 Assurez-vous de remplacer "SUBSCRIPTION KEY HERE" par votre clé d'abonnement réelle.

Les environnements possibles

| Constante                            |      Valeur      | Default |
|--------------------------------------|:----------------:|:-------:|
| `MomoApi::ENVIRONMENT_MTN_CONGO`     |     mtncongo     |         |
| `MomoApi::ENVIRONMENT_MTN_UGANDA`    |    mtnuganda     |         |
| `MomoApi::ENVIRONMENT_MTN_GHANA`     |     mtnghana     |         |
| `MomoApi::ENVIRONMENT_IVORY_COAST`   |  mtnivorycoast   |         |
| `MomoApi::ENVIRONMENT_ZAMBIA`        |    mtnzambia     |         |
| `MomoApi::ENVIRONMENT_CAMEROON`      |   mtncameroon    |         |
| `MomoApi::ENVIRONMENT_BENIN`         |     mtnbenin     |         |
| `MomoApi::ENVIRONMENT_SWAZILAND`     |   mtnswaziland   |         |
| `MomoApi::ENVIRONMENT_GUINEACONAKRY` | mtnguineaconakry |         |
| `MomoApi::ENVIRONMENT_SOUTHAFRICA`   |  mtnsouthafrica  |         |
| `MomoApi::ENVIRONMENT_LIBERIA`       |    mtnliberia    |         |
| `MomoApi::ENVIRONMENT_SANDBOX`       |     sandbox      | **OUI** |

### Intéragir avec la sandbox

#### Créer un api user

```php
// Créer une api user
$uuid = Utilities::guidv4(); // Ou tout autre guuidv4 valide
$callbackHost = 'https://my-domain.com/callback';

$apiUser = $momo->sandbox($subscriptionKey)->createApiUser($uuid, $callbackHost);
echo "Api user created: $apiUser\n";
```

#### Récupérer les informations d'un utilisateur

```php
$data = $momo->sandbox($subscriptionKey)->getApiUser($apiUser);
print_r($data);
// [
//      'providerCallbackHost' => 'https://my-domain.com/callback',
//      'targetEnvironment' => 'sandbox',
// ]
```

#### Créer une api key

```php
$apiKey = $momo->sandbox($subscriptionKey)->createApiKey($apiUser);
echo "Api token created: $apiKey\n";
```

### Intéragir avec le produit collection

Avant d'utiliser l'API collection, vous devez définir la configuration.

```php
// Créer un object Config
$config = new \Lepresk\MomoApi\Config::collection($subscriptionKey, $apiUser, $apiKey, $callbackHost);

// Définir la configuration sur l'instance de MomoApi
$momo->setupCollection($config);
```

#### Obtenir un token oauth

```php
$token = $momo->collection()->getAccessToken();

echo $token->getAccessToken(); // Token
echo $token->getExpiresIn(); // Date d'expiration du token
```

> _Pour faire une requête requestToPay ou vérifier le statut de la transaction, vous n'avez pas besoin de demander un token, il est automatiquement généré à chaque transaction_

#### Récupérer le solde du compte

```php
$balance = $momo->collection()->getAccountBalance();

echo $balance->getAvailableBalance(); // Solde du compte
echo $balance->getCurrency(); // Devise du compte
```

#### Faire une requête requestToPay

```php
<?php

// Pour initier un paiement requestToPay
$request = new PaymentRequest(1000, 'EUR', 'ORDER-10', '46733123454', 'Payer message', 'Payer note');
$paymentId = $momo->collection()->requestToPay($request);
```

> Pour obtenir les numéros de téléphones de test, veuillez vous référer à [https://momodeveloper.mtn.com/api-documentation/testing/](https://momodeveloper.mtn.com/api-documentation/testing/)

`$paymentId` est l'id du paiement qui vient d'être éffectuer, vous pouvez l'enregistrer dans votre base de données pour l'utiliser plus tard (vérifier le statut du paiement par exemple)

#### Vérifier le status d'une transaction

```php
<?php
// Vérifier le statut du paiement
$transaction = $momo->collection()->checkRequestStatus($paymentId);

echo $transaction->getStatus(); // Pour obtenir le statut de la transaction
```

#### Gérer le hook du callback

```php
<?php
use Lepresk\MomoApi\Models\Transaction;

// Créer un objet transaction depuis le tableau GET
$transaction = Transaction::parse($_GET);

echo $transaction->getStatus(); // Pour obtenir le statut de la transaction
echo $transaction->getAmount(); // Pour récuperer le montant de la transaction
```

## Documentation supplémentaire

Pour plus d'informations sur l'utilisation de la librairie **lepresk/momo-api** et les fonctionnalités disponibles,
veuillez consulter la documentation officielle dans le dossier "docs" du dépôt GitHub.

## Contribution

Les contributions sont les bienvenues ! Si vous souhaitez améliorer la librairie, signalez des problèmes ou soumettez
des demandes de fonctionnalités, veuillez créer une issue sur le dépôt GitHub de la
librairie : [lepresk/momo-api](https://github.com/lepresk/momo-api).

## Licence

Cette librairie est distribuée sous la licence [MIT](https://opensource.org/licenses/MIT). Vous êtes libre de l'utiliser
et de la modifier selon vos besoins.