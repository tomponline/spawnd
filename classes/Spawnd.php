#!/usr/bin/php
<?php
/**
 * This class is the Spanwd process manager.
 * @author Thomas Parrott
 * @package spawnd
 */
class Spawnd
{
    const INI_DIR = '/etc/spawnd';

    /**
     * @var This variable stores information about the managed processes.
     */
    private $_procs;

    /**
     * @var This variable stores global config settings.
     */
    private $_config;

    /**
     * @var This variable contains status information.
     */
    private $_status;

    /**
     * This method initialises the internal processes list array.
     * @return NULL
     */
    public function __construct()
    {
        $this->_procs   = array();
        $this->_config  = array();
        $this->_status  = new StdClass;
        $this->_stats->nextConfigParseTime = 0;
    }

    /**
     * This method lets you add processes to be managed.
     * @param string $name The name of the process to be managed.
     * @param StdClass $config The configuration of this process.
     * @return NULL
     */
    private function _setProcess( $name, StdClass $config )
    {
        //Validate process config.
        if( empty( $config->cmd ) )
        {
            throw new Exception(
                'Process config for ' . $name . ' is missing cmd property' );
        }

        if( empty( $this->_procs[ $name ] ) )
        {
            $this->_procs[ $name ] = new StdClass;
        }

        $fields = array( 'cmd' );

        foreach( $fields as $field )
        {
            if( isset( $config->{ $field } ) )
            {
                $this->_procs[ $name ]->{ $field } = $config->{ $field };
            }
        }
    }

    /**
     * This method parses the files in /etc/spawnd directory.
     * @return NULL
     */
    private function _parseConfig()
    {
        if( is_dir( self::INI_DIR ) && $files = scandir( self::INI_DIR ) )
        {
            foreach( $files as $fileName )
            {
                $file = self::INI_DIR . '/' . $fileName;

                if( is_file( $file ) )
                {
                    if( $sections = parse_ini_file( $file, TRUE ) )
                    {
                        foreach( $sections as $section => $config )
                        {
                            $config = (object) $config;

                            if( 'spawnd' === $section )
                            {
                                $this->_config = $config;
                            }
                            else
                            {
                                $this->_setProcess( $section, $config );
                            }
                        }
                    }
                    else
                    {
                        throw new Exception( $file . ' file is invalid' );
                    }
                }
            }
        }
        else
        {
            throw new Exception( self::INI_DIR . ' directory does not exist' );
        }
    }

    /**
     * This method starts the process manager and enters main loop.
     * @return NULL
     */
    public function run()
    {
        while( TRUE )
        {
            if( time() > $this->_status->nextConfigParseTime )
            {
                $this->_parseConfig();
                $this->_status->nextConfigParseTime = time() + 10;
            }

            $this->_startProcesses();
            $this->_readProcesses();

            if( empty( $this->_procs ) )
            {
                sleep(1);
            }
        }
    }

    /**
     * This method checks each managed process, and it is not running
     * then it attempts to start it.
     * @return NULL
     */
    private function _startProcesses()
    {
        $startedProcs   = 0;
        $descriptorSpec = array(
            1 => array( 'pipe', 'w' ),  // stdout
        );

        foreach( $this->_procs as $i => $procDetail )
        {
            $this->_updateProcStatus( $procDetail );

            if( isset( $procDetail->pid ) && !$procDetail->running )
            {
                echo "Process " . $procDetail->pid
                    . " has stopped with exit code "
                    . $procDetail->exitcode . "\n";
            }

            if( !isset( $procDetail->running ) || !$procDetail->running )
            {
                $proc = proc_open( $procDetail->cmd, $descriptorSpec, $pipes );

                if( is_resource( $proc ) )
                {
                    $procDetail->proc   = $proc;
                    $procDetail->stdout = $pipes[ 1 ];
                    stream_set_blocking ( $procDetail->stdout , FALSE );
                    $procs[ $i ] = $procDetail;
                    $this->_updateProcStatus( $procDetail );
                    echo "Started process " . $procDetail->pid
                        . " running " . $procDetail->cmd . "\n";
                }
            }
        }
    }

    /**
     * This method updates the status information of a managed process.
     * @param StdClass $procDetail The process object.
     * @return NULL
     */
    private function _updateProcStatus( StdClass $procDetail )
    {
        if( isset( $procDetail->proc ) )
        {
            if( $status = proc_get_status( $procDetail->proc ) )
            {
                foreach( $status as $key => $value )
                {
                    if( $key === 'exitcode' && $value === -1 )
                    {
                        continue;
                    }

                    $procDetail->$key = $value;
                }
            }
        }
    }


    /**
     * This method checks all the processes to see if there is any unread
     * output on the stdout stream.
     * @return array stream handles that can be read.
     */
    private function _streamSelect()
    {
        $readStreams = array();

        //Build a read array.
        foreach( $this->_procs as $i => $procDetail )
        {
            $readStreams[ $i ] = $procDetail->stdout;
        }

        $NULL = NULL;

        if( $readStreams )
        {
            $num = stream_select( $readStreams, $NULL, $NULL, 1 );
            if( $num > 0 )
            {
                return $readStreams;
            }
        }
    }

    /**
     * This method reads from any process streams that are ready to be read.
     * @return NULL
     */
    private function _readProcesses()
    {
        //Read output from processes.
        if( $streams = $this->_streamSelect() )
        {
            foreach( $streams as $i => $stream )
            {
                while( $buf = fgets( $stream, 4096 ) )
                {
                    echo $buf;
                }
            }
        }
    }
}
