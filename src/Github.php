<?php

namespace RabieRabit\GithubApi;

use RabieRabit\GithubApi\Content\Content;
use RabieRabit\GithubApi\Issues\Issues;

class Github {
    protected string $repoUrl;
    protected string $repo;
    protected string $owner;
    protected string $token;
    protected string $github_base_url = "https://api.github.com";


    /**
     * Constructor for Github API.
     *
     * @param string $repoUrl URL of the repository, e.g. https://github.com/dylanschutte/testsourcegit.git
     * @param string $token The Github API token to use for requests.
     *
     * @throws \InvalidArgumentException If the repository URL is invalid, or if the owner/repo cannot be extracted from the URL.
     */
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
    
    /**
     * Get an instance of the Issues service.
     *
     * @param int $issue_number Optional issue number to target. Use -1 (default) when no specific issue is selected.
     * @return Issues
     */
    public function issues(int $issue_number = 0): Issues {
        return new Issues($this, $issue_number);
    }

    public function content(): Content {
        return new Content($this);
    }

    /**
     * Gets the owner of the repository.
     *
     * @return string The owner of the repository.
     */
    public function getOwner(): string {
        return $this->owner;
    }

    /**
     * Gets the Github API token used for requests.
     *
     * @return string The Github API token.
     */
    public function getToken(): string {
        return $this->token;
    }

    /**
     * Gets the name of the repository.
     *
     * @return string The name of the repository.
     */
    public function getRepo(): string {
        return $this->repo;
    }

    /**
     * Gets the full name of the repository in the format "owner/repo".
     *
     * @return string The full name of the repository.
     */
    public function getFullName(): string {
        return "{$this->owner}/{$this->repo}";
    }

    /**
     * Gets the base URL of the Github API.
     *
     * @return string The base URL of the Github API.
     */
    public function getBaseUrl(): string {
        return $this->github_base_url;
    }


}
