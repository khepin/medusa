<?php
namespace Khepin\Medusa;

use Guzzle\Service\Client;

/**
 * Finds all the dependencies on which a given package relies
 */
class DependencyResolver
{
    protected $package;

    public function __construct($package)
    {
        $this->package = $package;
    }

    public function resolve()
    {
        $deps = [$this->package];
        $resolved = [];

        $guzzle = new Client('http://packagist.org');

        while(count($deps) > 0){
            $package = array_pop($deps);

            $response = $guzzle->get('/packages/'.$package.'.json')->send();
            $response = $response->getBody(true);
            $package = json_decode($response);

            if(!is_null($package)){
                foreach($package->package->versions as $version){
                    foreach($version->require as $dependency => $version){
                        if(!in_array($dependency, $resolved) && !in_array($dependency, $deps)){
                            $deps[] = $dependency;
                            $deps = array_unique($deps);
                        }
                    }
                }
                $resolved[] = $package->package->name;
            }
        }
        return $resolved;
    }
}