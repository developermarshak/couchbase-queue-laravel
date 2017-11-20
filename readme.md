# Installation
`
composer require developermarshak/queue-couchbase
`

or add to your composer.json in section autoload

`
"developermarshak/queue-couchbase": "0.*"
`

Register service providerS:

```
'\Mpociot\Couchbase\CouchbaseServiceProvider::class'
'\developermarshak\QueueCouchbase\CouchbaseQueueServiceProvider'
```

Copy queue config:
```
mkdir config
cp ./vendor/developermarshak/queue-couchbase/src/config/queue.php ./config/
```

Set at .env queue driver couchbase:
```
QUEUE_DRIVER=couchbase
```

#Run tests

```
php ./tests/create-bucket.php
php ./vendor/bin/phpunit
```