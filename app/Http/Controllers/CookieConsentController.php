<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CookieConsentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();
        $csvFile = storage_path('app/cookie-consents.csv');

        // Create headers if file doesn't exist
        if (!file_exists($csvFile)) {
            $headers = array_keys($data);
            file_put_contents($csvFile, implode(',', $headers) . "\n");
        }

        // Append data
        $values = array_map(function ($value) {
            return is_string($value) ? '"' . str_replace('"', '""', $value) . '"' : $value;
        }, array_values($data));

        file_put_contents($csvFile, implode(',', $values) . "\n", FILE_APPEND);

        return response()->json(['success' => true]);
    }

    public function download()
    {
        $csvFile = storage_path('app/cookie-consents.csv');

        if (!file_exists($csvFile)) {
            return redirect()->back()->with('error', __('No data available'));
        }

        return response()->download($csvFile, 'cookie-consents-' . date('Y-m-d') . '.csv');
    }
}
