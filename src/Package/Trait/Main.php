<?php
namespace Package\Raxon\Videoplayer\Trait;

use Raxon\App;
use Raxon\Config;

use Raxon\Doctrine\Module\Database;
use Raxon\Exception\DirectoryCreateException;

use Raxon\Module\Cli;
use Raxon\Module\Data;
use Raxon\Module\Dir;
use Raxon\Module\Core;
use Raxon\Module\File;
use Raxon\Parse\Module\Parse;

use Raxon\Node\Module\Node;

use Exception;

trait Main {
    const NAME = 'Videoplayer';
    /**
     * @throws DirectoryCreateException
     * @throws Exception
     */
    public function install($flags, $options): void
    {
        $object = $this->object();
        if($object->config(Config::POSIX_ID) !== 0){
            return;
        }
        $has_frontend = false;
        if(property_exists($options, 'frontend')){
            if(property_exists($options->frontend, 'host')){                
                $has_frontend = true;
                $frontend_options = [
                    'where' => [
                        [
                            'value' => $options->frontend->host,
                            'attribute' => 'name',
                            'operator' => 'partial',
                        ]
                    ]
                ];
            }                
        }        
        $has_backend = false;
        if(property_exists($options, 'backend')){
            if(property_exists($options->backend, 'host')){                
                $has_backend = true;
                $backend_options = [
                    'where' => [
                        [
                            'value' => $options->backend->host,
                            'attribute' => 'name',
                            'operator' => 'partial',
                        ]
                    ]
                ];                
            }
        }
        if($has_frontend === false){
            throw new Exception('Frontend.host option is required and must be defined in Node/System.Host.json aborting...');
        }
        if($has_backend === false){
            throw new Exception('Backend.host option is required and must be defined in Node/System.Host.json aborting...');
        }
        $class = 'System.Host';
        $node = new Node($object);
        $response_frontend = $node->record($class, $node->role_system(), $frontend_options);
        $response_backend = $node->record($class, $node->role_system(), $backend_options);
        $dir_read = $object->config('project.dir.vendor') .
            $object->request('package') .
            $object->config('ds') .
            'src' .
            $object->config('ds') .
            $object->config('dictionary.application') .
            $object->config('ds')
        ;
        $dir_application = $object->config('project.dir.domain') .
            $response_frontend['node']->name .
            $object->config('ds') .
            $object->config('dictionary.application') .
            $object->config('ds')
        ;
        $dir_target = $dir_application .
            self::NAME .
            $object->config('ds')
        ;
        if(!File::exist($dir_target)){
            Dir::create($dir_target, Dir::CHMOD);
            File::permission($object, [
                'target' => $dir_target,
                'application' => $dir_application,
            ]);
        }
        $dir = new Dir();
        $read = $dir->read($dir_read, true);
        foreach($read as $nr => $file){
            $explode = explode($dir_read, $file->url, 2);
            if(array_key_exists(1, $explode)){
                $file->target = $dir_target . $explode[1];
            }
        }
        foreach($read as $nr => $file){
            if($file->type === Dir::TYPE){
                if(!File::exist($file->target)){
                    Dir::create($file->target, Dir::CHMOD);
                    File::permission($object, [
                        'target' => $file->target,
                    ]);
                }
            }
        }
        $patch = $options->patch ?? null;
        foreach($read as $nr => $file){
            if($file->type === File::TYPE){
                $file->extension = File::extension($file->target);
                if($file->extension === 'rax'){
                    $explode = explode('.rax', $file->target, 2);
                    if(array_key_exists(1, $explode)){
                        $file->target = $explode[0];
                        $file->original_extension = File::extension($file->target);
                        if(!File::exist($file->target) || $patch !== null){
                            $clone_options = new Data();
                            if(!property_exists($response_frontend['node'],'subdomain')){
                                $clone_options->set('frontend.host', $response_frontend['node']->domain . '.' . $response_frontend['node']->extension);
                            } else {
                                $clone_options->set('frontend.host', $response_frontend['node']->subdomain . '.' . $response_frontend['node']->domain . '.' . $response_frontend['node']->extension);
                            }
                            if(!property_exists($response_backend['node'],'subdomain')){
                                $clone_options->set('backend.host', $response_backend['node']->domain . '.' . $response_backend['node']->extension);
                            } else {
                                $clone_options->set('backend.host', $response_backend['node']->subdomain . '.' . $response_backend['node']->domain . '.' . $response_backend['node']->extension);
                            }
                            $data = new Data($object->data());
                            $clone = clone $object;
                            $clone->data(App::OPTIONS, $clone_options->data());                                                        
                            switch($file->original_extension){
                                case 'json':                                    
                                    echo Cli::info('Processing file:') . $file->target . PHP_EOL;
                                    $content = $clone->parse_read($file->url);
                                    if($patch !== null) {
                                        File::delete($file->target);
                                    }                                    
                                    File::write($file->target, Core::object($content->data(), Core::JSON));
                                    File::permission($object, [
                                        'target' => $file->target,
                                    ]);
                                    //imports should be in a json file (class => url/contains)
                                    if(str_contains($file->target, 'System.Route')){
                                        $command = 'app raxon/node object import -class=System.Route -url="' . $file->target . '" -patch';
                                        Core::execute($object, $command, $output, $notification);
                                        if($output){
                                            echo $output;
                                        }
                                        if($notification){
                                            echo $notification;
                                        }
                                    }
                                break;
                                default:
                                    echo Cli::info('Processing file:') . $file->target . PHP_EOL;
                                    $clone_options->set('source', $file->url);
                                    $flags = App::flags($clone);
                                    $parse = new Parse($clone, $data, $flags, $clone_options->data());
                                    $read = File::read($file->url);
                                    $content = $parse->compile($read, $data);
                                    if($patch !== null) {
                                        File::delete($file->target);
                                    }                                    
                                    File::write($file->target, $content);
                                    File::permission($object, [
                                        'target' => $file->target,
                                    ]);
                                break;
                            }
                        }                                    
                    }
                } else {
                    if($patch !== null) {
                        File::delete($file->target);
                    }
                    echo Cli::info('Processing file:') . $file->target . PHP_EOL;
                    File::copy($file->url, $file->target);                    
                    File::permission($object, [
                        'target' => $file->target,
                    ]);
                }                
            }
        }
        if(!property_exists($options, 'environment')){
            $options->environment = $object->config('framework.environment');
        }
        if(!property_exists($options, 'connection')){
            $options->connection = 'system';
        }
        $config = Database::config($object);
        $connection = $object->config('doctrine.environment.' . $options->connection . '.' . $options->environment);
        if($connection === null){
            $connection = $object->config('doctrine.environment.' . $options->connection . '.' . '*');
        }
        $connection->manager = Database::entity_manager($object, $config, $connection);
        $repository = $connection->manager->getRepository('\\Entity\\Extension');
        $property = 'name';
        $extensions = [
            'mp4',
            'webm',
        ];
        $list =$repository->findBy([
            $property => $extensions
        ]);
        foreach($list as $nr => $extension){
            d($extension->getName());
            d($extension->getApplications());
        }




        /*
         * on staging we have a new menu on raxon, we have an old desktop menu.
         *
        $repository = $connection->manager->getRepository('\\Entity\\Extension');
        $property = 'name';
        $value = [
            'mp4',
            'webm',
        ];
        foreach($value as $nr => $extension) {
            $record = $repository->findOneBy([
                $property => $extension
            ]);
            if ($record) {
                $applications = $record->getApplications();
                if($applications->count() > 0){
                    $is_found = false;
                    $list = $applications->toArray();
                    foreach($list as $nr => $application){
                        if($application->getUrl() === '{{route.get(\'application-video-player\')}}'){
                            $is_found = true;
                        }
                    }
                    if($is_found === false){
                        $entity_application = new \Entity\Application();
                        $entity_application->setUrl('{{route.get(\'application-video-player\')}}');
                        $entity_application->setName('Video Player');
                        $entity_application->iconUrl('/Application/VideoPlayer/Icon/Icon.png');

//                        $connection->manager->persist($entity_application);
//                        $connection->manager->flush();




                        $entity_extension = new \Entity\Extension();
                        $entity_extension->setName($extension);
                        $connection->manager->persist($entity_extension);
                        $connection->manager->flush();

                        dd(($entity_extension->getId()));




                    }
                    //check if videoplayer is already installed
                    ddd($applications->count());
                } else {
                    ddd('none');
                }
            } else {
                dd('none');
            }
        }
        */
/*

        //enable application extensions allowed movietypes:
            case 'mp4' :
                    return 'video/mp4';
            case 'webm' :
                return 'video/webm';
*/

        $command = 'app install raxon/account -patch';
        Core::execute($object, $command, $output, $notification);
        if($output){
            echo $output;
        }
        if($notification){
            echo $notification;
        }
    }

}