<?php

// Copyright (C) 2017 Michael H. Potter
// See the LICENSE file for information.

// ----------------
// Action functions
// ----------------

// Create a new queue for a user
function createQueue ($userInfo)
{
    $message = '';
    
    $game = $userInfo->game;
    
    if (empty($userInfo->queues))
    {
        $userInfo->queues = (object) [];
    }
    
    if (empty($userInfo->queues->{$game}))
    {
        $userInfo->queues->{$game} = [];
        
        $message = 'Created queue ' . $game . ' for user ' . $userInfo->user . "\n";
    }
    else
    {
        $message = 'Queue ' . $game . ' already exists for user ' . $userInfo-> user . "\n";
    }
    
    $userInfo->message = $message;
    return $userInfo;
}

// Delete a user's queue
function removeQueue ($userInfo)
{
    $message = '';
    
    $game = $userInfo->game;
    
    if (!empty($userInfo->queues) && isset($userInfo->queues->{$game}))
    {
        unset($userInfo->queues->{$game});
        
        $message = 'Removed queue ' . $game . ' for user ' . $userInfo->user . "\n";
    }
    else
    {
        $message = 'User ' . $userInfo->user . ' has no queue ' . $game . "\n";
    }
    
    unset($userInfo->currentGame);
    
    $userInfo->message = $message;
    return $userInfo;
}

// Add an entry to a queue
function addToQueue ($userInfo)
{
    $message = '';
    
    if (!empty($userInfo->currentGame))
    {
        $cgame = $userInfo->currentGame;
        $id = filter_var($_GET['id'], FILTER_SANITIZE_URL);
        
        if (!empty($userInfo->queues) && isset($userInfo->queues->{$cgame}))
        {
            $numEntries = array_push($userInfo->queues->{$cgame}, $id);
            $message = 'Added ' . $id . ' to queue at position #' . $numEntries;
        }
        else
        {
            $message = 'User ' . $userInfo->user . ' has no queue ' . $game . "\n";
        }
    }
    else
    {
        $message = "You have not set an active queue.\n";
    }
    
    $userInfo->message = $message;
    return $userInfo;
}

// Get the next entry in a queue
function nextInQueue ($userInfo)
{
    $message = '';
    
    if (!empty($userInfo->currentGame))
    {
        $cgame = $userInfo->currentGame;
    
        if (!empty($userInfo->queues) && isset($userInfo->queues->{$cgame}))
        {
            $result = array_shift($userInfo->queues->{$cgame});
            $message = 'Next in queue: ' . $result;
        }
        else
        {
            $message = 'User ' . $userInfo->user . ' has no queue ' . $game . "\n";
        }
    }
    else
    {
        $message = "You have not set an active queue.\n";
    }
    
    $userInfo->message = $message;
    return $userInfo;
}

// List the entries in a queue
function listQueue ($userInfo)
{
    $message = '';
    
    if (!empty($userInfo->currentGame))
    {
        $cgame = $userInfo->currentGame;
        
        if (!empty($userInfo->queues) && isset($userInfo->queues->{$cgame}))
        {
            $result = $userInfo->queues->{$cgame};
            
            if (!empty($result))
            {
                $message = "Current queue:\n";
                foreach ($result as $index => $id)
                {
                    $message .= '#' . ($index+1) . ': ' . $id . ";    \n";
                }
            }
            else
            {
                $message = "The queue is empty.\n";
            }
        }
        else
        {
            $message = "There is no queue for this game.\n";
        }
    }
    else
    {
        $message = "You have not set an active queue.\n";
    }
    
    $userInfo->message = $message;
    return $userInfo;
}

// List all queues belonging to a user
function listAllQueues ($userInfo)
{
    $message = '';
    
    if (!empty($userInfo->queues))
    {
        $message = 'Queues for ' . $userInfo->user . ":\n";
        foreach ($userInfo->queues as $q => $a)
        {
            $message .= $q . "\n";
        }
    }
    else
    {
        $message = "This user has no queues.\n";
    }
    
    $userInfo->message = $message;
    return $userInfo;
}

