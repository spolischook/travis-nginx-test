<?php
return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(['ordered_use', 'short_array_syntax', 'unused_use',])
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->in([__DIR__.'/../../package', __DIR__.'/../../application', __DIR__.'/../../tool'])
            ->notPath('vendor')
    )
;
