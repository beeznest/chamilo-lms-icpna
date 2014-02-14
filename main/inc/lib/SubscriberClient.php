<?php

use Guzzle\Common\Collection;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;

class SubscriberClient extends Client
{
    public static function factory($config = array())
    {
        // Provide a hash of default client configuration options
        $default = array('base_url' => 'https://api.twitter.com/1.1');
        //"baseUrl": "http://localhost/icpna-course-subscriber/web/app.php/api/v1/",
        // The following values are required when creating the client
        $required = array(
            'base_url',
            /*'consumer_key',
            'consumer_secret',
            'token',
            'token_secret'*/
        );
        // Merge in default settings and validate the config
        $config = Collection::fromConfig($config, $default, $required);

        // Create a new Twitter client
        $client = new self($config->get('base_url'), $config);

        // Ensure that the OauthPlugin is attached to the client
        //$client->addSubscriber();
        $client->setDescription(ServiceDescription::factory(api_get_path(LIBRARY_PATH).'rest/course_subscriber.json'));
        return $client;
    }
}
