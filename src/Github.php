<?php

namespace RabieRabit\GithubApi;

use RabieRabit\GithubApi\Issues\Issues;

class Github {
    protected string $repoUrl;
    protected string $repo;
    protected string $owner;
    protected string $token;
    protected string $github_base_url = "https://api.github.com";


    public function __construct(string $repoUrl, string $token) {
        $this->repoUrl = $repoUrl;
        $this->token = $token;

        // Parse URL and extract "owner" and "repo" from the path
        $parsed = parse_url($repoUrl);
        if (!isset($parsed['path'])) {
            throw new \InvalidArgumentException("Invalid repository URL: $repoUrl");
        }

        // Example path: /dylanschutte/testsourcegit.git
        $pathParts = explode('/', trim($parsed['path'], '/'));
        if (count($pathParts) < 2) {
            throw new \InvalidArgumentException("Could not extract owner/repo from: $repoUrl");
        }

        $this->owner = $pathParts[0];
        // Remove .git if present at the end
        $this->repo = preg_replace('/\.git$/', '', $pathParts[1]);
    }
    
    public function issues(): Issues {
        return new Issues($this);
    }

    public function getOwner(): string {
        return $this->owner;
    }

    public function getToken(): string {
        return $this->token;
    }

    public function getRepo(): string {
        return $this->repo;
    }

    public function getFullName(): string {
        return "{$this->owner}/{$this->repo}";
    }

    public function getBaseUrl(): string {
        return $this->github_base_url;
    }


}
