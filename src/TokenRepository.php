<?php

namespace Laravel\Passport;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class TokenRepository
{
    /**
     * Creates a new Access Token.
     *
     * @param  array  $attributes
     * @return \Laravel\Passport\Token
     */
    public function create($attributes)
    {
        return Passport::token()->create($attributes);
    }

    /**
     * Get a token by the given ID.
     *
     * @param  string  $id
     * @return \Laravel\Passport\Token
     */
    public function find($id)
    {
        return Passport::token()->where('id', $id)->first();
    }

    /**
     * Get a token by the given user ID and token ID.
     *
     * @param  string  $id
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Laravel\Passport\Token|null
     */
    public function findForUser($id, $user)
    {
        return Passport::token()->where('id', $id)
            ->whereHasMorph(
                'user',
                get_class($user),
                function (Builder $query) use ($user) {
                    $query->where($user->getKeyName(), $user->getKey());
                }
            )->first();
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($user)
    {
        return Passport::token()
            ->whereHasMorph(
                'user',
                get_class($user),
                function (Builder $query) use ($user) {
                    $query->where($user->getKeyName(), $user->getKey());
                }
            )
            ->get();
    }

    /**
     * Get a valid token instance for the given user and client.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Token|null
     */
    public function getValidToken($user, $client)
    {
        return $client->tokens()
                    ->whereHasMorph(
                        'user',
                        get_class($user),
                        function (Builder $query) use ($user) {
                            $query->where($user->getKeyName(), $user->getKey());
                        }
                    )
                    ->where('revoked', 0)
                    ->where('expires_at', '>', Carbon::now())
                    ->first();
    }

    /**
     * Store the given token instance.
     *
     * @param  \Laravel\Passport\Token  $token
     * @return void
     */
    public function save(Token $token)
    {
        $token->save();
    }

    /**
     * Revoke an access token.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeAccessToken($id)
    {
        return Passport::token()->where('id', $id)->update(['revoked' => true]);
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param  string  $id
     * @return bool
     */
    public function isAccessTokenRevoked($id)
    {
        if ($token = $this->find($id)) {
            return $token->revoked;
        }

        return true;
    }

    /**
     * Find a valid token for the given user and client.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Token|null
     */
    public function findValidToken($user, $client)
    {
        return $client->tokens()
            ->whereHasMorph(
                'user',
                get_class($user),
                function (Builder $query) use ($user) {
                    $query->where($user->getKeyName(), $user->getKey());
                }
            )
            ->where('revoked', 0)
            ->where('expires_at', '>', Carbon::now())
            ->latest('expires_at')
            ->first();
    }
}
