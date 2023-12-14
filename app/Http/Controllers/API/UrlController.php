<?php

namespace App\Http\Controllers\API;

use App\Models\Url;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController;

class UrlController extends Controller
{
    protected $baseController;

    public function __construct(BaseController $baseController)
    {
        $this->baseController = $baseController;
    }

    public function shortenUrl(Request $request)
    {
        $user = Auth::user();
        $longUrl = $request->input('long_url');

        $url = Url::where('long_url', $longUrl)->where('user_id', $user->id)->first();

        if (!$url) {
            $shortUrl = $this->generateUniqueShortUrl();
            $url = Url::create([
                'user_id' => $user->id,
                'long_url' => $longUrl,
                'short_url' => $shortUrl,
            ]);

            return $this->baseController->sendResponse(['short_url' => $url->short_url], 'Shortened URL created successfully.');
        }

        return $this->baseController->sendResponse(['short_url' => $url->short_url], 'Shortened URL already exists.');
    }

    public function listUrls(Request $request)
    {
        $user = Auth::user();
        $urls = Url::where('user_id', $user->id)->get();

        // Add visit_count to each URL in the response
        $formattedUrls = $urls->map(function ($url) {
            return [
                'short_url' => $url->short_url,
                'visit_count' => $url->visit_count,
            ];
        });

        return $this->baseController->sendResponse(['urls' => $formattedUrls], 'List of URLs retrieved successfully.');
    }

    public function redirect(Request $request, $shortUrl)
    {
        $url = Url::where('short_url', $shortUrl)->first();

        if ($url) {
            $url->increment('visit_count');
            return redirect($url->long_url);
        } else {
            return $this->baseController->sendError('Shortened URL not found.', [], 404);
        }
    }

    private function generateUniqueShortUrl()
    {
        do {
            $shortUrl = Str::random(6);
        } while (Url::where('short_url', $shortUrl)->exists());

        return $shortUrl;
    }
}