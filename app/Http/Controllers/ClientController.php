<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        // Recherche simple
        $query = Client::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        // On trie par date de création décroissante et on pagine
        $clients = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,company',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Client::create($validated);

        return Redirect::route('clients.index')->with('success', 'Client créé avec succès.');
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,company',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $client->update($validated);

        return Redirect::route('clients.index')->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return Redirect::route('clients.index')->with('success', 'Client supprimé.');
    }
}