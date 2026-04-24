<?php declare(strict_types = 1);

// osfsl-C:/xampp/htdocs/amazepays/vendor/composer/../barryvdh/laravel-debugbar/src/Console/QueriesCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Fruitcake\LaravelDebugbar\Console\QueriesCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-9880e4a54c7f99ef8e8f40c777739efcea89eda75ae6d7aa732fe7f0ba4795b0-8.3.30-6.70.0.0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'filename' => 'C:/xampp/htdocs/amazepays/vendor/composer/../barryvdh/laravel-debugbar/src/Console/QueriesCommand.php',
      ),
    ),
    'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
    'name' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
    'shortName' => 'QueriesCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 258,
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
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'debugbar:queries
    {id : The id of the request to show, or "latest" to show the latest}
    {--statement= : The index of the statement to show}
    {--explain : Run EXPLAIN on the statement (requires --statement)}
    {--result : Run the query and show results (requires --statement)}
    \'',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 18,
            'startTokenPos' => 46,
            'startFilePos' => 272,
            'endTokenPos' => 46,
            'endFilePos' => 564,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 13,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 6,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Shows Debugbar Queries from a specific request\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 55,
            'startFilePos' => 596,
            'endTokenPos' => 55,
            'endFilePos' => 643,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 78,
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
          'debugbar' => 
          array (
            'name' => 'debugbar',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Fruitcake\\LaravelDebugbar\\LaravelDebugbar',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 28,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'currentClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'aliasName' => NULL,
      ),
      'showSummary' => 
      array (
        'name' => 'showSummary',
        'parameters' => 
        array (
          'queries' => 
          array (
            'name' => 'queries',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 72,
        'endLine' => 145,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'currentClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'aliasName' => NULL,
      ),
      'showStatementDetail' => 
      array (
        'name' => 'showStatementDetail',
        'parameters' => 
        array (
          'statements' => 
          array (
            'name' => 'statements',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 42,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'index' => 
          array (
            'name' => 'index',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 61,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 147,
        'endLine' => 187,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'currentClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'aliasName' => NULL,
      ),
      'runExplain' => 
      array (
        'name' => 'runExplain',
        'parameters' => 
        array (
          'stmt' => 
          array (
            'name' => 'stmt',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 189,
        'endLine' => 215,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'currentClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'aliasName' => NULL,
      ),
      'runResult' => 
      array (
        'name' => 'runResult',
        'parameters' => 
        array (
          'stmt' => 
          array (
            'name' => 'stmt',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 32,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 217,
        'endLine' => 247,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'currentClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'aliasName' => NULL,
      ),
      'truncateSql' => 
      array (
        'name' => 'truncateSql',
        'parameters' => 
        array (
          'sql' => 
          array (
            'name' => 'sql',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 249,
            'endLine' => 249,
            'startColumn' => 34,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'max' => 
          array (
            'name' => 'max',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 249,
            'endLine' => 249,
            'startColumn' => 47,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 249,
        'endLine' => 256,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
        'currentClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\QueriesCommand',
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