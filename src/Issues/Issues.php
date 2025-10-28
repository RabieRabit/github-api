<?php

namespace RabieRabit\GithubApi\Issues;

use RabieRabit\GithubApi\Github;

class Issues {
    protected Github $github;

    public function __construct(Github $github) {
        $this->github = $github;
    }
    
    public function list(): array{
        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
