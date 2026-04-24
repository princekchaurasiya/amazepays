<?php declare(strict_types = 1);

// ftm-C:\xampp\htdocs\amazepays\app\Services\Order\OrderCreationService.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      '8e2c4eb8959ca2af57b493ab66addf68' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services\\Order',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'pricingresult' => 'App\\Data\\PricingResult',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'user' => 'App\\Models\\User',
          'pricingservice' => 'App\\Services\\Pricing\\PricingService',
          'walletservice' => 'App\\Services\\Wallet\\WalletService',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
        ),
         'className' => 'App\\Services\\Order\\OrderCreationService',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '383f1eb9f9e047881566405a7086339f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services\\Order',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'pricingresult' => 'App\\Data\\PricingResult',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'user' => 'App\\Models\\User',
          'pricingservice' => 'App\\Services\\Pricing\\PricingService',
          'walletservice' => 'App\\Services\\Wallet\\WalletService',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
        ),
         'className' => 'App\\Services\\Order\\OrderCreationService',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services\\Order',
           'uses' => 
          array (
            'billingdata' => 'App\\Data\\BillingData',
            'orderdata' => 'App\\Data\\OrderData',
            'pricingresult' => 'App\\Data\\PricingResult',
            'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
            'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
            'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
            'order' => 'App\\Models\\Order',
            'product' => 'App\\Models\\Product',
            'tenant' => 'App\\Models\\Tenant',
            'user' => 'App\\Models\\User',
            'pricingservice' => 'App\\Services\\Pricing\\PricingService',
            'walletservice' => 'App\\Services\\Wallet\\WalletService',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
          ),
           'className' => 'App\\Services\\Order\\OrderCreationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '6121a8f37245122d96d57cb7e2f7f964' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services\\Order',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'pricingresult' => 'App\\Data\\PricingResult',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'user' => 'App\\Models\\User',
          'pricingservice' => 'App\\Services\\Pricing\\PricingService',
          'walletservice' => 'App\\Services\\Wallet\\WalletService',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
        ),
         'className' => 'App\\Services\\Order\\OrderCreationService',
         'functionName' => 'create',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services\\Order',
           'uses' => 
          array (
            'billingdata' => 'App\\Data\\BillingData',
            'orderdata' => 'App\\Data\\OrderData',
            'pricingresult' => 'App\\Data\\PricingResult',
            'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
            'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
            'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
            'order' => 'App\\Models\\Order',
            'product' => 'App\\Models\\Product',
            'tenant' => 'App\\Models\\Tenant',
            'user' => 'App\\Models\\User',
            'pricingservice' => 'App\\Services\\Pricing\\PricingService',
            'walletservice' => 'App\\Services\\Wallet\\WalletService',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
          ),
           'className' => 'App\\Services\\Order\\OrderCreationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'ee4a52e55233465df019f286d4e24c2d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services\\Order',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'pricingresult' => 'App\\Data\\PricingResult',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'user' => 'App\\Models\\User',
          'pricingservice' => 'App\\Services\\Pricing\\PricingService',
          'walletservice' => 'App\\Services\\Wallet\\WalletService',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
        ),
         'className' => 'App\\Services\\Order\\OrderCreationService',
         'functionName' => 'resolveTenantForOrder',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services\\Order',
           'uses' => 
          array (
            'billingdata' => 'App\\Data\\BillingData',
            'orderdata' => 'App\\Data\\OrderData',
            'pricingresult' => 'App\\Data\\PricingResult',
            'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
            'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
            'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
            'order' => 'App\\Models\\Order',
            'product' => 'App\\Models\\Product',
            'tenant' => 'App\\Models\\Tenant',
            'user' => 'App\\Models\\User',
            'pricingservice' => 'App\\Services\\Pricing\\PricingService',
            'walletservice' => 'App\\Services\\Wallet\\WalletService',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
          ),
           'className' => 'App\\Services\\Order\\OrderCreationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '41a1464b74d4a93e5f851d1b43da336d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services\\Order',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'pricingresult' => 'App\\Data\\PricingResult',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'user' => 'App\\Models\\User',
          'pricingservice' => 'App\\Services\\Pricing\\PricingService',
          'walletservice' => 'App\\Services\\Wallet\\WalletService',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
        ),
         'className' => 'App\\Services\\Order\\OrderCreationService',
         'functionName' => 'verifyPaymentAmount',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services\\Order',
           'uses' => 
          array (
            'billingdata' => 'App\\Data\\BillingData',
            'orderdata' => 'App\\Data\\OrderData',
            'pricingresult' => 'App\\Data\\PricingResult',
            'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
            'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
            'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
            'order' => 'App\\Models\\Order',
            'product' => 'App\\Models\\Product',
            'tenant' => 'App\\Models\\Tenant',
            'user' => 'App\\Models\\User',
            'pricingservice' => 'App\\Services\\Pricing\\PricingService',
            'walletservice' => 'App\\Services\\Wallet\\WalletService',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
          ),
           'className' => 'App\\Services\\Order\\OrderCreationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '8d45058cf44ab1c669c38edc35bc3820' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services\\Order',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'pricingresult' => 'App\\Data\\PricingResult',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'user' => 'App\\Models\\User',
          'pricingservice' => 'App\\Services\\Pricing\\PricingService',
          'walletservice' => 'App\\Services\\Wallet\\WalletService',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
        ),
         'className' => 'App\\Services\\Order\\OrderCreationService',
         'functionName' => 'enforcePurchaseLimits',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services\\Order',
           'uses' => 
          array (
            'billingdata' => 'App\\Data\\BillingData',
            'orderdata' => 'App\\Data\\OrderData',
            'pricingresult' => 'App\\Data\\PricingResult',
            'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
            'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
            'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
            'order' => 'App\\Models\\Order',
            'product' => 'App\\Models\\Product',
            'tenant' => 'App\\Models\\Tenant',
            'user' => 'App\\Models\\User',
            'pricingservice' => 'App\\Services\\Pricing\\PricingService',
            'walletservice' => 'App\\Services\\Wallet\\WalletService',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
          ),
           'className' => 'App\\Services\\Order\\OrderCreationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'e742dc20cd99919f8c864c401ed3f660' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services\\Order',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'pricingresult' => 'App\\Data\\PricingResult',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'user' => 'App\\Models\\User',
          'pricingservice' => 'App\\Services\\Pricing\\PricingService',
          'walletservice' => 'App\\Services\\Wallet\\WalletService',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
        ),
         'className' => 'App\\Services\\Order\\OrderCreationService',
         'functionName' => 'generateOrderNumber',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services\\Order',
           'uses' => 
          array (
            'billingdata' => 'App\\Data\\BillingData',
            'orderdata' => 'App\\Data\\OrderData',
            'pricingresult' => 'App\\Data\\PricingResult',
            'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
            'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
            'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
            'order' => 'App\\Models\\Order',
            'product' => 'App\\Models\\Product',
            'tenant' => 'App\\Models\\Tenant',
            'user' => 'App\\Models\\User',
            'pricingservice' => 'App\\Services\\Pricing\\PricingService',
            'walletservice' => 'App\\Services\\Wallet\\WalletService',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
          ),
           'className' => 'App\\Services\\Order\\OrderCreationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      'C:\\xampp\\htdocs\\amazepays\\app\\Services\\Order\\OrderCreationService.php' => '1ef1d681488840b4e33f717a99c56db6649ea6f90c2d20c9d44c6a24f46efe22',
    ),
  ),
));