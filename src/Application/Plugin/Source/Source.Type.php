<?php
/**
 * @package Plugin\Modifier
 * @author Remco van der Velde
 * @since 2024-08-19
 * @license MIT
 * @version 1.0
 * @changeLog
 *    - all
 */
namespace Plugin;

use Raxon\Module\File;

trait Source_Type {

    protected function source_type(string $url=''): string
    {
        $options = [];
        $options['url'] = $url;
        if(!empty($options['url'])){
            $url = $options['url'];
            $extension = File::extension($url);
            switch($extension){
                case 'mp4' :
                    return 'video/mp4';
                case 'avi' :
                    return 'video/x-msvideo';
                case 'mov' :
                    return 'video/quicktime';
                case 'webm' :
                    return 'video/webm';
                case 'wmv' :
                    return 'video/x-ms-wmv';
                default:
                    return '';
            }
        } else {
            return '';
        }
    }

}