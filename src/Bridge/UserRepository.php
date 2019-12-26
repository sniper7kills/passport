<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Hashing\HashManager;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use RuntimeException;

class UserRepository implements UserRepositoryInterface
{
    /**
     * The hasher implementation.
     *
     * @var \Illuminate\Hashing\HashManager
     */
    protected $hasher;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Hashing\HashManager  $hasher
     * @return void
     */
    public function __construct(HashManager $hasher)
    {
        $this->hasher = $hasher->driver();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $provider = Auth::guard()->getProvider();

        if (!method_exists($provider, 'findForPassport')) {
            if (method_exists($provider->getModel(), 'findForPassport')) {
                $user = (new $provider->getModel())->findForPassport($username);
            } else {
                $user = (new $provider->getModel())->where('email', $username)->first();
            }
        } else{
            $user = $provider->findForPassport($username);
        }

        if (! $user) {
            return;
        } elseif (method_exists($user, 'validateForPassportPasswordGrant')) {
            if (! $user->validateForPassportPasswordGrant($password)) {
                return;
            }
        } elseif (! $this->hasher->check($password, $user->getAuthPassword())) {
            return;
        }

        return new User($user->getAuthIdentifier());
    }
}
