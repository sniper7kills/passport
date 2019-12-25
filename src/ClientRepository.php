<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use RuntimeException;

class ClientRepository
{
    /**
     * Get a client by the given ID.
     *
     * @param  int  $id
     * @return \Laravel\Passport\Client|null
     */
    public function find($id)
    {
        $client = Passport::client();

        return $client->where($client->getKeyName(), $id)->first();
    }

    /**
     * Get an active client by the given ID.
     *
     * @param  int  $id
     * @return \Laravel\Passport\Client|null
     */
    public function findActive($id)
    {
        $client = $this->find($id);

        return $client && ! $client->revoked ? $client : null;
    }

    /**
     * Get a client instance for the given ID and user ID.
     *
     * @param  int  $clientId
     * @param  mixed  $user
     * @return \Laravel\Passport\Client|null
     */
    public function findForUser($clientId, $user)
    {
        $client = Passport::client();

        return $client
            ->where($client->getKeyName(), $clientId)
            ->whereHasMorph(
                'user',
                get_class($user),
                function (Builder $query) use ($user) {
                    $query->where($user->getKeyName(), $user->getKey());
                }
            )
            ->first();
    }

    /**
     * Get the client instances for the given user ID.
     *
     * @param  mixed  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($user)
    {
        return Passport::client()
            ->whereHasMorph(
                'user',
                get_class($user),
                function (Builder $query) use ($user) {
                    $query->where($user->getKeyName(), $user->getKey());
                }
            )
            ->orderBy('name', 'asc')->get();
    }

    /**
     * Get the active client instances for the given user ID.
     *
     * @param  mixed  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function activeForUser($user)
    {
        return $this->forUser($user)->reject(function ($client) {
            return $client->revoked;
        })->values();
    }

    /**
     * Get the personal access token client for the application.
     *
     * @return \Laravel\Passport\Client
     *
     * @throws \RuntimeException
     */
    public function personalAccessClient()
    {
        if (Passport::$personalAccessClientId) {
            return $this->find(Passport::$personalAccessClientId);
        }

        $client = Passport::personalAccessClient();

        if (! $client->exists()) {
            throw new RuntimeException('Personal access client not found. Please create one.');
        }

        return $client->orderBy($client->getKeyName(), 'desc')->first()->client;
    }

    /**
     * Store a new client.
     *
     * @param  int  $user
     * @param  string  $name
     * @param  string  $redirect
     * @param  bool  $personalAccess
     * @param  bool  $password
     * @param  bool  $confidential
     * @return \Laravel\Passport\Client
     */
    public function create($user, $name, $redirect, $personalAccess = false, $password = false, $confidential = true)
    {
        $client = Passport::client()->forceFill([
            'name' => $name,
            'secret' => ($confidential || $personalAccess) ? Str::random(40) : null,
            'redirect' => $redirect,
            'personal_access_client' => $personalAccess,
            'password_client' => $password,
            'revoked' => false,
        ])->user()->associate($user);

        $client->save();

        return $client;
    }

    /**
     * Store a new personal access token client.
     *
     * @param  mixed  $user
     * @param  string  $name
     * @param  string  $redirect
     * @return \Laravel\Passport\Client
     */
    public function createPersonalAccessClient($user, $name, $redirect)
    {
        return tap($this->create($user, $name, $redirect, true), function ($client) {
            $accessClient = Passport::personalAccessClient();
            $accessClient->client_id = $client->id;
            $accessClient->save();
        });
    }

    /**
     * Store a new password grant client.
     *
     * @param  mixed  $user
     * @param  string  $name
     * @param  string  $redirect
     * @return \Laravel\Passport\Client
     */
    public function createPasswordGrantClient($user, $name, $redirect)
    {
        return $this->create($user, $name, $redirect, false, true);
    }

    /**
     * Update the given client.
     *
     * @param  \Laravel\Passport\Client  $client
     * @param  string  $name
     * @param  string  $redirect
     * @return \Laravel\Passport\Client
     */
    public function update(Client $client, $name, $redirect)
    {
        $client->forceFill([
            'name' => $name, 'redirect' => $redirect,
        ])->save();

        return $client;
    }

    /**
     * Regenerate the client secret.
     *
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Client
     */
    public function regenerateSecret(Client $client)
    {
        $client->forceFill([
            'secret' => Str::random(40),
        ])->save();

        return $client;
    }

    /**
     * Determine if the given client is revoked.
     *
     * @param  int  $id
     * @return bool
     */
    public function revoked($id)
    {
        $client = $this->find($id);

        return is_null($client) || $client->revoked;
    }

    /**
     * Delete the given client.
     *
     * @param  \Laravel\Passport\Client  $client
     * @return void
     */
    public function delete(Client $client)
    {
        $client->tokens()->update(['revoked' => true]);

        $client->forceFill(['revoked' => true])->save();
    }
}
