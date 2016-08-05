# Sqs Queue Driver Bundle

A driver for [Queue Manager Bundle](https://github.com/mcfedr/queue-manager-bundle) that uses [Amazon SQS](https://aws.amazon.com/sqs/)

[![Latest Stable Version](https://poser.pugx.org/mcfedr/sqs-queue-driver-bundle/v/stable.png)](https://packagist.org/packages/mcfedr/sqs-queue-driver-bundle)
[![License](https://poser.pugx.org/mcfedr/sqs-queue-driver-bundle/license.png)](https://packagist.org/packages/mcfedr/sqs-queue-driver-bundle)

## Install

### Composer

    composer require mcfedr/sqs-queue-driver-bundle

### AppKernel

Include the bundle in your AppKernel

    public function registerBundles()
    {
        $bundles = [
            ...
            new Mcfedr\QueueManagerBundle\McfedrQueueManagerBundle(),
            new Mcfedr\SqsQueueDriverBundle\McfedrSqsQueueDriverBundle(),

## Config

With this bundle installed you can setup your queue manager config similar to this:

    mcfedr_queue_manager:
        managers:
            default:
                driver: sqs
                options:
                    default_url: https://sqs.eu-west-1.amazonaws.com/...
                    region: eu-west-1
                    credentials:
                        key: 'my-access-key-id'
                        secret: 'my-secret-access-key'
                    queues:
                        name: https://sqs.eu-west-1.amazonaws.com/...
                        name2: https://sqs.eu-west-1.amazonaws.com/...

This will create a `QueueManager` service named `"mcfedr_queue_manager.default"`

* `default_url` - Default SQS queue url
* `region` **required** - The region where your queue is
* `credentials` *optional* - [Specify your key and secret](http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html#using-hard-coded-credentials)
  This is optional because the SDK can pick up your credentials from a [variety of places](http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html)
* `queues` *optional* - Allows you to setup a mapping of short names for queues, this makes it easier to use multiple queues and keep the config in one place

## Options to `QueueManager::put`

* `url` - A `string` with the url of a queue
* `queue` - A `string` with the name of a queue in the config
* `time` - A `\DateTime` object of when to schedule this job. **Note:** SQS can delay jobs up to 15 minutes 
* `delay` - Number of seconds from now to schedule this job. **Note:** SQS can delay jobs up to 15 minutes
