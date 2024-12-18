<?php

namespace App\Http\Controllers;

use App\Models\MyClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;

class MyClientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:250',
            'slug' => 'required|string|max:100|unique:my_clients',
            'client_logo' => 'required|image|mimes:jpeg,png,jpg,gif',
        ]);

        $path = $request->file('client_logo')->store('client_logos', 's3');

        $client = MyClient::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'client_logo' => $path,
            'is_project' => $request->is_project ?? '0',
            'self_capture' => $request->self_capture ?? '1',
            'client_prefix' => $request->client_prefix,
            'address' => $request->address,
            'phone_number' => $request->phone_number,
            'city' => $request->city,
        ]);

        MyClient::storeInRedis($client);

        return response()->json($client, 201);
    }

    public function update(Request $request, $slug)
    {
        $request->validate([
            'name' => 'required|string|max:250',
            'client_logo' => 'sometimes|required|image|mimes:jpeg,png,jpg,gif',
        ]);

        $client = MyClient::where('slug', $slug)->firstOrFail();

        if ($request->hasFile('client_logo')) {
            Storage::disk('s3')->delete($client->client_logo);

            $path = $request->file('client_logo')->store('client_logos', 's3');
            $client->client_logo = $path;
        }

        $client->update([
            'name' => $request->name,
            'is_project' => $request->is_project ?? $client->is_project,
            'self_capture' => $request->self_capture ?? $client->self_capture,
            'client_prefix' => $request->client_prefix ?? $client->client_prefix,
            'address' => $request->address ?? $client->address,
            'phone_number' => $request->phone_number ?? $client->phone_number,
            'city' => $request->city ?? $client->city,
        ]);

        MyClient::removeFromRedis($slug);
        MyClient::storeInRedis($client);

        return response()->json($client);
    }

    public function destroy($slug)
    {
        $client = MyClient::where('slug', $slug)->firstOrFail();

        $client->update(['deleted_at' => now()]);

        MyClient::removeFromRedis($slug);

        return response()->json(['message' => 'Client deleted successfully']);
    }

    public function show($slug)
    {
        $clientData = Redis::get(MyClient::redisKey($slug));

        if (!$clientData) {
            $client = MyClient::where('slug', $slug)->first();
            if ($client) {
                $clientData = $client->toJson();
                Redis::set(MyClient::redisKey($slug), $clientData);
            }
        }

        return response()->json(json_decode($clientData));
    }
}

