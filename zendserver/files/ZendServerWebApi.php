<?php

/**
 * Class ZendServerWebApi intended to use during local provisioning of vagrant boxes.
 * Thus methods actively utilize stdout/echo/printf in order to be verbose and visible
 * in `make` provisioning log.
 */
class ZendServerWebApi
{
    const SCHEME = 'http://';
    const HOST = 'localhost:10081';
    const PATH = '/ZendServer/Api/';
    const USER_AGENT = 'PhpZendUpdater';

    const PHP_GET = 'get';
    const PHP_POST = 'post';

    const ZEND_CHANGE_PHP_VERSION = 'changePhpVersion';
    const ZEND_GET_ALL_QUEUES = 'jobqueueGetQueues';
    const ZEND_UPDATE_QUEUE = 'jobqueueUpdateQueue';

    /**
     * @var string Zend Server WebAPI user
     */
    private $user;

    /**
     * @var string Zend Server WebAPI key
     */
    private $key;

    /**
     * @var string [optional] value to be set
     */
    private $value;

    /**
     * @param array $argv
     */
    public function __construct(array $argv)
    {
        if (count($argv) < 4) {
            die("USAGE: php <filename>.php <user> <api_key> <value>\n");
        }
        $this->user = strval($argv[1]);
        $this->key = strval($argv[2]);
        $this->value = isset($argv[3]) ? strval($argv[3]) : '0';
    }

    /**
     * Retrieve all existing queues in Zend Queue Daemon.
     * Returns array of objects e.g.:
     * ```
     * {
     *     [id] => 3
     *     [name] => BllRecurring
     *     [status] => 1
     *     [priority] => 2
     *     [max_http_jobs] => 12
     *     [max_wait_time] => 30.0
     *     [http_connection_timeout] => 30
     *     [http_job_timeout] => 120
     *     [cli_job_timeout] => 1800
     *     [http_job_retry_count] => 10
     *     [http_job_retry_timeout] => 1
     *     [running_jobs_count] => 0
     *     [pending_jobs_count] => 0
     * }
     * ```
     */
    public function getAllQueues(): array
    {
        $response = $this->execApiCall(self::ZEND_GET_ALL_QUEUES);
        $response = json_decode($response);

        if (isset($response->errorData->errorMessage)) {
            echo "ERROR: {$response->errorData->errorMessage}\n";
            die();
        }

        return $response->responseData->queues;
    }

    /**
     * Sets all queues `http_job_retry_count` parameter to zero
     */
    public function setAllQueuesRetries(): void
    {
        foreach ($this->getAllQueues() as $ix => $queue) {
            $this->setQueueRetryCount(intval($queue->id));
        }
    }

    /**
     * Sets current PHP version to specified in CLI argument. In case of soft failure doesn't produce errorMessage
     * but specifies `responseData->status = false` instead. Same goes for switching from 7.2 to 7.2.
     */
    public function setPhpVersion(): void
    {
        $response = $this->execApiCall(self::ZEND_CHANGE_PHP_VERSION, self::PHP_POST, ['phpVersion' => $this->value]);
        $response = json_decode($response);

        if (isset($response->errorData->errorMessage)) {
            echo "ERROR: {$response->errorData->errorMessage}\n";
            die();
        }

        printf(
            "PHP switch to version '%s' status: %s\n",
            $this->value,
            var_export($response->responseData->status, true)
        );
    }

    /**
     * Set particular queue http_job_retry_count to zero
     * @param int $queueId Queue id
     */
    public function setQueueRetryCount(int $queueId): void
    {
        $postfields = [
            'id' => $queueId,
            'http_job_retry_count' => $this->value
        ];
        $response = $this->execApiCall(self::ZEND_UPDATE_QUEUE, self::PHP_POST, $postfields);
        $response = json_decode($response);

        if (isset($response->errorData->errorMessage)) {
            echo "ERROR: {$response->errorData->errorMessage}\n";
            return;
        }

        printf("Queue #%s http_job_retry_count set to %s: %s\n", $queueId, $this->value, $response->responseData->result);
    }

    /**
     * Execute call to Zend Server WebAPI
     * @param string $endpoint WebAPI endpoint name
     * @param string $method request method, i.e. get or post
     * @param array $postFields map of post fields
     * @return string
     */
    private function execApiCall(string $endpoint, string $method = self::PHP_GET, array $postFields = []): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::SCHEME . self::HOST . self::PATH . $endpoint,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => $this->generateHeaders($endpoint)
        ]);

        if ($method == self::PHP_POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        }

        $server_output = curl_exec($ch);
        curl_close($ch);

        return $server_output;
    }

    /**
     * Build all headers to use in Zend Server WebAPI request
     * @param string $endpoint
     * @return array
     */
    private function generateHeaders(string $endpoint): array
    {
        return [
            'Accept: application/vnd.zend.serverapi+json;version=1.16',
            'User-Agent: ' . self::USER_AGENT,
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
            'Date: ' . gmdate('D, d M Y H:i:s ') . 'GMT',
            'X-Zend-Signature: ' . $this->user . ";" . $this->generateRequestSignature(self::PATH . $endpoint),
        ];
    }

    /**
     * Build authentication signature to use in X-Zend-Signature header
     * @param string $path
     * @return string
     */
    private function generateRequestSignature(string $path): string
    {
        $data = sprintf("%s:%s:%s:%s GMT", self::HOST, $path, self::USER_AGENT, gmdate('D, d M Y H:i:s', time()));
        return hash_hmac('sha256', $data, $this->key);
    }
}