// Set a given queue as active
function startQueue ($userInfo)
{
    $message = '';
    
    $cgame = '';
    if (empty($userInfo->currentGame))
    {
        $cgame = $userInfo->game;
    }
    else
    {
        $cgame = $userInfo->currentGame;
    }
    
    if (!empty($userInfo->queues) && isset($userInfo->queues->{$cgame}))
    {
        $userInfo->currentGame = $cgame;
        $message = 'Starting queue ' . $cgame;
    }
    else
    {
        $message = 'User ' . $userInfo->user . ' has no queue ' . $cgame . "\n";
    }

    $userInfo->message = $message;
    return $userInfo;
}

// Set the currently active queue as inactive
function stopQueue ($userInfo)
{
    $message = '';
    if (!empty($userInfo->currentGame))
    {
        unset($userInfo->currentGame);
        $message = 'Stopping queue ' . $userInfo->currentGame . "\n";
    }
    else
    {
        $message = "You have no active queue.\n";
    }
    
    $userInfo->message = $message;
    return $userInfo;
}

// Remove all entries from a queue (defaults to active queue)
function clearQueue ($userInfo)
{
    $message = '';
    
    $cgame = '';
    if (empty($userInfo->currentGame))
    {
        $cgame = $userInfo->game;
    }
    else
    {
        $cgame = $userInfo->currentGame;
    }
    
    if (!empty($userInfo->queues) && isset($userInfo->queues->{$cgame}))
    {
        $userInfo->queues->{$cgame} = [];
        $message = 'Cleared queue ' . $cgame . "\n";
    }
    else
    {
        $message = 'User ' . $userInfo->user . ' has no queue ' . $cgame . "\n";
    }
    
    $userInfo->message = $message;
    return $userInfo;
}

// ----------
// Main logic
// ----------

$theUser = $_GET["user"];
$filteredUser = filter_var($theUser, FILTER_SANITIZE_URL);
$userFilename = strtolower('./queues/' . $filteredUser . '.json');

$theGame = $_GET["game"];
if ($theGame == FALSE)
{
    $theGame = 'unnamed';
}

// TODO: Put header-handling checks in here

$theAction = $_GET["action"];

try
{
    if ($theUser == FALSE)
    {
        throw new InvalidArgumentException("You must specify a user.");
    }
    
    if ($theAction == FALSE)
    {
        throw new InvalidArgumentException("You must specify an action.");
    }
    
    if (file_exists($userFilename))
    {
        $userInfo = json_decode(file_get_contents($userFilename));
    }
    else
    {
        $userInfo = (object) ['user' => $theUser];
    }
    
    $userInfo->game = $theGame;

    switch ($theAction)
    {
        case 'new':
            $userInfo = createQueue($userInfo);
            break;
        case 'delete':
            $userInfo = removeQueue($userInfo);
            break;
        case 'list':
            $userInfo = listQueue($userInfo);
            break;
        case 'add':
            $userInfo = addToQueue($userInfo);
            break;
        case 'start':
            $userInfo = startQueue($userInfo);
            break;
        case 'stop':
            $userInfo = stopQueue($userInfo);
            break;
        case 'listall':
            $userInfo = listAllQueues($userInfo);
            break;
        case 'clear':
            $userInfo = clearQueue($userInfo);
            break;
        case 'next':
            $userInfo = nextInQueue($userInfo);
            break;
        default:
            throw new InvalidArgumentException('Unknown action requested: ' . $theAction);
    }
    
    $response = $userInfo->message;
    echo $response;
    unset($userInfo->message);
    
    file_put_contents($userFilename, json_encode($userInfo));    
}
catch (Exception $e)
{
    echo 'Error: ' . $e->getMessage(), "\n";
}
