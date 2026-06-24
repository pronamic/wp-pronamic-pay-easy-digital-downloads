<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/psr-4',
		]
	)
	->withPhpSets()
	->withTypeCoverageLevel( 0 )
	->withDeadCodeLevel( 0 )
	->withCodeQualityLevel( 0 );
