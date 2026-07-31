<?php

use Licentra\LicentraLaravel\LicentraLaravel;

if (! function_exists('licentra')) {
    /**
     * Get Licentra client SDK instance.
     */
    function licentra(): LicentraLaravel
    {
        /** @var LicentraLaravel */
        return app(LicentraLaravel::class);
    }
}
