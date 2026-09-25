<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // All stored dates are local theatre time; do not depend on the server's php.ini.
        date_default_timezone_set('Europe/Vienna');

        parent::boot();
    }
}
