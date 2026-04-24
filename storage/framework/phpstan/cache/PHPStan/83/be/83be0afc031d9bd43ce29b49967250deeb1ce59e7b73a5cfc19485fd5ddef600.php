<?php declare(strict_types = 1);

// osfsl-C:/xampp/htdocs/amazepays/vendor/composer/../laravel/dusk/src/Console/ChromeDriverCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Laravel\Dusk\Console\ChromeDriverCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-74d10ab52c739532277b375d3d5baca229cddf92442d5e5b66dbbd16dd54b429-8.3.30-6.70.0.0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'filename' => 'C:/xampp/htdocs/amazepays/vendor/composer/../laravel/dusk/src/Console/ChromeDriverCommand.php',
      ),
    ),
    'namespace' => 'Laravel\\Dusk\\Console',
    'name' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
    'shortName' => 'ChromeDriverCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @copyright Originally created by Jonas Staudenmeir: https://github.com/staudenmeir/dusk-updater
 */',
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'Symfony\\Component\\Console\\Attribute\\AsCommand',
        'isRepeated' => false,
        'arguments' => 
        array (
          'name' => 
          array (
            'code' => '\'dusk:chrome-driver\'',
            'attributes' => 
            array (
              'startLine' => 18,
              'endLine' => 18,
              'startTokenPos' => 60,
              'startFilePos' => 431,
              'endTokenPos' => 60,
              'endFilePos' => 450,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 18,
    'endLine' => 346,
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
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'dusk:chrome-driver {version?}
                    {--all : Install a ChromeDriver binary for every OS}
                    {--detect : Detect the installed Chrome / Chromium version}
                    {--proxy= : The proxy to download the binary through (example: "tcp://127.0.0.1:9000")}
                    {--ssl-no-verify : Bypass SSL certificate verification when installing through a proxy}\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 30,
            'startTokenPos' => 82,
            'startFilePos' => 621,
            'endTokenPos' => 82,
            'endFilePos' => 1020,
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
        'startLine' => 26,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 109,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Install the ChromeDriver binary\'',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 93,
            'startFilePos' => 1135,
            'endTokenPos' => 93,
            'endFilePos' => 1167,
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
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 63,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'legacyVersions' => 
      array (
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'name' => 'legacyVersions',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[43 => \'2.20\', 44 => \'2.20\', 45 => \'2.20\', 46 => \'2.21\', 47 => \'2.21\', 48 => \'2.21\', 49 => \'2.22\', 50 => \'2.22\', 51 => \'2.23\', 52 => \'2.24\', 53 => \'2.26\', 54 => \'2.27\', 55 => \'2.28\', 56 => \'2.29\', 57 => \'2.29\', 58 => \'2.31\', 59 => \'2.32\', 60 => \'2.33\', 61 => \'2.34\', 62 => \'2.35\', 63 => \'2.36\', 64 => \'2.37\', 65 => \'2.38\', 66 => \'2.40\', 67 => \'2.41\', 68 => \'2.42\', 69 => \'2.44\']',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 72,
            'startTokenPos' => 104,
            'startFilePos' => 1289,
            'endTokenPos' => 295,
            'endFilePos' => 1889,
          ),
        ),
        'docComment' => '/**
 * The legacy versions for ChromeDriver.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 6,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'directory' => 
      array (
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'name' => 'directory',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '__DIR__ . \'/../../bin/\'',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 306,
            'startFilePos' => 1996,
            'endTokenPos' => 308,
            'endFilePos' => 2016,
          ),
        ),
        'docComment' => '/**
 * Path to the bin directory.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 49,
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
 * @return void
 */',
        'startLine' => 86,
        'endLine' => 107,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'version' => 
      array (
        'name' => 'version',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the desired ChromeDriver version.
 *
 * @return string
 */',
        'startLine' => 114,
        'endLine' => 142,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'latestVersion' => 
      array (
        'name' => 'latestVersion',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the latest stable ChromeDriver version.
 *
 * @return string
 */',
        'startLine' => 149,
        'endLine' => 155,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'detectChromeVersion' => 
      array (
        'name' => 'detectChromeVersion',
        'parameters' => 
        array (
          'os' => 
          array (
            'name' => 'os',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 44,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Detect the installed Chrome / Chromium major version.
 *
 * @param  string  $os
 * @return int|bool
 */',
        'startLine' => 163,
        'endLine' => 182,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'download' => 
      array (
        'name' => 'download',
        'parameters' => 
        array (
          'version' => 
          array (
            'name' => 'version',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 191,
            'endLine' => 191,
            'startColumn' => 33,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'os' => 
          array (
            'name' => 'os',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 191,
            'endLine' => 191,
            'startColumn' => 43,
            'endColumn' => 45,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Download the ChromeDriver archive.
 *
 * @param  string  $version
 * @param  string  $os
 * @return string
 */',
        'startLine' => 191,
        'endLine' => 211,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'extract' => 
      array (
        'name' => 'extract',
        'parameters' => 
        array (
          'archive' => 
          array (
            'name' => 'archive',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 221,
            'endLine' => 221,
            'startColumn' => 32,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract the ChromeDriver binary from the archive and delete the archive.
 *
 * @param  string  $archive
 * @return string
 *
 * @throws \\Exception
 */',
        'startLine' => 221,
        'endLine' => 250,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'rename' => 
      array (
        'name' => 'rename',
        'parameters' => 
        array (
          'binary' => 
          array (
            'name' => 'binary',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 259,
            'endLine' => 259,
            'startColumn' => 31,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'os' => 
          array (
            'name' => 'os',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 259,
            'endLine' => 259,
            'startColumn' => 40,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Rename the ChromeDriver binary and make it executable.
 *
 * @param  string  $binary
 * @param  string  $os
 * @return void
 */',
        'startLine' => 259,
        'endLine' => 270,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'fetchChromeVersionFromUrl' => 
      array (
        'name' => 'fetchChromeVersionFromUrl',
        'parameters' => 
        array (
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 277,
            'endLine' => 277,
            'startColumn' => 50,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the Chrome version from URL.
 *
 * @return string
 */',
        'startLine' => 277,
        'endLine' => 282,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'resolveChromeVersionsPerMilestone' => 
      array (
        'name' => 'resolveChromeVersionsPerMilestone',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the Chrome versions per milestone.
 *
 * @return array
 */',
        'startLine' => 289,
        'endLine' => 294,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'resolveChromeDriverDownloadUrl' => 
      array (
        'name' => 'resolveChromeDriverDownloadUrl',
        'parameters' => 
        array (
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 55,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'os' => 
          array (
            'name' => 'os',
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
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 72,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the download URL.
 *
 * @return string
 *
 * @throws \\Exception
 */',
        'startLine' => 303,
        'endLine' => 321,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'aliasName' => NULL,
      ),
      'getUrl' => 
      array (
        'name' => 'getUrl',
        'parameters' => 
        array (
          'url' => 
          array (
            'name' => 'url',
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
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the contents of a URL using the \'proxy\' and \'ssl-no-verify\' command options.
 *
 * @return string
 *
 * @throws \\Exception
 */',
        'startLine' => 330,
        'endLine' => 345,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Dusk\\Console',
        'declaringClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'implementingClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
        'currentClassName' => 'Laravel\\Dusk\\Console\\ChromeDriverCommand',
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