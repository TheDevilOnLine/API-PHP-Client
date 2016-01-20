<?php

/**
 * This Rate Limiter waits when a limit is reached, keeping a pre-set number of api requests unused
 */
class WebshopappApiClientSimpleRateLimiter implements WebshopappApiClientRateLimiter {

    private $apiCallMargin;

    public function __construct($apiCallMargin = 25){
        $this->apiCallMargin = $apiCallMargin;
    }

    public function preSendRequest($api) {
        $headers = $api->getLastHeaders();

        if($headers === null) {
            return;
        }else{
            $rateLimits = array();
            $rateLimits[0] = new StdClass();
            $rateLimits[1] = new StdClass();
            $rateLimits[2] = new StdClass();

            $headerLines = explode("\n", $headers);
            foreach($headerLines as $headerLine) {
                $headerParts = explode(":", $headerLine,2);

                if(isset($headerParts[1])) {
                    if(strpos($headerParts[0],"X-RateLimit-") === 0) {
                        $rateLimitType = strtolower(substr($headerParts[0],strlen("X-RateLimit-")));
                        list(
                            $rateLimits[0]->{$rateLimitType},
                            $rateLimits[1]->{$rateLimitType},
                            $rateLimits[2]->{$rateLimitType}) = explode("/",trim($headerParts[1]));
                    }
                }
            }

            $rateLimits[0]->totaltime = 300;
            $rateLimits[1]->totaltime = 3600;
            $rateLimits[2]->totaltime = 86400;

            // Daily rate limits are currently ignored
            unset($rateLimits[2]);

            $waitingTime = 0;
            foreach($rateLimits as $rateLimit) {
                if($rateLimit->remaining <= $this->apiCallMargin) {

                    $waitingTime = $rateLimit->reset;
                    if($rateLimit->reset > $rateLimit->totaltime - 5) {
                        // When an api requests gets executed slightly after the limit resets there is a chance
                        // the seoshop api will return a new reset time, but without updating the remaining request
                        // count. This causes this code to sleep for the entire limit-duration

                        // As a workaround we assume we can't have run out of requests in the first 5 seconds of a limit
                        // window, and sleep for no more than 1 second
                        $waitingTime = 1;
                    }
                }
            }

            if($waitingTime > 0) {
                sleep($waitingTime +1);
            }
        }
    }
}