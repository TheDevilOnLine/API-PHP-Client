<?php

/**
 * The Webshopapp Api Rate limiter interface
 */
interface WebshopappApiClientRateLimiter {

    /**
     * @param $api
     */
    public function preSendRequest($api);
}