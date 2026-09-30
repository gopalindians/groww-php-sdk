<?php

namespace Groww\API\Resources;

use Groww\API\Exceptions\GrowwApiException;

class User extends Resource
{
    /**
     * GET /user/detail
     *
     * @throws GrowwApiException
     */
    public function detail(): array
    {
        $response = $this->client->get('/user/detail');
        return $this->extractPayload($response);
    }
}
