<?php

namespace RabieRabit\GithubApi\Issues;

use RabieRabit\GithubApi\Github;

class Issues {
    protected Github $github;
    protected int $issue;

    /**
     * Initialize the Issues service.
     *
     * @param Github $github      The GitHub client instance used to perform API requests.
     * @param int    $issue_number Optional issue number to target. Use -1 (default) when no specific issue is selected.
     */
    public function __construct(Github $github, int $issue_number = -1) {
        $this->github = $github;
        $this->setIssueNumber($issue_number);
    }
    
    /**
     * Set the issue number to target.
     *
     * @param int $issue_number The issue number to target. Use -1 to reset to no specific issue.
     */
    public function setIssueNumber(int $issue_number): void {
        $this->issue = $issue_number;
    }

    /**
     * Lists all issues for a repository.
     *
     * @return array JSON response from the API containing a list of issues.
     */
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

    /**
     * Gets the currently set issue number.
     *
     * @return int The currently set issue number, or -1 if no specific issue is selected.
     */
    public function getIssue(): int {
        return $this->issue;
    }

    /**
     * Post a comment on the currently set issue.
     *
     * @param string $comment The comment to post.
     * @param array $options Optional parameters to pass to the API.
     * @return array JSON response from the API containing the created comment.
     * @throws \InvalidArgumentException If the issue number is not set or is set to an invalid value.
     */
    public function comment($comment, $options = []): array {
        if (!$this->issue || $this->issue < 1) {
            throw new \InvalidArgumentException("Issue number must be set to post a comment.");
        }

        $options = array_merge([
            'body' => $comment
        ], $options);

        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues/{$this->getIssue()}/comments");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
                "Content-Type: application/json"
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($options)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Updates an existing issue.
     *
     * @param string $title The issue title to update.
     * @param string $body The issue body to update.
     * @param int $issue The issue number to update.
     * @param array $options Optional parameters to pass to the API.
     * @return array JSON response from the API containing the updated issue.
     * @throws \InvalidArgumentException If the issue number is not set or is set to an invalid value.
     */
    public function updateIssue($title, $body, $issue,$options=[]): array{
        $options = array_merge([
            "title" => $title,
            "body" => $body,
            'labels' => []
        ], $options);

        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues/$issue");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
                "Content-Type: application/json"
            ],
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($options)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

/*************  ✨ Windsurf Command ⭐  *************/
    /**
     * Creates a new issue.
     *
     * @param string $title The issue title.
     * @param string $body The issue body.
     * @param array $options Optional parameters to pass to the API.
     * @return array JSON response from the API containing the newly created issue.
     */
/*******  5e4a2312-c366-4221-b9f5-c62587b76538  *******/
    public function createIssue($title, $body, $options=[]): array {
        $options = array_merge([
            "title" => $title,
            "body" => $body,
            'labels' => []
        ], $options);

        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
                "Content-Type: application/json"
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($options)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
