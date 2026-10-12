<?php

use App\Models\SystemModel;

if (!function_exists('translate')) {
    function translate(string $key): string {
        static $cachedTranslations = null;
        static $language = null;

        if ($cachedTranslations === null) {
            try {
                $modelSystem = new SystemModel();
                $query = $modelSystem->select('value')->where('key', 'language')->get();
                $lang = $query->getRow() ? $query->getRow()->value : 'spanish';
                $language = strtolower($lang ?: 'spanish');
            } catch (\Throwable $e) {
                $language = 'spanish';
            }

            initializeLanguageFile($language);

            $defaultFilePath = APPPATH . 'Language/' . $language . '.json';
            if (file_exists($defaultFilePath)) {
                $cachedTranslations = json_decode(@file_get_contents($defaultFilePath), true) ?: [];
            } else {
                $cachedTranslations = [];
            }
        }

        if (isset($cachedTranslations[$key])) {
            return $cachedTranslations[$key];
        }

        $englishFilePath = APPPATH . 'Language/english.json';
        $defaultFilePath = APPPATH . 'Language/' . $language . '.json';

        $englishTranslations = file_exists($englishFilePath) ? (json_decode(@file_get_contents($englishFilePath), true) ?: []) : [];
        $defaultTranslations = $cachedTranslations;

        if (!array_key_exists($key, $englishTranslations)) {
            $englishTranslations[$key] = ucfirst($key);
            @file_put_contents($englishFilePath, json_encode($englishTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        if (!array_key_exists($key, $defaultTranslations)) {
            $defaultTranslations[$key] = ucfirst($key);
            @file_put_contents($defaultFilePath, json_encode($defaultTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $cachedTranslations[$key] = $defaultTranslations[$key];
        }

        return $defaultTranslations[$key] ?? $key;
    }
}


if (!function_exists('initializeLanguageFile')) {
    function initializeLanguageFile(string $language): void {
        $languageFilePath = APPPATH . 'Language/' . strtolower($language) . '.json';
        $englishFilePath = APPPATH . 'Language/english.json';

        if (!file_exists($englishFilePath)) {
            file_put_contents($englishFilePath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        if (!file_exists($languageFilePath)) {
            $englishTranslations = json_decode(file_get_contents($englishFilePath), true);
            $newTranslations = [];

            foreach ($englishTranslations as $key => $value) {
                $newTranslations[$key] = $value;
            }

            file_put_contents($languageFilePath, json_encode($newTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}

