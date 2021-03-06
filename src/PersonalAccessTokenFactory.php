<?php

namespace Laravel\Passport;

use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class PersonalAccessTokenFactory
{
    /**
     * The authorization server instance.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * The client repository instance.
     *
     * @var \Laravel\Passport\ClientRepository
     */
    protected $clients;

    /**
     * The token repository instance.
     *
     * @var \Laravel\Passport\TokenRepository
     */
    protected $tokens;

    /**
     * The JWT token parser instance.
     *
     * @var \Lcobucci\JWT\Parser
     */
    protected $jwt;

    /**
     * Create a new personal access token factory instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @param  \Lcobucci\JWT\Parser  $jwt
     * @return void
     */
    public function __construct(AuthorizationServer $server,
                                ClientRepository $clients,
                                TokenRepository $tokens,
                                JwtParser $jwt)
    {
        $this->jwt = $jwt;
        $this->tokens = $tokens;
        $this->server = $server;
        $this->clients = $clients;
    }

    /**
     * Create a new personal access token.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  string  $name
     * @param  array  $scopes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function make($user, $name, array $scopes = [])
    {
        $response = $this->dispatchRequestToAuthorizationServer(
            $this->createRequest($this->clients->personalAccessClient(), $user, $scopes)
        );

        $token = tap($this->findAccessToken($response), function ($token) use ($user, $name) {
            $this->tokens->save($token->forceFill([
                'user_type' => $user->getMorphClass(),
                'user_id' => $user->getKey(),
                'name' => $name,
            ]));
        });

        return new PersonalAccessTokenResult(
            $response['access_token'], $token
        );
    }

    /**
     * Create a request instance for the given client.
     *
     * @param  \Laravel\Passport\Client  $client
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  array  $scopes
     * @return \Zend\Diactoros\ServerRequest
     */
    protected function createRequest($client, $user, array $scopes)
    {
        return (new ServerRequest)->withParsedBody([
            'grant_type' => 'personal_access',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            //TODO: find a better way to do this;
            // possibly make a pull request to thephpleague/oauth2-server
            // adding meta-data support and storing the class in the meta-data
            'user_id' => json_encode(['authId'=>$user->getAuthIdentifier(),'class'=>$user->getMorphClass()]),
            'scope' => implode(' ', $scopes),
        ]);
    }

    /**
     * Dispatch the given request to the authorization server.
     *
     * @param  \Zend\Diactoros\ServerRequest  $request
     * @return array
     */
    protected function dispatchRequestToAuthorizationServer(ServerRequest $request)
    {
        return json_decode($this->server->respondToAccessTokenRequest(
            $request, new Response
        )->getBody()->__toString(), true);
    }

    /**
     * Get the access token instance for the parsed response.
     *
     * @param  array  $response
     * @return \Laravel\Passport\Token
     */
    protected function findAccessToken(array $response)
    {
        return $this->tokens->find(
            $this->jwt->parse($response['access_token'])->getClaim('jti')
        );
    }
}
