<?php
//Support IDE shtorm fast autocomplete
$catalogPath       = 'catalog/';
$adminPath         = 'admin/';

    require 'config.php';
    
      function getModels( $basedir = DIR_APPLICATION ) {
        $permission = array();
        $files = scanDirectories($basedir . 'model');
        $unique = array();
        foreach ( $files as $file ) {

            $data  = explode( '/', dirname( $file ) );
            $names = explode( '_', basename( $file, '.php' ) );

            if ( !$names ) {
                $names = array( basename( $file, '.php' ) );
            }

            $domain_check = $after_domain = false;
            $model_name = $model_var_name = '';
            foreach ($data as $dt){
                if(empty($dt)){
                    continue;
                }

                if($after_domain) {
                    $model_name .= ucfirst($dt);
                    $model_var_name.= $dt.'_';
                }

                if($domain_check){
                    $after_domain = true;
                }

                if(stristr(HTTP_SERVER,$dt)){
                    $domain_check = true;
                }

            }
//            $model_var_name = substr($model_var_name,0,-1);

            $model_path_name = $model_name.
                implode( '',
                    array_map(
                        function ( $x ) {
                            //var_dump($x);
                            return ucfirst( $x );
                        }
                        , $names ) );

//            $model_var_name.=implode( '_',$names);

            if(isset($unique[$model_path_name])){
                continue;
            }else{
                $unique[$model_path_name] = true;
            }

            $permission[] = //'Model' .
                $model_path_name .
                ' $'.$model_var_name.basename( $file, '.php' );
        }
//var_dump($permission);
        return $permission;
    }

    function getClasses( $file ) {
        $result  = array();
        $pattern = '%library/([a-z]+)\.php%';
        $content = file_get_contents( $file );
        if ( preg_match_all( $pattern, $content, $matches ) ) {
            foreach ( $matches[1] as $item ) {
                if( $item == 'template' ){
                    continue; //remove template - var of Controller
                }
                $result[] = sprintf( '%s $%s', ucfirst( $item ), $item );
            }
        }
        return $result;
    }

    function getLineOfFile( $fp, $needle ) {
        rewind( $fp );

        $lineNumber = 0;

        while ( !feof( $fp ) ) {
            $line = fgets( $fp );
            if ( !( strpos( $line, $needle ) === false ) ) {
                break;
            }
            $lineNumber++;
        }

        return feof( $fp ) ? null : $lineNumber;
    }


    $rewriteController = false;
    $pathToController  = DIR_SYSTEM . 'engine/controller.php';
    $searchLine        = 'abstract class Controller {';


    $properties = array(
          'string $id'
        , 'string $template'
        , 'array $children'
        , 'array $data'
        , 'string $output'
        , 'Loader $load'
        , '\Cart\Cart $cart'
        , '\Cart\User $user'
        , '\Cart\Customer $customer'
        , 'Mail $mail'
        , 'Encryption $encryption'
        , 'Event $event'
        , 'Db $db'
        , 'Url $url'
        , 'Request $request'
        , 'Response $response'
        , 'Cache $cache'
        , 'Session $session'
        , 'Language $language'
        , 'Document $document'

    );
    
 $html ='<html><head><script type="text/javascript" src="catalog/view/javascript/jquery/jquery-2.1.1.min.js"></script>
</head><body>';
 
    if (is_writable($pathToController)){
        $rewriteController = true;
    }

    $catalogModels   = getModels();
    $adminModels     = getModels( str_ireplace( $catalogPath, $adminPath, DIR_APPLICATION ) );

    $startupClasses  = getClasses( DIR_SYSTEM . 'startup.php' );
    $registryClasses = getClasses( 'index.php' );

    $textToInsert    = array_unique( array_merge( $properties, $startupClasses, $registryClasses, $catalogModels, $adminModels ) );

    if( $rewriteController ){
        //get line number where start Controller description
        $fp     = fopen( $pathToController, 'r' );
        $lineNumber = getLineOfFile( $fp, $searchLine );
        fclose( $fp );

        //regenerate Controller text with properties
        $file = new SplFileObject( $pathToController );
        $file->seek( $lineNumber );
        $tempFile = sprintf( "<?php %s \t/**%s", PHP_EOL, PHP_EOL );

//        $stop_list = array(
//            //'ModelGkd_',
//            //'ModelExtensionOcfilter'
//        );

        foreach ( $textToInsert as $val ) {
            $tempFile .= sprintf( "\t* @property %s%s", $val, PHP_EOL );
        }
        $tempFile .= sprintf( "\t**/%s%s%s", PHP_EOL, $searchLine, PHP_EOL );
        while ( !$file->eof() ) {
            $tempFile .= $file->fgets();
        }

        //write Controller
        $fp = fopen( $pathToController, 'w' );
        fwrite( $fp, $tempFile );
        fclose( $fp );
       
        $html.= '<h3>Аutocomplete Properties Successfully Installed.</h3>';
    } else {
        $html.=  '<h3>Place the following code above abstract class Controller in your system/engine/controller.php file</h3><hr>';
       	
        $properties='/**'."\n";
        
        foreach($textToInsert as $val)
        {
            $properties .= '* @property '.$val."\n";
        }
        
        $properties .= '**/'."\n";
        
        $propnum=count($textToInsert);
        
        $html.=  '<textarea rows="'. $propnum .'" cols="200" id= "code">'."\n";
        $html.= $properties;
        $html.=  "</textarea>";
        $html.=  '<hr>';
    }
 $html.=  '<script language="javascript" type="text/javascript">
$(document).ready(function () {
$("#code").focus(function() {
    var $this = $(this);
    $this.select();

    // Work around Chromes little problem
    $this.mouseup(function() {
        // Prevent further mouseup intervention
        $this.unbind("mouseup");
        return false;
    });
});
});
</script>';	    
$html.= "</body></html>";
echo $html;


function scanDirectories($rootDir, $allData=array()) {
    // set filenames invisible if you want
    $invisibleFileNames = array(".", "..", ".htaccess", ".htpasswd",'lib','src');
    // run through content of root directory
    $dirContent = scandir($rootDir);
    foreach($dirContent as $key => $content) {
        // filter all files not accessible
        $path = $rootDir.'/'.$content;

        if(!in_array($content, $invisibleFileNames)) {
            // if content is file & readable, add to array
            if(is_file($path) && is_readable($path) && stristr($content,'.php')) {
                // save file name with path
                $allData[] = $path;
                // if content is a directory and readable, add path and name
            }elseif(is_dir($path) && is_readable($path)) {
                // recursive callback to open new directory
                $allData = scanDirectories($path, $allData);
            }
        }
    }
    return $allData;
}
