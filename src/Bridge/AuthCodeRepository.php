<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    use FormatsScopesForStorage;

    /**
     * {@inheritdoc}
     */
    public function getNewAuthCode()
    {
        return new AuthCode;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $provider = Auth::guard()->getProvider();
        $user = $provider->retrieveById($authCodeEntity->getUserIdentifier());
        $attributes = [
            'id' => $authCodeEntity->getIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'user_type' => $user->getMorphClass(),
            'user_id' => $user->getKey(),
            'scopes' => $this->formatScopesForStorage($authCodeEntity->getScopes()),
            'revoked' => false,
            'expires_at' => $authCodeEntity->getExpiryDateTime(),
        ];

        Passport::authCode()->setRawAttributes($attributes)->save();
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAuthCode($codeId)
    {
        Passport::authCode()->where('id', $codeId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthCodeRevoked($codeId)
    {
        return Passport::authCode()->where('id', $codeId)->where('revoked', 1)->exists();
    }
}
