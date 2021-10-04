Sylius Payzen Bundle by Antilop
===========

Sylius PayZen bundle via Payum


### Usage & install

1. Install this bundle:

```bash
$ composer require antilop/sylius-payzen-bundle
```

2. Configure new payment method in Sylius Admin

### Complementary documentation

- [Kiboko/PayzenBundle](https://github.com/kiboko-labs/payzen-bundle)


# Installation manuelle (old)

* Copie le contenu de `src` dans `src/Antilop/Bundle/SyliusPayzenBundle`

* Déclarer le bundle dans `config/bundles.php` :

```
Antilop\Bundle\SyliusPayzenBundle\AntilopSyliusPayzenBundle::class => ['all' => true],
```

 
* Ajouter dans le fichier `composer.json` dans la partie `psr-4` :
```
"Antilop\\Bundle\\": "src/Antilop/Bundle/",
```

### Build archive

Install `jq` package.

Run `make build`

In your project, you can add the plugin with 

```
    "repositories": {
        "payzen-integration": {
            "type": "package",
            "package": {
                "name": "antilop/sylius-payzen-bundle",
                "version": "0.1.0",
                "dist": {
                    "url": "{PATH_TO_YOUR_ARCHIVE}/sylius-payzenbundle-0.1.0.tar",
                    "type": "tar"
                }
            }
        }
    }
```

And run `composer require antilop/sylius-payzen-bundle`
