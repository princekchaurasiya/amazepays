<?php declare(strict_types = 1);

// ftm-C:\xampp\htdocs\amazepays\app\Http\Controllers\Admin\B2bPortalController.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      '9bc029ada3b0f4cd87f11338c5a7ce9a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
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
      '2a01e8890cad76abbb743857dd3b2fe4' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => '__construct',
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
      'a8875c25cd3fa705ce68b591ca0ea242' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'shop',
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
      'bbb6f5ce774d4737b88fb744f6baef17' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'catalogManage',
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
      'c2ec7a2d98195b7b76b2ddd94b10db66' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'updateCatalog',
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
      'ef64e919fcb8214e16525735308928e8' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'priceList',
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
      '87a3b11a6064b118d449c054ff5fc86c' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'exportPriceList',
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
      '032f7b7dbc3696ecd2b2bebfcf4aa87a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'financialActivity',
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
      '08f2cb1edd846fc75a76ffc9b0614d4b' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'storeOrder',
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
      'd22d771ee61f793771852192b254a3cf' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'orders',
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
      'b46db83d94cf1980cab75e380e3589a0' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'team',
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
      '30f5c99a4600cd518d33fb489615e58c' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'wallet',
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
      'f89d52836dde081f65308a561869e760' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'storeWalletLoadRequest',
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
      '5377fa2e44401ba0cab1b770a55aebae' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'resolveTenant',
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
      '2149c3eee459d73e43dc33e7bae12808' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'billingdata' => 'App\\Data\\BillingData',
          'orderdata' => 'App\\Data\\OrderData',
          'insufficientbalanceexception' => 'App\\Exceptions\\InsufficientBalanceException',
          'ordercreationexception' => 'App\\Exceptions\\OrderCreationException',
          'walletfrozenexception' => 'App\\Exceptions\\WalletFrozenException',
          'b2bpricelistexport' => 'App\\Exports\\B2bPriceListExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'b2bplaceorderrequest' => 'App\\Http\\Requests\\B2b\\B2bPlaceOrderRequest',
          'submitwalletloadrequest' => 'App\\Http\\Requests\\Wallet\\SubmitWalletLoadRequest',
          'order' => 'App\\Models\\Order',
          'product' => 'App\\Models\\Product',
          'tenant' => 'App\\Models\\Tenant',
          'walletloadrequest' => 'App\\Models\\WalletLoadRequest',
          'b2bcatalogservice' => 'App\\Services\\B2b\\B2bCatalogService',
          'ordercreationservice' => 'App\\Services\\Order\\OrderCreationService',
          'walletloadrequestservice' => 'App\\Services\\Wallet\\WalletLoadRequestService',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'inertia' => 'Inertia\\Inertia',
          'response' => 'Inertia\\Response',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\B2bPortalController',
         'functionName' => 'tenantPayload',
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
    ),
    1 => 
    array (
      'C:\\xampp\\htdocs\\amazepays\\app\\Http\\Controllers\\Admin\\B2bPortalController.php' => '6d44941d64e74894cb1db0a00190b68f3a0fe6473b0f726f4cc0396e88cfb7c2',
    ),
  ),
));