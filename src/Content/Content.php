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
            'branch' => 'rabie_rabit-github-api-image-uploads',
        ], $options);
        
        // 1. Validate and Read the Image File
        if (!file_exists($imageFilePath)) {
            throw new \InvalidArgumentException("Image file not found at path: {$imageFilePath}");
        }

        if ($this->getBranchLatestSha($options['branch']) === null) {
        
            // 1a. Get the default branch name (e.g., 'main')
            $defaultBranchName = $this->getDefaultBranchName();
            
            // 1b. Get the SHA of the latest commit on the default branch
            $sourceSha = $this->getBranchLatestSha($defaultBranchName);

            if ($sourceSha === null) {
                // Should only happen if the repo is empty or API is inconsistent
                throw new \RuntimeException("Could not get SHA for default branch '{$defaultBranchName}' to create '{$options['branch']}'.");
            }

            // 1c. Create the new 'images' branch pointing to the default branch's last commit.
            $this->createBranch($options['branch'], $sourceSha);
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
        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/contents/rabie_rabit-github-api-image-uploads/{$pathInRepo}");
        
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

    /**
     * Retrieves the latest commit SHA of a given branch.
     *
     * @param string $branchName The name of the branch (e.g., 'main', 'images').
     * @return string|null The commit SHA if the branch exists, otherwise null.
     * @throws \RuntimeException if the API call fails unexpectedly.
     */
    protected function getBranchLatestSha(string $branchName): ?string {
        // API Endpoint: GET /repos/{owner}/{repo}/branches/{branch}
        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/branches/{$branchName}");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 404) {
            // Branch does not exist
            return null;
        }

        if ($http_code !== 200) {
            $error_details = json_decode($response, true)['message'] ?? 'Unknown error';
            throw new \RuntimeException("Failed to check branch '{$branchName}': HTTP {$http_code}. Details: {$error_details}");
        }

        $branch_data = json_decode($response, true);
        return $branch_data['commit']['sha'] ?? null;
    }
    
    /**
     * Creates a new branch from a specified commit SHA.
     *
     * @param string $newBranchName The name of the branch to create (e.g., 'images').
     * @param string $sourceSha The SHA of the commit the new branch should point to.
     * @throws \RuntimeException if the branch creation fails.
     */
    protected function createBranch(string $newBranchName, string $sourceSha): void {
        // API Endpoint: POST /repos/{owner}/{repo}/git/refs
        $payload = json_encode([
            'ref' => "refs/heads/{$newBranchName}",
            'sha' => $sourceSha
        ]);

        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/git/refs");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
                "Content-Type: application/json",
                "Content-Length: " . strlen($payload)
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 201) {
            $error_details = json_decode($response, true)['message'] ?? 'Unknown error';
            throw new \RuntimeException("Failed to create branch '{$newBranchName}': HTTP {$http_code}. Details: {$error_details}");
        }
    }

    /**
     * Retrieves the default branch name of the repository.
     *
     * @return string The default branch name (e.g., 'main' or 'master').
     * @throws \RuntimeException if the API call fails or the branch name is missing.
     */
    public function getDefaultBranchName(): string {

        // API Endpoint: GET /repos/{owner}/{repo}
        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            $error_details = json_decode($response, true)['message'] ?? 'Unknown error';
            throw new \RuntimeException("Failed to retrieve repository information: HTTP {$http_code}. Details: {$error_details}");
        }

        $repo_data = json_decode($response, true);

        if (!isset($repo_data['default_branch'])) {
            throw new \RuntimeException("Repository data is missing the 'default_branch' field.");
        }

        return $repo_data['default_branch'];
    }
}