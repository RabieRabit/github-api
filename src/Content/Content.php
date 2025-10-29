<?php

namespace RabieRabit\GithubApi\Content;

use RabieRabit\GithubApi\Github;

class Content {
    protected Github $github;

    public function __construct(Github $github) {
        $this->github = $github;
    }
    
    public function uploadImageToRepo(string $imageFilePath, string $pathInRepo, $options = []): array {

        $options = array_merge([
            'commit_message' => 'Add Issue image via API',
            'branch' => 'main',
        ], $options);

        // 1. Validate and Read the Image File
        if (!file_exists($imageFilePath)) {
            throw new \InvalidArgumentException("Image file not found at path: {$imageFilePath}");
        }

        $fileContent = file_get_contents($imageFilePath);

        if ($fileContent === false) {
            throw new \RuntimeException("Failed to read image file: {$imageFilePath}");
        }

        // 2. Convert File Content to Base64
        // The GitHub Contents API requires the file content to be Base64-encoded.
        $content_to_upload = base64_encode($fileContent);

        // 3. Prepare the request payload
        $payload = json_encode([
            'message' => $options['commit_message'],
            'content' => $content_to_upload, // The Base64 string from the file
            'branch' => $options['branch'], // Or your preferred branch
        ]);
        
        // 4. Set up the cURL request to the Contents API (PUT)
        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/contents/{$pathInRepo}");
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT', 
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
                "Content-Type: application/json",
                "Content-Length: " . strlen($payload)
            ],
        ]);

        // 5. Execute the request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 6. Handle response and errors
        if ($http_code !== 201) { // 201 Created is the expected success code
            $error_details = json_decode($response, true)['message'] ?? 'Unknown error';
            throw new \RuntimeException("Failed to upload image: HTTP {$http_code}. Details: {$error_details}");
        }

        return json_decode($response, true);
    }

}