<?php

namespace App\Models;

class NewAccessToken
{
    /**
     * The access token instance.
     *
     * @var \App\Models\PersonalAccessToken
     */
    public $accessToken;

    /**
     * The plain-text token.
     *
     * @var string
     */
    public $plainTextToken;

    /**
     * Create a new access token result.
     *
     * @param  \App\Models\PersonalAccessToken  $accessToken
     * @param  string  $plainTextToken
     * @return void
     */
    public function __construct(PersonalAccessToken $accessToken, string $plainTextToken)
    {
        $this->accessToken = $accessToken;
        $this->plainTextToken = $plainTextToken;
    }

    /**
     * Get the access token instance.
     *
     * @return \App\Models\PersonalAccessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Get the plain-text token.
     *
     * @return string
     */
    public function getPlainTextToken()
    {
        return $this->plainTextToken;
    }

    /**
     * Get the string representation of the token.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->plainTextToken;
    }
}
