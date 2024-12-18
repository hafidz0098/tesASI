<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class MyClient extends Model
{
    protected $fillable = [
        'name', 'slug', 'is_project', 'self_capture', 'client_prefix', 'client_logo',
        'address', 'phone_number', 'city', 'created_at', 'updated_at', 'deleted_at'
    ];

    private static $redisKeyPrefix = 'client:';

    public static function redisKey($slug)
    {
        return self::$redisKeyPrefix . $slug;
    }

    public static function storeInRedis(MyClient $client)
    {
        $clientData = $client->toArray();
        Redis::set(self::redisKey($client->slug), json_encode($clientData));
    }


    public static function removeFromRedis($slug)
    {
        Redis::del(self::redisKey($slug));
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($client) {
            self::storeInRedis($client);
        });

        static::updated(function ($client) {
            self::removeFromRedis($client->slug);
            self::storeInRedis($client);
        });

        static::deleted(function ($client) {
            self::removeFromRedis($client->slug);
        });
    }
}
