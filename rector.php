<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

/*
 * @see https://getrector.com/documentation
 * @see https://getrector.com/documentation/integration-to-new-project
 * @see https://getrector.com/blog/5-common-mistakes-in-rector-config-and-how-to-avoid-them
 */

return RectorConfig::configure()
	->withPaths([
		__DIR__.'/config',
		__DIR__.'/public',
		__DIR__.'/src',
		__DIR__.'/tests',
	])
	// Ignore files
	// https://getrector.com/documentation/ignoring-rules-or-paths
	->withSkip([
		__DIR__.'/migrations',
		__DIR__.'/src/Entity/ResetPasswordRequest.php',
	])
	// Register rules
	->withRules([
		InlineConstructorDefaultToPropertyRector::class,
		AddVoidReturnTypeWhereNoReturnRector::class,
		RenameParamToMatchTypeRector::class,
	])
	->withPhpSets(php82: true)
	// Upgrade annotations to attributes
	// https://getrector.com/documentation/set-lists
	->withAttributesSets(symfony: true, doctrine: true, phpunit: true, sensiolabs: true)
	// Use code quality sets
	->withPreparedSets(deadCode: true, codeQuality: true, privatization: true, typeDeclarations: true)
	// Naming can be used temporarily and code must be reverted for:
	//   - src/entity folder
	//   - src/Service/FlysystemService.php
	// This is equivalent to rules:
	//   - RenameParamToMatchTypeRector
	//   - RenameVariableToMatchNewTypeRector
	// ->withPreparedSets(naming: true)
;
