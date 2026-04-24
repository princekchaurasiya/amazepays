<?php declare(strict_types = 1);

// odsl-C:\xampp\htdocs\amazepays\app\Console\Commands\CleanupTestPaymentData.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Console\Commands\CleanupTestPaymentData
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.0-8.3.30-0da71222edbd18b94b5fe7dce102b89d13f86bdc6a60315dadaa92faacceae1e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'filename' => 'C:/xampp/htdocs/amazepays/app/Console/Commands/CleanupTestPaymentData.php',
      ),
    ),
    'namespace' => 'App\\Console\\Commands',
    'name' => 'App\\Console\\Commands\\CleanupTestPaymentData',
    'shortName' => 'CleanupTestPaymentData',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 285,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'signature' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'implementingClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'payment:cleanup-test-data 
                            {--force : Force deletion without confirmation}
                            {--user-id= : Delete data for specific user ID only}
                            {--order-id= : Delete data for specific order ID only}
                            {--include-tokens : Also delete expired API tokens (default: skip tokens)}\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 26,
            'startTokenPos' => 70,
            'startFilePos' => 477,
            'endTokenPos' => 70,
            'endFilePos' => 847,
          ),
        ),
        'docComment' => '/**
 * The name and signature of the console command.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 104,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'implementingClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Clean up test order and payment data for analysis\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 81,
            'startFilePos' => 962,
            'endTokenPos' => 81,
            'endFilePos' => 1012,
          ),
        ),
        'docComment' => '/**
 * The console command description.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 81,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Execute the console command.
 *
 * @return int
 */',
        'startLine' => 40,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'implementingClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'currentClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'aliasName' => NULL,
      ),
      'getDeletionStats' => 
      array (
        'name' => 'getDeletionStats',
        'parameters' => 
        array (
          'userId' => 
          array (
            'name' => 'userId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 699,
                'startFilePos' => 4352,
                'endTokenPos' => 699,
                'endFilePos' => 4355,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 39,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'orderId' => 
          array (
            'name' => 'orderId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 706,
                'startFilePos' => 4369,
                'endTokenPos' => 706,
                'endFilePos' => 4372,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 55,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'includeTokens' => 
          array (
            'name' => 'includeTokens',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 713,
                'startFilePos' => 4392,
                'endTokenPos' => 713,
                'endFilePos' => 4396,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 72,
            'endColumn' => 93,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get statistics about data to be deleted
 */',
        'startLine' => 144,
        'endLine' => 206,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'implementingClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'currentClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'aliasName' => NULL,
      ),
      'deletePaymentData' => 
      array (
        'name' => 'deletePaymentData',
        'parameters' => 
        array (
          'userId' => 
          array (
            'name' => 'userId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 211,
                'endLine' => 211,
                'startTokenPos' => 1244,
                'startFilePos' => 6859,
                'endTokenPos' => 1244,
                'endFilePos' => 6862,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 40,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'orderId' => 
          array (
            'name' => 'orderId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 211,
                'endLine' => 211,
                'startTokenPos' => 1251,
                'startFilePos' => 6876,
                'endTokenPos' => 1251,
                'endFilePos' => 6879,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 56,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'includeTokens' => 
          array (
            'name' => 'includeTokens',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 211,
                'endLine' => 211,
                'startTokenPos' => 1258,
                'startFilePos' => 6899,
                'endTokenPos' => 1258,
                'endFilePos' => 6903,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 73,
            'endColumn' => 94,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Delete payment data in correct order (respecting foreign keys)
 */',
        'startLine' => 211,
        'endLine' => 284,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'implementingClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'currentClassName' => 'App\\Console\\Commands\\CleanupTestPaymentData',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));