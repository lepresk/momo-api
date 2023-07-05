# Librairie Momo API

La librairie **lepresk/momo-api** est une surcouche au-dessus de l'API officielle de Momo (Mobile Money). Elle facilite
l'interaction avec la plateforme Momo et fournit des fonctionnalités supplémentaires pour simplifier l'intégration et la
gestion des transactions financières.

## Fonctionnalités

La librairie **lepresk/momo-api** vous permet de :

- Interagir avec la sandbox Momo pour effectuer des tests de développement.
- Interagir avec le produit Collection de Momo pour effectuer des paiements et vérifier l'état des transactions.

## Configuration requise

- PHP 7.4 ou supérieur.
- Accès à l'API officielle de Momo et une clé d'abonnement valide.

En production le `subscriptionKey`, le `apiUser` et le `apiKey` vous sont directement fournit par MTN

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
$momo = MomoApi::create($subscriptionKey);

// Assurez-vous de remplacer "SUBSCRIPTION KEY HERE" par votre clé d'abonnement réelle.

// Définir l'environnement (MomoApi::ENVIRONMENT_SANDBOX par défaut)
$momo->setEnvironment(MomoApi::ENVIRONMENT_SANDBOX);
```

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

#### [Créer un api user](https://momodeveloper.mtn.com/docs/services/sandbox-provisioning-api/operations/post-v1_0-apiuser)

```php
$momo->setEnvironment(MomoApi::ENVIRONMENT_SANDBOX);

// Créer une api user
$uuid = Utilities::guidv4(); // Ou tout autre guuidv4 valide
$callbackHost = 'https://my-domain.com/callback';

$apiUser = $momo->sandbox()->createApiUser($uuid, $callbackHost);
echo "Api user created: $apiUser\n";
```

#### [Récupérer les informations d'un utilisateur](https://momodeveloper.mtn.com/docs/services/sandbox-provisioning-api/operations/get-v1_0-apiuser)

```php
$data = $momo->sandbox()->getApiUser($apiUser);
print_r($data);
// [
//      'providerCallbackHost' => 'https://my-domain.com/callback',
//      'targetEnvironment' => 'sandbox',
// ]
```

#### [Créer une api key](https://momodeveloper.mtn.com/docs/services/sandbox-provisioning-api/operations/post-v1_0-apiuser-apikey)

```php
$apiKey = $momo->sandbox()->createApiKey($apiUser);
echo "Api token created: $apiKey\n";
```

### Intéragir avec le produit collection

```php
$config = new \Lepresk\MomoApi\Collection\Config($apiUser, $apiKey, $callbackHost);
$momo->setupCollection($config);
```

### Obtenir un token oauth

```php
$token = $momo->collection()->getAccessToken();

echo $token->getAccessToken(); // Token
echo $token->getExpiresIn(); // Date d'expiration du token

```

#### Faire une requête requestToPay

```php
<?php

// Pour initier un paiement requestToPay
$request = new PaymentRequest(1000, 'EUR', 'ORDER-10', '46733123454', '', '');
$paymentId = $momo->collection()->requestToPay($request);
```

#### Vérifier le status d'une transaction

```php
<?php
// Vérifier le statut du paiement
$transaction = $momo->collection()->checkRequestStatus($paymentId);

echo $transaction->getStatus(); // Pour obtenir le statut de la transaction
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