<?php declare(strict_types = 1);

// osfsl-C:/xampp/htdocs/amazepays/vendor/composer/../barryvdh/laravel-debugbar/src/Console/FindCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Fruitcake\LaravelDebugbar\Console\FindCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-940ddbb3bd1e0339b8949b423e204512322af4820d2b454c22995a0e2eba7482-8.3.30-6.70.0.0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'filename' => 'C:/xampp/htdocs/amazepays/vendor/composer/../barryvdh/laravel-debugbar/src/Console/FindCommand.php',
      ),
    ),
    'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
    'name' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
    'shortName' => 'FindCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 213,
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
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'debugbar:find
    {--utime= : Shows only requests after this micro timestamp}
    {--ip= : Filter by IP}
    {--method= : Filter by HTTP method (GET/POST/PUT/DELETE)}
    {--uri= : Filter by URI, eg. /admin/*, in fnmatch format}
    {--max=20 : Number of results to show}
    {--offset=0 : Offset of the results}
    {--issues : Only show requests with potential issues (applies defaults for threshold options)}
    {--min-queries= : Flag requests with at least this many queries (default: 50 with --issues)}
    {--min-duration= : Flag requests slower than this in ms (default: 1000 with --issues)}
    {--min-duplicates= : Flag requests with at least this many duplicate query groups (default: 2 with --issues)}
    \'',
          'attributes' => 
          array (
            'startLine' => 12,
            'endLine' => 23,
            'startTokenPos' => 41,
            'startFilePos' => 222,
            'endTokenPos' => 41,
            'endFilePos' => 941,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 23,
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
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'List the Debugbar Storage\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 50,
            'startFilePos' => 973,
            'endTokenPos' => 50,
            'endFilePos' => 999,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 57,
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
            'startLine' => 26,
            'endLine' => 26,
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
        'startLine' => 26,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'currentClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'aliasName' => NULL,
      ),
      'detectIssues' => 
      array (
        'name' => 'detectIssues',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 35,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'minQueries' => 
          array (
            'name' => 'minQueries',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 48,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'minDuration' => 
          array (
            'name' => 'minDuration',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'float',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 66,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'minDuplicates' => 
          array (
            'name' => 'minDuplicates',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 87,
            'endColumn' => 105,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return list<string>
 */',
        'startLine' => 139,
        'endLine' => 192,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'currentClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'aliasName' => NULL,
      ),
      'countDuplicateGroups' => 
      array (
        'name' => 'countDuplicateGroups',
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
            'startLine' => 194,
            'endLine' => 194,
            'startColumn' => 43,
            'endColumn' => 59,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 194,
        'endLine' => 212,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Fruitcake\\LaravelDebugbar\\Console',
        'declaringClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'implementingClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
        'currentClassName' => 'Fruitcake\\LaravelDebugbar\\Console\\FindCommand',
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