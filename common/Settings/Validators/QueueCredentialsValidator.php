<?php

namespace Common\Settings\Validators;

use Config;
use Queue;
use Exception;
use Illuminate\Support\Arr;

class QueueCredentialsValidator
{
    const KEYS = [
        'queue_driver',

        // sqs
        'SQS_QUEUE_KEY', 'SQS_QUEUE_SECRET', 'SQS_QUEUE_PREFIX', 'SQS_QUEUE_NAME', 'SQS_QUEUE_REGION',
    ];

    public function fails($settings)
    {
        $this->setConfigDynamically($settings);

        $driver = Arr::get($settings, 'queue_driver', config('queue.default'));

        try {
            Queue::connection($driver)->size();
        } catch (Exception $e) {
            return $this->getErrorMessage($e, $driver);
        }
    }

    private function setConfigDynamically($settings)
    {
        foreach ($settings as $key => $value) {
            // SQS_QUEUE_KEY => sqs.queue.key
            $key = strtolower(str_replace('_', '.', $key));
            // sqs.queue.key => sqs.key
            $key = str_replace('queue.', '', $key);
            Config::set("queue.connections.$key", $value);
        }
    }

    /**
     * @param Exception $e
     * @param string $driver
     * @return array
     */
    private function getErrorMessage($e, $driver)
    {
        return ['queue_group' => "Could not change queue driver to <strong>$driver</strong>.<br> {$e->getMessage()}"];
    }
}