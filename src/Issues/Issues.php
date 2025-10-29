<?php

namespace RabieRabit\GithubApi\Issues;

use RabieRabit\GithubApi\Github;

class Issues {
    protected Github $github;
    protected array $issue;
    protected int $issueNumber = 0;

    /**
     * Initialize the Issues service.
     *
     * @param Github $github      The GitHub client instance used to perform API requests.
     * @param int    $issue_number Optional issue number to target. Use -1 (default) when no specific issue is selected.
     */
    public function __construct(Github $github, int $issue_number = 0) {
        $this->github = $github;
        if ($issue_number) $this->setIssueNumber($issue_number);
    }
    
    /**
     * Set the issue number to target.
     *
     * @param int $issue_number The issue number to target. Use -1 to reset to no specific issue.
     */
    public function setIssueNumber(int $issue_number): void {
        $this->issueNumber = $issue_number;
        $this->issue = $this->initIssue();
    }

    /**
     * Lists all issues for a repository.
     *
     * @return array JSON response from the API containing a list of issues.
     */
    public function list($options = []): array{

        $options = array_merge([
            'filters' => [
                "state" => false, // open, closed, all
                "labels" => false, // comma separated label names
                "sort" => false, // created, updated, comments
                "direction" => false, // asc, desc
                "per_page" => false, // default 30
                "page" => false, // default 1
                "milestone" => false, // milestone number or '*', 'none'
                "assignee" => false, // assignee username or '*', 'none'
                "type" => false, // issue, pr
                "creator" => false, // creator username
                "mentioned" => false, // mentioned username
                "since" => false, // ISO 8601 format: YYYY-MM-DDTHH:MM:SSZ
            ]
        ], $options);
        
        foreach ($options['filters'] as $key => $value) {
            if ($value === false) {
                unset($options['filters'][$key]);
            }
        }

        $url = "{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues?".http_build_query($options['filters']);
        echo $url . "\n";
        $ch = curl_init($url);
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
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \RuntimeException("Failed to issues: {$http_code}");
        }

        return json_decode($response, true);
    }

    /**
     * Gets the currently set issue number.
     *
     * @return int The currently set issue number, or -1 if no specific issue is selected.
     */
    public function getIssueNumber(): int {
        return $this->issueNumber;
    }

    /**
     * Post a comment on the currently set issue.
     *
     * @param string $comment The comment to post.
     * @param array $options Optional parameters to pass to the API.
     * @return array JSON response from the API containing the created comment.
     * @throws \InvalidArgumentException If the issue number is not set or is set to an invalid value.
     */
    public function comment($comment, $options = []): mixed {
        $this->validate();

        $options = array_merge([
            'post_data' => [
                'body' => $comment
            ],
        ], $options);

        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues/{$this->getIssueNumber()}/comments");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
                "Content-Type: application/json"
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($options['post_data'])
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \RuntimeException("Failed to create comment: {$http_code}");
        }

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
    public function updateIssue($options=[]): array{
        $this->validate();
        
        $options = array_merge([
            "post_data" => [
                "title" => $this->issue['title'],
                "body" => $this->issue['body'],
                'labels' => $this->getLabelArray(),
                'type' => '',
                'assignees' => $this->getAssigneeArray()
            ],
        ], $options);

        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues/{$this->getIssueNumber()}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
                "Content-Type: application/json"
            ],
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($options['post_data'])
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \RuntimeException("Failed to update issue: {$http_code}");
        }

        return json_decode($response, true);
    }

    /**
     * Creates a new issue.
     *
     * @param string $title The title of the issue to create.
     * @param string $body The body of the issue to create.
     * @param array $options Optional parameters to pass to the Github API.
     * @return mixed The JSON response from the API, or the formatted issue data if $options['raw_response'] is false.
     */
    public function createIssue($title, $body, $options=[]): array {
        $options = array_merge([
            "post_data" => [
                "title" => $title,
                "body" => $body,
                'labels' => [],
                'type' => '',
                'assignees' => []
            ],
        ], $options);

        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->github->getToken()}",
                "User-Agent: {$this->github->getOwner()}",
                "Accept: application/vnd.github.v3+json",
                "Content-Type: application/json"
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($options['post_data'])
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 201) {
            throw new \RuntimeException("Failed to create issue: {$http_code}");
        }
        
        $json = json_decode($response, true);
        $issue_number = $json['number'] ?? null;
        if ($issue_number) {
            $this->setIssueNumber($issue_number);
        }

        return json_decode($response, true);
    }


    /**
     * Retrieves the timeline of an issue.
     * 
     * This function takes an optional array of options to pass to the Github API.
     * 
     * The options array can contain the following keys:
     * - raw_response: A boolean indicating whether to return the raw response from the Github API, or to format it using IssueFormatter::formatTimeline.
     * 
     * @param array $options Optional options to pass to the Github API.
     * @return mixed The response from the Github API, or the formatted response using IssueFormatter::formatTimeline.
     * @throws \InvalidArgumentException If the issue number is not set or is set to an invalid value.
     */
    public function getTimeline($options=[]): array {
        $this->validate();

        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues/{$this->getIssueNumber()}/timeline");
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
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \RuntimeException("Failed to timeline: {$http_code}");
        }
        
        return json_decode($response, true);
    }

    public function initIssue($options=[]): array {
        $this->validate();

        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/issues/{$this->getIssueNumber()}");
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
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \RuntimeException("Failed Init Issue: {$http_code}");
        }
        
        $this->issue = json_decode($response, true);

        return $this->issue;
    }

    public function getLabelArray(): array {
        $labels = $this->issue['labels'] ?? [];
        $labelNames = [];
        foreach ($labels as $label) {
            $labelNames[] = $label['name'];
        }
        return $labelNames;
    }

    public function getAssigneeArray(): array {
        $this->validate();

        $assignees = $this->issue['assignees'] ?? [];
        $assigneeNames = [];
        foreach ($assignees as $assignee) {
            $assigneeNames[] = $assignee['login'];
        }
        return $assigneeNames;
    }

    public function validate(): bool {
        if (!$this->issueNumber || $this->issueNumber < 1) {
            throw new \InvalidArgumentException("Issue number must be set.");
        }
        return true;
    }

    public function getAvailableLabels(){
        $ch = curl_init("{$this->github->getBaseUrl()}/repos/{$this->github->getFullName()}/labels");
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
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \RuntimeException("Failed to get available labels: {$http_code}");
        }

        return json_decode($response, true);
    }
}
