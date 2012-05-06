#!/usr/bin/php
<?php
/**
 * This class is the Spanwd process manager.
 * @author Thomas Parrott
 * @package spawnd
 */
class Spawnd
{
    /**
     * @var This variable stores information about the managed processes.
     */
    private $_procs;

    /**
     * This method initialises the internal processes list array.
     * @return NULL
     */
    public function __construct()
    {
        $this->_procs = array();
    }

    /**
     * This method starts the process manager and enters main loop.
     * @return NULL
     */
    public function run()
    {
        while( TRUE )
        {
            $this->_startProcesses( $procs );
            $this->_readProcesses( $procs );
        }
    }

    /**
     * This method checks each managed process, and it is not running
     * then it attempts to start it.
     * @return NULL
     */
    private function _startProcesses()
    {
        $descriptorSpec = array(
            1 => array( 'pipe', 'w' ),  // stdout
        );

        foreach( $this->_procs as $i => $procDetail )
        {
            updateProcStatus( $procDetail );

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
                    updateProcStatus( $procDetail );
                    echo "Started process " . $procDetail->pid
                        . " running " . $procDetail->cmd . "\n";
                    sleep(1);
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
                $buf = fread( $stream, 4096 );
                echo $buf;
            }
        }
    }
}
