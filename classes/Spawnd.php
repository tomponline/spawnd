#!/usr/bin/php
<?php
$input = (object) getopt('f:');
$procs = array();

if( !empty( $input->f ) )
{
    $procDetail = new StdClass;
    $procDetail->cmd = $input->f;
    $procs[] = $procDetail;
}

function startProcesses( array &$procs )
{
    $descriptorSpec = array(
        1 => array( 'pipe', 'w' ),  // stdout
    );

    foreach( $procs as $i => $procDetail )
    {
        updateProcStatus( $procDetail );

        if( isset( $procDetail->pid ) && !$procDetail->running )
        {
            echo "Process " . $procDetail->pid
                . " has stopped with exit code " . $procDetail->exitcode . "\n";
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
                echo "Started process "
                    . $procDetail->pid . " running " . $procDetail->cmd . "\n";
                sleep(1);
            }
        }
    }
}

function updateProcStatus( StdClass $procDetail )
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


function streamSelect( array $procs )
{
    $readStreams = array();

    //Build a read array.
    foreach( $procs as $i => $procDetail )
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

function readProcesses( array &$procs )
{
    //Read output from processes.
    if( $streams = streamSelect( $procs ) )
    {
        foreach( $streams as $i => $stream )
        {
            $buf = fread( $stream, 4096 );
            echo $buf;
        }
    }
}


while(1)
{
    startProcesses( $procs );
    readProcesses( $procs );
}
