# Installation
`
composer require developermarshak/QueueCouchbase
`

or add to your composer.json in section autoload

`
"developermarshak/QueueCouchbase": "0.*"
`

Register service provider:

`
'developermarshak\QueueCouchbase\CouchbaseQueueServiceProvider'
`

Run tests

```
php ./tests/create-bucket.php
php ./vendor/bin/phpunit
```